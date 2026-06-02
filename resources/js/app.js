import './bootstrap';
import Alpine from 'alpinejs';
import Collapse from '@alpinejs/collapse';
import { animate, stagger, inView } from 'motion';
import Swiper from 'swiper';
import { Pagination, Navigation, Autoplay, Thumbs, FreeMode, EffectFade } from 'swiper/modules';

// Import Swiper styles
import 'swiper/css';
import 'swiper/css/pagination';
import 'swiper/css/navigation';
import 'swiper/css/thumbs';
import 'swiper/css/free-mode';
import 'swiper/css/effect-fade';

// ==========================================================================
// Alpine.js Components
// ==========================================================================

// Dark Mode Toggle Component
Alpine.data('darkMode', () => ({
    isDark: false,

    init() {
        // Check localStorage first, then system preference
        const stored = localStorage.getItem('theme');
        if (stored) {
            this.isDark = stored === 'dark';
        } else {
            this.isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        }
        this.apply();

        // Listen for system preference changes (only if no stored preference)
        window.matchMedia('(prefers-color-scheme: dark)')
            .addEventListener('change', e => {
                if (!localStorage.getItem('theme')) {
                    this.isDark = e.matches;
                    this.apply();
                }
            });
    },

    toggle() {
        this.isDark = !this.isDark;
        localStorage.setItem('theme', this.isDark ? 'dark' : 'light');
        this.apply();
    },

    apply() {
        document.documentElement.classList.toggle('dark', this.isDark);
    }
}));

// Metric Count-Up Component
Alpine.data('metricCountUp', () => ({
    displayValue: '',
    rawValue: '',
    hasAnimated: false,

    init() {
        this.rawValue = this.$el.dataset.value || '';
        this.displayValue = this.rawValue; // Show final value as SSR fallback
        this.setupObserver();
    },

    setupObserver() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !this.hasAnimated) {
                    this.hasAnimated = true;
                    this.animateCount();
                    observer.disconnect();
                }
            });
        }, { threshold: 0.3 });
        observer.observe(this.$el);
    },

    animateCount() {
        const parsed = this.parseMetric(this.rawValue);

        if (parsed.number === null) {
            this.displayValue = this.rawValue;
            return;
        }

        // Reset to 0 before animating
        this.displayValue = '0' + parsed.suffix;

        const duration = 1500;
        const startTime = performance.now();
        const endValue = parsed.number;
        const decimals = parsed.decimals;

        const tick = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            // Ease-out cubic
            const eased = 1 - Math.pow(1 - progress, 3);
            const current = endValue * eased;

            if (decimals > 0) {
                this.displayValue = current.toFixed(decimals) + parsed.suffix;
            } else {
                this.displayValue = Math.round(current).toLocaleString() + parsed.suffix;
            }

            if (progress < 1) {
                requestAnimationFrame(tick);
            } else {
                this.displayValue = this.rawValue;
            }
        };

        requestAnimationFrame(tick);
    },

    parseMetric(str) {
        if (!str) return { number: null, suffix: '', decimals: 0 };
        const cleaned = str.replace(/,/g, '');
        const match = cleaned.match(/^([\d.]+)(.*)$/);
        if (!match) return { number: null, suffix: '', decimals: 0 };

        const numStr = match[1];
        const suffix = match[2] || '';
        const number = parseFloat(numStr);
        if (isNaN(number)) return { number: null, suffix: '', decimals: 0 };

        const decPart = numStr.split('.')[1];
        const decimals = decPart ? decPart.length : 0;
        return { number, suffix, decimals };
    }
}));

// Globe Testimonials Component
Alpine.data('globeTestimonials', () => ({
    testimonials: [],
    activeTestimonial: null,
    activeCountry: null,
    activeIndex: -1,
    globe: null,
    isAutoCycling: false,
    autoCycleTimer: null,
    hasUserInteracted: false,
    isInView: false,
    locations: [],
    THREE: null,
    // ISO 3166-1 numeric to alpha-2 mapping for country polygon highlighting
    isoNumToAlpha2: {
        '4':'AF','8':'AL','12':'DZ','20':'AD','24':'AO','28':'AG','32':'AR',
        '36':'AU','40':'AT','48':'BH','50':'BD','51':'AM','52':'BB','56':'BE',
        '64':'BT','68':'BO','70':'BA','72':'BW','76':'BR','84':'BZ','96':'BN',
        '100':'BG','104':'MM','108':'BI','116':'KH','120':'CM','124':'CA',
        '144':'LK','148':'TD','152':'CL','156':'CN','158':'TW','170':'CO',
        '178':'CG','180':'CD','188':'CR','191':'HR','192':'CU','196':'CY',
        '203':'CZ','208':'DK','214':'DO','218':'EC','818':'EG','222':'SV',
        '231':'ET','232':'ER','233':'EE','242':'FJ','246':'FI','250':'FR',
        '266':'GA','268':'GE','276':'DE','288':'GH','300':'GR','320':'GT',
        '328':'GY','332':'HT','340':'HN','348':'HU','352':'IS','356':'IN',
        '360':'ID','364':'IR','368':'IQ','372':'IE','376':'IL','380':'IT',
        '384':'CI','388':'JM','392':'JP','398':'KZ','400':'JO','404':'KE',
        '408':'KP','410':'KR','414':'KW','417':'KG','418':'LA','422':'LB',
        '426':'LS','428':'LV','430':'LR','434':'LY','440':'LT','442':'LU',
        '450':'MG','454':'MW','458':'MY','466':'ML','470':'MT','478':'MR',
        '480':'MU','484':'MX','496':'MN','498':'MD','504':'MA','508':'MZ',
        '512':'OM','516':'NA','524':'NP','528':'NL','540':'NC','554':'NZ',
        '558':'NI','562':'NE','566':'NG','578':'NO','586':'PK','591':'PA',
        '598':'PG','600':'PY','604':'PE','608':'PH','616':'PL','620':'PT',
        '634':'QA','642':'RO','643':'RU','646':'RW','682':'SA','686':'SN',
        '688':'RS','694':'SL','702':'SG','703':'SK','704':'VN','705':'SI',
        '706':'SO','710':'ZA','716':'ZW','724':'ES','728':'SS','729':'SD',
        '740':'SR','752':'SE','756':'CH','760':'SY','762':'TJ','764':'TH',
        '768':'TG','780':'TT','784':'AE','788':'TN','792':'TR','795':'TM',
        '800':'UG','804':'UA','826':'GB','840':'US','858':'UY','860':'UZ',
        '862':'VE','887':'YE','894':'ZM'
    },

    async init() {
        // Load testimonials data
        const dataEl = document.getElementById('testimonials-data');
        if (dataEl) {
            try {
                this.testimonials = JSON.parse(dataEl.textContent);
            } catch (e) {
                console.error('Failed to parse testimonials data:', e);
                this.testimonials = [];
            }
        }

        this.locations = this.testimonials.filter(t => t.lat && t.lng);

        // Initialize globe on desktop only
        if (window.innerWidth >= 1024) {
            await this.initGlobe();
            this.setupScrollObserver();
        }
    },

    async initGlobe() {
        const container = document.getElementById('globe-container');
        if (!container) return;

        // Dynamically import Globe.gl, topojson, and THREE
        const [{ default: Globe }, topojson, THREE] = await Promise.all([
            import('globe.gl'),
            import('topojson-client'),
            import('three')
        ]);

        this.THREE = THREE;

        if (this.locations.length === 0) {
            container.style.display = 'none';
            return;
        }

        // Fetch countries GeoJSON for polygon overlays
        let countryFeatures = [];
        try {
            const res = await fetch('https://cdn.jsdelivr.net/npm/world-atlas@2/countries-110m.json');
            const worldData = await res.json();
            countryFeatures = topojson.feature(worldData, worldData.objects.countries).features;
        } catch (e) {
            console.error('Failed to load world atlas:', e);
        }

        // Determine theme colors
        const isDark = document.documentElement.classList.contains('dark');

        const self = this;

        // Create globe with real earth texture + semi-transparent polygon overlays
        this.globe = Globe()
            .backgroundColor('rgba(0,0,0,0)')
            .globeImageUrl('//cdn.jsdelivr.net/npm/three-globe/example/img/earth-blue-marble.jpg')
            .bumpImageUrl('//cdn.jsdelivr.net/npm/three-globe/example/img/earth-topology.png')
            .showAtmosphere(true)
            .atmosphereColor(isDark ? '#41EAD4' : '#2E9F91')
            .atmosphereAltitude(0.2)
            .width(container.offsetWidth)
            .height(container.offsetHeight)

            // Subtle polygon overlays for land-mass definition (no active highlighting)
            .polygonsData(countryFeatures)
            .polygonCapMaterial(() => {
                return new THREE.MeshLambertMaterial({
                    color: isDark ? '#1B3A4B' : '#2D8B70',
                    opacity: isDark ? 0.6 : 0.15,
                    transparent: true,
                    side: THREE.DoubleSide
                });
            })
            .polygonSideColor(() => 'rgba(0, 0, 0, 0)')
            .polygonStrokeColor(() => isDark ? 'rgba(65, 234, 212, 0.1)' : 'rgba(0, 0, 0, 0.08)')
            .polygonAltitude(() => 0.006)

            // Custom HTML pin markers (all pulsing is CSS-only in HTML layer)
            .htmlElementsData(this.locations)
            .htmlLat('lat')
            .htmlLng('lng')
            .htmlAltitude(0.06)
            .htmlElement(d => {
                const el = document.createElement('div');
                el.className = 'globe-marker';
                el.dataset.countryCode = d.country_code;
                el.innerHTML = `
                    <div class="globe-pin">
                        <div class="globe-pin-head">
                            <div class="globe-pin-ping"></div>
                            <div class="globe-pin-ping globe-pin-ping-delayed"></div>
                        </div>
                        <div class="globe-pin-stem"></div>
                    </div>
                `;
                el.style.cursor = 'pointer';
                el.addEventListener('click', () => self.focusTestimonial(d));
                return el;
            })
            .htmlElementVisibilityModifier((el, isVisible) => {
                if (isVisible) {
                    el.style.opacity = '1';
                    el.style.pointerEvents = 'auto';
                } else {
                    el.style.opacity = '0';
                    el.style.pointerEvents = 'none';
                }
            })

            (container);

        // Globe controls
        this.globe.controls().autoRotate = true;
        this.globe.controls().autoRotateSpeed = 0.5;
        this.globe.controls().enableZoom = false;
        this.globe.controls().enablePan = false;

        // Set globe material to match theme
        const globeMaterial = this.globe.globeMaterial();
        if (isDark) {
            globeMaterial.color.set('#0D1B2A');
            globeMaterial.emissive.set('#0D1B2A');
            globeMaterial.emissiveIntensity = 0.1;
        } else {
            globeMaterial.color.set('#ffffff');
            globeMaterial.emissive.set('#000000');
            globeMaterial.emissiveIntensity = 0;
        }

        // Interaction handlers
        container.addEventListener('mouseenter', () => {
            if (this.globe && !this.isAutoCycling) {
                this.globe.controls().autoRotate = false;
            }
        });
        container.addEventListener('mouseleave', () => {
            if (this.globe && !this.activeTestimonial && !this.isAutoCycling) {
                this.globe.controls().autoRotate = true;
            }
        });

        // Responsive resize
        const resizeObserver = new ResizeObserver(entries => {
            for (const entry of entries) {
                const { width, height } = entry.contentRect;
                if (this.globe && width > 0 && height > 0) {
                    this.globe.width(width).height(height);
                }
            }
        });
        resizeObserver.observe(container);

        // Dark mode reactivity
        const darkModeObserver = new MutationObserver(() => this.updateGlobeTheme());
        darkModeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    },

    setupScrollObserver() {
        const section = this.$el;
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !this.isInView) {
                    this.isInView = true;
                    setTimeout(() => {
                        if (!this.hasUserInteracted) {
                            this.startAutoCycle();
                        }
                    }, 1500);
                } else if (!entry.isIntersecting) {
                    this.isInView = false;
                    this.stopAutoCycle();
                }
            });
        }, { threshold: 0.3 });
        observer.observe(section);
    },

    startAutoCycle() {
        if (this.isAutoCycling) return;
        this.isAutoCycling = true;
        this.cycleNext();
        this.autoCycleTimer = setInterval(() => this.cycleNext(), 5000);
    },

    stopAutoCycle() {
        this.isAutoCycling = false;
        if (this.autoCycleTimer) {
            clearInterval(this.autoCycleTimer);
            this.autoCycleTimer = null;
        }
    },

    cycleNext() {
        if (this.locations.length === 0) return;
        this.activeIndex = (this.activeIndex + 1) % this.locations.length;
        this.focusTestimonial(this.locations[this.activeIndex], true);
    },

    focusTestimonial(data, isAutomatic = false) {
        if (!isAutomatic) {
            this.hasUserInteracted = true;
            this.stopAutoCycle();
            // Resume auto-cycle after 15 seconds of inactivity
            setTimeout(() => {
                if (!this.isAutoCycling && this.isInView && this.hasUserInteracted) {
                    this.hasUserInteracted = false;
                    this.startAutoCycle();
                }
            }, 15000);
        }

        // Update active state
        this.activeTestimonial = data;
        this.activeCountry = data.country_code;
        this.activeIndex = this.locations.findIndex(t => t.id === data.id);

        // Rotate globe to focus on the location
        if (this.globe) {
            this.globe.pointOfView(
                { lat: data.lat, lng: data.lng, altitude: 2.2 },
                1200
            );
            this.globe.controls().autoRotate = false;
        }

        // Update marker visual states
        this.updateMarkerStates(data.country_code);
    },

    updateMarkerStates(activeCode) {
        document.querySelectorAll('.globe-marker').forEach(marker => {
            marker.classList.toggle('active', marker.dataset.countryCode === activeCode);
        });
    },

    selectCountry(countryCode) {
        const testimonial = this.testimonials.find(t => t.country_code === countryCode);
        if (!testimonial) return;

        // Desktop: focus globe
        if (window.innerWidth >= 1024 && this.globe && testimonial.lat && testimonial.lng) {
            this.focusTestimonial(testimonial, false);
        }

        // Mobile: slide Swiper
        if (window.innerWidth < 1024 && window._testimonialsSwiper) {
            const index = this.testimonials.findIndex(t => t.country_code === countryCode);
            if (index >= 0) {
                window._testimonialsSwiper.slideToLoop(index);
            }
        }

        this.activeCountry = countryCode;
    },

    updateGlobeTheme() {
        if (!this.globe || !this.THREE) return;
        const isDark = document.documentElement.classList.contains('dark');
        const THREE = this.THREE;

        // Update polygon materials for theme
        this.globe
            .polygonCapMaterial(() => {
                return new THREE.MeshLambertMaterial({
                    color: isDark ? '#1B3A4B' : '#2D8B70',
                    opacity: isDark ? 0.6 : 0.15,
                    transparent: true,
                    side: THREE.DoubleSide
                });
            })
            .polygonStrokeColor(() => isDark ? 'rgba(65, 234, 212, 0.1)' : 'rgba(0, 0, 0, 0.08)')
            .atmosphereColor(isDark ? 'rgba(65, 234, 212, 0.4)' : 'rgba(65, 234, 212, 0.3)');

        // Update globe material for texture tinting
        const globeMaterial = this.globe.globeMaterial();
        if (isDark) {
            globeMaterial.color.set('#0D1B2A');
            globeMaterial.emissive.set('#0D1B2A');
            globeMaterial.emissiveIntensity = 0.1;
        } else {
            globeMaterial.color.set('#ffffff');
            globeMaterial.emissive.set('#000000');
            globeMaterial.emissiveIntensity = 0;
        }
    },

    get progressWidth() {
        if (this.locations.length === 0 || this.activeIndex < 0) return '0%';
        return ((this.activeIndex + 1) / this.locations.length * 100) + '%';
    }
}));

// Live Scores Component (T20 WC 2026 index page)
Alpine.data('liveScores', () => ({
    matches: [],
    loading: true,
    pollTimer: null,

    init() {
        this.fetchMatches();

        // Listen for Echo broadcasts if available
        if (window.Echo) {
            window.Echo.channel('sports.live')
                .listen('.score.updated', (data) => {
                    this.updateMatch(data);
                });
        }

        // Poll every 20 seconds
        this.pollTimer = setInterval(() => this.fetchMatches(), 20000);
    },

    async fetchMatches() {
        try {
            const response = await axios.get('/api/sports/live');
            this.matches = response.data.matches || [];
        } catch (e) {
            // Silently fail - scores are non-critical
        } finally {
            this.loading = false;
        }
    },

    updateMatch(data) {
        const index = this.matches.findIndex(m => m.match_id === data.match_id);
        if (index >= 0) {
            this.matches[index] = { ...this.matches[index], ...data };
        } else {
            this.fetchMatches();
        }
    },

    destroy() {
        if (this.pollTimer) clearInterval(this.pollTimer);
    }
}));

// Match Detail Component — cricket-specific with batsmen, bowler, recent balls
Alpine.data('matchDetail', (matchId) => ({
    status: null,
    homeScore: null,
    awayScore: null,
    resultSummary: null,
    matchStatus: null,
    batsmen: [],
    bowler: {},
    recentBalls: [],
    latestBall: null,
    playerOfMatch: {},
    teamScores: {},
    pollTimer: null,
    homeScoreAnimating: false,
    awayScoreAnimating: false,
    wicketFlash: false,

    init() {
        this.fetchDetail();

        // Listen for Echo broadcasts if available
        if (window.Echo) {
            window.Echo.channel('sports.match.' + matchId)
                .listen('.score.updated', (data) => {
                    this.applyData(data);
                });
        }

        // Poll every 10 seconds for live data
        this.pollTimer = setInterval(() => this.fetchDetail(), 10000);
    },

    async fetchDetail() {
        try {
            const response = await axios.get('/api/sports/match/' + matchId + '/detail');
            this.applyData(response.data);
        } catch (e) {
            // Silently fail
        }
    },

    parseScore(scoreStr) {
        if (!scoreStr || scoreStr === '-' || scoreStr === 'Yet to bat') return null;
        const m = scoreStr.match(/^(\d+)\/(\d+)\s*\(([^)]+)\)$/);
        return m ? { runs: parseInt(m[1]), wickets: parseInt(m[2]), overs: m[3] } : null;
    },

    applyData(data) {
        // Detect score changes for animations
        const oldHome = this.parseScore(this.homeScore);
        const oldAway = this.parseScore(this.awayScore);

        this.status = data.status || this.status;
        this.homeScore = data.home_display_score || this.homeScore;
        this.awayScore = data.away_display_score || this.awayScore;

        const newHome = this.parseScore(this.homeScore);
        const newAway = this.parseScore(this.awayScore);

        // Runs changed — trigger pop animation
        if (oldHome && newHome && oldHome.runs !== newHome.runs) {
            this.homeScoreAnimating = true;
            setTimeout(() => this.homeScoreAnimating = false, 600);
        }
        if (oldAway && newAway && oldAway.runs !== newAway.runs) {
            this.awayScoreAnimating = true;
            setTimeout(() => this.awayScoreAnimating = false, 600);
        }

        // Wicket fallen — trigger shake/flash
        if ((oldHome && newHome && newHome.wickets > oldHome.wickets) ||
            (oldAway && newAway && newAway.wickets > oldAway.wickets)) {
            this.wicketFlash = true;
            setTimeout(() => this.wicketFlash = false, 1000);
        }

        this.resultSummary = data.result_summary || this.resultSummary;
        this.matchStatus = data.match_status || this.matchStatus;
        this.batsmen = data.batsmen || this.batsmen;
        this.bowler = data.bowler || this.bowler;
        this.recentBalls = data.recent_balls || this.recentBalls;
        this.latestBall = data.latest_ball || this.latestBall;
        this.playerOfMatch = data.player_of_match || this.playerOfMatch;
        this.teamScores = data.team_scores || this.teamScores;
    },

    ballColor(event) {
        switch (event) {
            case 'FOUR': return 'bg-green-500/20 text-green-400';
            case 'SIX': return 'bg-purple-500/20 text-purple-400';
            case 'WICKET': return 'bg-red-500/20 text-red-400';
            case 'HUNDRED': return 'bg-yellow-500/20 text-yellow-400';
            default: return 'bg-soft/10 text-soft-dark dark:text-soft';
        }
    },

    destroy() {
        if (this.pollTimer) clearInterval(this.pollTimer);
    }
}));

// Project Showcase Component — screenshot carousel + lightbox (project-detail page)
Alpine.data('projectShowcase', () => ({
    lightboxOpen: false,
    lightboxIndex: 0,
    mainSwiper: null,
    thumbsSwiper: null,
    imageCount: 0,

    init() {
        this.imageCount = this.$el.querySelectorAll('.screenshot-swiper-main .swiper-slide').length;
        this.$nextTick(() => this.initSwipers());
    },

    initSwipers() {
        const thumbsEl = this.$refs.thumbsSwiper;
        if (thumbsEl) {
            this.thumbsSwiper = new Swiper(thumbsEl, {
                modules: [FreeMode],
                slidesPerView: 'auto',
                spaceBetween: 12,
                watchSlidesProgress: true,
                freeMode: true,
            });
        }

        const mainEl = this.$refs.mainSwiper;
        if (mainEl) {
            this.mainSwiper = new Swiper(mainEl, {
                modules: [Pagination, Navigation, Autoplay, Thumbs],
                slidesPerView: 1,
                spaceBetween: 0,
                loop: this.imageCount > 1,
                keyboard: { enabled: true },
                autoplay: { delay: 5000, disableOnInteraction: true },
                navigation: {
                    nextEl: this.$refs.swiperNext,
                    prevEl: this.$refs.swiperPrev,
                },
                pagination: {
                    el: this.$refs.swiperPagination,
                    clickable: true,
                },
                thumbs: this.thumbsSwiper ? { swiper: this.thumbsSwiper } : undefined,
            });
        }
    },

    openLightbox(index) {
        this.lightboxIndex = index;
        this.lightboxOpen = true;
        document.body.style.overflow = 'hidden';
    },

    closeLightbox() {
        this.lightboxOpen = false;
        document.body.style.overflow = '';
    },

    lightboxPrev() {
        this.lightboxIndex = (this.lightboxIndex - 1 + this.imageCount) % this.imageCount;
    },

    lightboxNext() {
        this.lightboxIndex = (this.lightboxIndex + 1) % this.imageCount;
    },
}));

window.Alpine = Alpine;
Alpine.plugin(Collapse);
Alpine.start();

// ==========================================================================
// Motion Animations
// ==========================================================================

document.addEventListener('DOMContentLoaded', () => {

    // ---------- Fade Up Animation ----------
    // Elements with .animate-up will fade in and slide up when scrolled into view
    inView('.animate-up', ({ target }) => {
        animate(
            target,
            { opacity: [0, 1], y: [40, 0] },
            { duration: 0.7, easing: [0.22, 1, 0.36, 1] }
        );
    }, { margin: '-100px' });

    // ---------- Fade In Animation ----------
    inView('.animate-fade', ({ target }) => {
        animate(
            target,
            { opacity: [0, 1] },
            { duration: 0.6, easing: 'ease-out' }
        );
    }, { margin: '-50px' });

    // ---------- Scale Up Animation ----------
    inView('.animate-scale', ({ target }) => {
        animate(
            target,
            { opacity: [0, 1], scale: [0.9, 1] },
            { duration: 0.5, easing: [0.22, 1, 0.36, 1] }
        );
    }, { margin: '-50px' });

    // ---------- Staggered Children Animation ----------
    // Parent with .animate-stagger will animate its children sequentially
    document.addEventListener('DOMContentLoaded', () => {
        // Initialize all Motion.js animations AFTER DOM is ready
        inView('.animate-stagger', ({ target }) => {
            if (!target || !target.children) return;

            const children = target.children;
            if (children.length > 0) {
                animate(
                    children,
                    { opacity: [0, 1], y: [30, 0] },
                    {
                        duration: 0.6,
                        delay: stagger(0.1),
                        easing: [0.22, 1, 0.36, 1]
                    }
                );
            }
        }, { margin: '-100px' });
    });


    // ---------- Slide In From Left ----------
    inView('.animate-slide-left', ({ target }) => {
        animate(
            target,
            { opacity: [0, 1], x: [-50, 0] },
            { duration: 0.6, easing: [0.22, 1, 0.36, 1] }
        );
    }, { margin: '-50px' });

    // ---------- Slide In From Right ----------
    inView('.animate-slide-right', ({ target }) => {
        animate(
            target,
            { opacity: [0, 1], x: [50, 0] },
            { duration: 0.6, easing: [0.22, 1, 0.36, 1] }
        );
    }, { margin: '-50px' });

    // ---------- Hero Text Animation ----------
    const heroText = document.querySelector('.hero-text');
    if (heroText) {
        const words = heroText.querySelectorAll('.hero-word');
        if (words.length > 0) {
            animate(
                words,
                { opacity: [0, 1], y: [20, 0] },
                { delay: stagger(0.1), duration: 0.5 }
            );
        }
    }

    // ---------- Floating Elements Animation ----------
    const floatingElements = document.querySelectorAll('.float-element');
    floatingElements.forEach((el, index) => {
        const delay = index * 2;
        animate(
            el,
            { y: [0, -20, 0] },
            { duration: 4, repeat: Infinity, easing: 'ease-in-out', delay }
        );
    });

    // ---------- Project Card Hover Effect ----------
    document.querySelectorAll('.project-card').forEach(card => {
        card.addEventListener('mouseenter', () => {
            animate(card, { y: -8 }, { duration: 0.3, easing: [0.22, 1, 0.36, 1] });
        });
        card.addEventListener('mouseleave', () => {
            animate(card, { y: 0 }, { duration: 0.3, easing: [0.22, 1, 0.36, 1] });
        });
    });

    // ---------- Button Hover Glow ----------
    document.querySelectorAll('.btn-primary, .btn-sunset').forEach(btn => {
        btn.addEventListener('mouseenter', () => {
            animate(btn, { scale: 1.02 }, { duration: 0.2 });
        });
        btn.addEventListener('mouseleave', () => {
            animate(btn, { scale: 1 }, { duration: 0.2 });
        });
    });

    // ---------- Navbar Scroll Effect ----------
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        let lastScrollY = window.scrollY;

        window.addEventListener('scroll', () => {
            const currentScrollY = window.scrollY;

            if (currentScrollY > 100) {
                navbar.classList.add('glass', 'shadow-lg');
            } else {
                navbar.classList.remove('glass', 'shadow-lg');
            }

            lastScrollY = currentScrollY;
        });
    }

    // ---------- Smooth Scroll for Anchor Links ----------
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;

            const target = document.querySelector(targetId);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Initialize testimonials swiper with auto-scroll and custom controls
    const swiperEl = document.querySelector('.testimonials-swiper');
    if (swiperEl) {
        window._testimonialsSwiper = new Swiper('.testimonials-swiper', {
            modules: [Pagination, Navigation, Autoplay, EffectFade],
            loop: true,
            slidesPerView: 1,
            spaceBetween: 0,
            effect: 'fade',
            fadeEffect: {
                crossFade: true,
            },
            autoplay: {
                delay: 4000,
                disableOnInteraction: false,
                pauseOnMouseEnter: true,
            },
            speed: 1000,
            pagination: false,
            navigation: false,
        });

        // Swiper slide change handler - update flag rail active states
        window._testimonialsSwiper.on('slideChange', () => {
            const activeIndex = window._testimonialsSwiper.realIndex;
            const flagButtons = document.querySelectorAll('.flag-rail-btn');
            flagButtons.forEach(btn => btn.classList.remove('active'));
            if (flagButtons[activeIndex]) {
                flagButtons[activeIndex].classList.add('active');
            }
        });

        // Hover pause/resume
        swiperEl.addEventListener('mouseenter', () => {
            window._testimonialsSwiper.autoplay.pause();
        });
        swiperEl.addEventListener('mouseleave', () => {
            window._testimonialsSwiper.autoplay.resume();
        });
    }

});

// ==========================================================================
// Mobile Menu Toggle (Alpine handles this, but fallback for non-Alpine)
// ==========================================================================
window.toggleMobileMenu = function() {
    const menu = document.getElementById('mobile-menu');
    if (menu) {
        menu.classList.toggle('hidden');
    }
};

