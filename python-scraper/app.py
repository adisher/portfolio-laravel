import json
import re
import time
from flask import Flask, jsonify
from curl_cffi import requests as cffi_requests
from bs4 import BeautifulSoup

app = Flask(__name__)


# ==================== HELPER FUNCTIONS ====================


def cffi_fetch(url, max_retries=3):
    """Fetch a URL using curl_cffi with retry logic."""
    for attempt in range(max_retries):
        try:
            response = cffi_requests.get(
                url,
                impersonate="chrome131",
                timeout=30,
                headers={
                    "Referer": "https://www.cricbuzz.com/",
                    "Accept-Language": "en-US,en;q=0.9",
                },
            )
            if response.status_code == 200 and "Access Denied" not in response.text:
                return response.text
            print(f"Attempt {attempt+1} blocked or bad status, retrying...")
        except Exception as e:
            print(f"Attempt {attempt+1} error: {e}")
        time.sleep(3)
    return None


def extract_rsc_payload(html):
    """Extract and concatenate Next.js RSC payload from script tags."""
    push_pattern = re.compile(r'self\.__next_f\.push\(\[1,"(.+?)"\]\)', re.DOTALL)
    raw_chunks = push_pattern.findall(html)

    full_payload = ""
    for chunk in raw_chunks:
        try:
            full_payload += chunk.encode().decode("unicode_escape")
        except Exception:
            full_payload += chunk.replace('\\"', '"').replace("\\\\", "\\")
    return full_payload


def extract_json_object(text, start_idx):
    """Extract a balanced JSON object starting at start_idx."""
    if start_idx >= len(text) or text[start_idx] != "{":
        return None

    depth = 0
    in_string = False
    escape_next = False

    for i in range(start_idx, min(start_idx + 50000, len(text))):
        ch = text[i]

        if escape_next:
            escape_next = False
            continue

        if ch == "\\" and in_string:
            escape_next = True
            continue

        if ch == '"' and not escape_next:
            in_string = not in_string
            continue

        if in_string:
            continue

        if ch == "{":
            depth += 1
        elif ch == "}":
            depth -= 1
            if depth == 0:
                return text[start_idx : i + 1]

    return None


# ==================== MATCH LIST (SERIES) ====================


def fetch_series_matches(series_id):
    """Return list of matches for a given series ID."""
    url = f"https://www.cricbuzz.com/cricket-series/{series_id}/icc-mens-t20-world-cup-2026/matches"
    print(f"Fetching series {series_id} matches...")

    html = cffi_fetch(url)
    if not html:
        return {"error": "Failed to fetch series page", "matches": []}

    return parse_series_matches(html)


def parse_series_matches(html):
    """Extract match data from Next.js RSC payload."""
    payload = extract_rsc_payload(html)
    if not payload:
        print("No RSC payload found")
        return []

    # Find matchDetails JSON block
    match_details_pattern = re.compile(
        r'"matchDetails"\s*:\s*\[(.+?)\],"landingPosition"', re.DOTALL
    )
    md_match = match_details_pattern.search(payload)

    if not md_match:
        print("Could not find matchDetails in RSC payload")
        return []

    try:
        match_details = json.loads("[" + md_match.group(1) + "]")
    except json.JSONDecodeError as e:
        print(f"JSON parse error: {e}")
        return []

    matches = []
    for day_group in match_details:
        details_map = day_group.get("matchDetailsMap", {})
        date_key = details_map.get("key", "")
        day_matches = details_map.get("match", [])

        for m in day_matches:
            info = m.get("matchInfo", {})
            score = m.get("matchScore", {})
            team1 = info.get("team1", {})
            team2 = info.get("team2", {})
            venue = info.get("venueInfo", {})

            match_data = {
                "id": info.get("matchId"),
                "url": f"https://www.cricbuzz.com/live-cricket-scores/{info.get('matchId')}",
                "match_desc": info.get("matchDesc", ""),
                "format": info.get("matchFormat", ""),
                "date": date_key,
                "start_date": info.get("startDate", ""),
                "state": info.get("state", "").lower(),
                "status": info.get("status", ""),
                "teams": {
                    "team1": {
                        "id": team1.get("teamId"),
                        "short": team1.get("teamSName", ""),
                        "full": team1.get("teamName", ""),
                    },
                    "team2": {
                        "id": team2.get("teamId"),
                        "short": team2.get("teamSName", ""),
                        "full": team2.get("teamName", ""),
                    },
                },
                "venue": {
                    "ground": venue.get("ground", ""),
                    "city": venue.get("city", ""),
                },
                "scores": {},
            }

            if score:
                t1_score = score.get("team1Score", {}).get("inngs1", {})
                t2_score = score.get("team2Score", {}).get("inngs1", {})
                if t1_score:
                    match_data["scores"]["team1"] = (
                        f"{t1_score.get('runs', '')}/{t1_score.get('wickets', '')} "
                        f"({t1_score.get('overs', '')})"
                    )
                if t2_score:
                    match_data["scores"]["team2"] = (
                        f"{t2_score.get('runs', '')}/{t2_score.get('wickets', '')} "
                        f"({t2_score.get('overs', '')})"
                    )

            matches.append(match_data)

    print(f"Extracted {len(matches)} matches from RSC payload")
    return matches


# ==================== INDIVIDUAL MATCH DATA ====================


def fetch_match_data(match_id):
    """Fetch detailed data for a specific match."""
    url = f"https://www.cricbuzz.com/live-cricket-scores/{match_id}"
    print(f"Fetching match {match_id}...")

    html = cffi_fetch(url)
    if not html:
        return {"error": "Failed to fetch match page", "match_id": match_id}

    return parse_match_page(html)


def parse_match_page(html):
    """Parse individual match page from RSC payload."""
    payload = extract_rsc_payload(html)
    if not payload:
        return {"error": "No RSC payload found"}

    data = {
        "last_updated": time.time(),
    }

    # --- Extract miniscore block (live/in-progress matches) ---
    mini_idx = payload.find('"miniscore":{')
    if mini_idx != -1:
        miniscore_json = extract_json_object(payload, mini_idx + len('"miniscore":'))
        if miniscore_json:
            try:
                mini = json.loads(miniscore_json)
                data.update(_parse_miniscore(mini))
            except json.JSONDecodeError:
                pass

    # --- Extract matchHeader for common info ---
    header_idx = payload.find('"matchHeader":{')
    if header_idx != -1:
        header_json = extract_json_object(payload, header_idx + len('"matchHeader":'))
        if header_json:
            try:
                header = json.loads(header_json)
                data.update(_parse_match_header(header))
            except json.JSONDecodeError:
                pass

    # --- Detect match state ---
    state = data.get("state", "")
    if not state:
        status = data.get("match_status", "")
        if "won by" in status.lower() or "tied" in status.lower():
            data["match_state"] = "completed"
        elif mini_idx != -1:
            data["match_state"] = "live"
        else:
            data["match_state"] = "upcoming"
    else:
        state_lower = state.lower()
        if state_lower in ("complete", "abandon"):
            data["match_state"] = "completed"
        elif state_lower in ("preview", "upcoming"):
            data["match_state"] = "upcoming"
        else:
            data["match_state"] = "live"

    # --- Last wicket ---
    lwm = re.search(r'"lastWicket"\s*:\s*"([^"]*)"', payload)
    if lwm:
        data["last_wicket"] = lwm.group(1)

    # --- Summary from meta description ---
    soup = BeautifulSoup(html, "html.parser")
    meta_desc = soup.find("meta", {"name": "description"})
    if meta_desc:
        data["summary"] = meta_desc.get("content", "")

    return data


def _parse_miniscore(mini):
    """Parse the miniscore JSON block."""
    data = {
        "match_status": mini.get("status", "N/A"),
        "team_scores": {},
        "batsmen": [],
        "bowler": {},
        "bowler_non_striker": {},
        "recent_overs": mini.get("recentOvsStats", ""),
        "event": mini.get("event", ""),
        "target": mini.get("target"),
        "overs": mini.get("overs"),
    }

    # Team scores
    bat_obj = mini.get("batTeamScoreObj", {})
    bowl_obj = mini.get("bowlTeamScoreObj", {})

    for label, obj in [("batting", bat_obj), ("bowling", bowl_obj)]:
        innings_list = obj.get("teamInningsArray", [])
        if innings_list:
            inn = innings_list[0]
            team_name = inn.get("batTeamName", obj.get("teamName", ""))
            score = inn.get("score", "")
            wickets = inn.get("wickets", "")
            overs = inn.get("overs", "")
            data["team_scores"][label] = f"{team_name} {score}/{wickets} ({overs})"

    crr = mini.get("currentRunRate")
    req = mini.get("requiredRunRate")
    if crr and data["team_scores"].get("batting"):
        data["team_scores"]["batting"] += f" CRR: {crr}"
    if req and data["team_scores"].get("batting"):
        data["team_scores"]["batting"] += f" REQ: {req}"

    # Batsmen
    for key in ["batsmanStriker", "batsmanNonStriker"]:
        bat = mini.get(key)
        if bat:
            data["batsmen"].append(
                {
                    "name": bat.get("name", ""),
                    "runs": str(bat.get("runs", "")),
                    "balls": str(bat.get("balls", "")),
                    "fours": bat.get("fours", 0),
                    "sixes": bat.get("sixes", 0),
                    "strike_rate": bat.get("strikeRate", ""),
                    "striker": key == "batsmanStriker",
                }
            )

    # Bowlers
    bowler = mini.get("bowlerStriker")
    if bowler:
        data["bowler"] = {
            "name": bowler.get("name", ""),
            "overs": str(bowler.get("overs", "")),
            "maidens": str(bowler.get("maidens", "")),
            "runs": str(bowler.get("runs", "")),
            "wickets": str(bowler.get("wickets", "")),
            "economy": str(bowler.get("economy", "")),
        }

    bowler_ns = mini.get("bowlerNonStriker")
    if bowler_ns:
        data["bowler_non_striker"] = {
            "name": bowler_ns.get("name", ""),
            "overs": str(bowler_ns.get("overs", "")),
            "maidens": str(bowler_ns.get("maidens", "")),
            "runs": str(bowler_ns.get("runs", "")),
            "wickets": str(bowler_ns.get("wickets", "")),
            "economy": str(bowler_ns.get("economy", "")),
        }

    # Partnership
    partnership = mini.get("partnerShip")
    if partnership:
        data["partnership"] = partnership

    # Performance stats
    perf = mini.get("latestPerformance")
    if perf:
        data["latest_performance"] = perf

    return data


def _parse_match_header(header):
    """Parse the matchHeader JSON block."""
    data = {}

    data["match_id"] = header.get("matchId")
    data["match_desc"] = header.get("matchDescription", "")
    data["match_format"] = header.get("matchFormat", "")
    data["state"] = header.get("state", "")
    data["status"] = header.get("status", "")

    # Result if completed
    result = header.get("result", {})
    if result:
        data["result"] = result.get("resultType", "")
        data["winning_team"] = result.get("winningTeam", "")

    # Teams
    team1 = header.get("team1", {})
    team2 = header.get("team2", {})
    if team1 or team2:
        data["teams"] = {
            "team1": {
                "id": team1.get("id"),
                "short": team1.get("shortName", team1.get("name", "")),
                "full": team1.get("name", ""),
            },
            "team2": {
                "id": team2.get("id"),
                "short": team2.get("shortName", team2.get("name", "")),
                "full": team2.get("name", ""),
            },
        }

    # Toss
    toss = header.get("tossResults", {})
    if toss:
        data["toss"] = {
            "winner": toss.get("tossWinnerName", ""),
            "decision": toss.get("decision", ""),
        }

    # Venue
    venue = header.get("venue", {})
    if venue:
        data["venue"] = {
            "ground": venue.get("ground", venue.get("name", "")),
            "city": venue.get("city", ""),
        }

    # Player of the match
    pom = header.get("playersOfTheMatch")
    if pom:
        data["player_of_match"] = [
            {"name": p.get("fullName", p.get("name", "")), "id": p.get("id")}
            for p in pom
        ]

    return data


# ==================== FLASK ENDPOINTS ====================


@app.route("/")
def index():
    return jsonify(
        {
            "name": "Cricbuzz API",
            "endpoints": {
                "/series/<series_id>/matches": "Get all matches in a series",
                "/match/<match_id>": "Get detailed match data (upcoming/live/completed)",
            },
        }
    )


@app.route("/series/<int:series_id>/matches")
def series_matches(series_id):
    matches = fetch_series_matches(series_id)
    if isinstance(matches, dict) and "error" in matches:
        return jsonify(matches), 500
    return jsonify(
        {
            "series_id": series_id,
            "total_matches": len(matches),
            "matches": matches,
        }
    )


@app.route("/match/<int:match_id>")
def match_detail(match_id):
    data = fetch_match_data(match_id)
    return jsonify(data)


# ==================== RUN ====================

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000, debug=False)
