import { useCurrentFrame, useVideoConfig } from 'remotion';
import { colors, font, SAFE_X } from '../brand';
import type { Word } from '../types';

const CHUNK = 5;

/**
 * Spoken-word captions for the current scene, a few words at a time, with the
 * word being said highlighted. Sits above the zone Instagram and YouTube
 * cover with their own UI.
 */
export const Captions: React.FC<{ words: Word[] }> = ({ words }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const t = frame / fps;

  if (!words.length) return null;

  let current = words.findIndex((w) => t >= w.start && t < w.end);
  if (current === -1) current = t < words[0].start ? 0 : words.length - 1;
  if (t < words[0].start - 0.15) return null;

  const from = Math.floor(current / CHUNK) * CHUNK;
  const chunk = words.slice(from, from + CHUNK);

  return (
    <div
      style={{
        position: 'absolute', left: SAFE_X, right: SAFE_X + 60, top: 1300,
        display: 'flex', flexWrap: 'wrap', justifyContent: 'center', gap: '6px 16px',
        fontFamily: font, fontWeight: 900, fontSize: 58, lineHeight: 1.15,
        textTransform: 'uppercase', letterSpacing: 0.5,
      }}
    >
      {chunk.map((w, i) => {
        const active = from + i === current;
        return (
          <span
            key={from + i}
            style={{
              color: active ? colors.midnight : colors.text,
              backgroundColor: active ? colors.teal : 'transparent',
              padding: '2px 12px', borderRadius: 12,
              textShadow: active ? 'none' : '0 4px 18px rgba(0,0,0,0.85)',
              transform: active ? 'scale(1.06)' : 'none',
            }}
          >
            {w.text}
          </span>
        );
      })}
    </div>
  );
};
