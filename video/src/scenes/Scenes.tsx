import { AbsoluteFill, Img, interpolate, spring, staticFile, useCurrentFrame, useVideoConfig } from 'remotion';
import { colors, font, SAFE_X } from '../brand';
import { CodeCard, ImageCard, Portrait } from '../components/Media';
import { Headline } from '../components/Headline';
import type { ReelProps, Scene } from '../types';

type SceneProps = { scene: Scene; reel: ReelProps; index: number; pointNumber: number };

/** Which image a scene shows, falling back to the article image or nothing. */
const imageFor = (scene: Scene, reel: ReelProps): string | null => {
  if (scene.visual === 'portrait') return reel.portrait ?? reel.featuredImage;
  if (scene.visual === 'featured_image' || scene.visual === 'screenshot') return reel.featuredImage;
  return null;
};

/** Fade + lift every scene in, and fade it out over its last frames. */
const SceneFrame: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const frame = useCurrentFrame();
  const { durationInFrames } = useVideoConfig();
  const opacity = interpolate(frame, [0, 6, durationInFrames - 5, durationInFrames], [0, 1, 1, 0], {
    extrapolateLeft: 'clamp', extrapolateRight: 'clamp',
  });

  return <AbsoluteFill style={{ opacity }}>{children}</AbsoluteFill>;
};

export const HookScene: React.FC<SceneProps> = ({ scene, reel }) => {
  const image = imageFor(scene, reel);

  return (
    <SceneFrame>
      <AbsoluteFill style={{ alignItems: 'center', paddingTop: 300, paddingLeft: SAFE_X, paddingRight: SAFE_X }}>
        {scene.visual === 'portrait' || !image ? (
          <Portrait src={reel.portrait} size={400} />
        ) : (
          <ImageCard src={image} />
        )}
        <div style={{ marginTop: 80 }}>
          <Headline text={scene.on_screen_text} size={104} delay={6} />
        </div>
      </AbsoluteFill>
    </SceneFrame>
  );
};

/** Wide images would crop badly full-screen, so blur a copy behind them. */
const BlurredBackdrop: React.FC<{ src: string }> = ({ src }) => (
  <AbsoluteFill>
    <Img src={staticFile(src)} style={{ width: '100%', height: '100%', objectFit: 'cover', filter: 'blur(40px) saturate(1.2)', transform: 'scale(1.2)', opacity: 0.35 }} />
    <AbsoluteFill style={{ background: `linear-gradient(180deg, rgba(13,27,42,0.4) 0%, ${colors.midnight} 85%)` }} />
  </AbsoluteFill>
);

export const ContextScene: React.FC<SceneProps> = ({ scene, reel }) => {
  const image = imageFor(scene, reel);

  return (
    <SceneFrame>
      {image && <BlurredBackdrop src={image} />}
      <AbsoluteFill style={{ alignItems: 'center', paddingTop: 280, paddingLeft: SAFE_X, paddingRight: SAFE_X }}>
        {image && <ImageCard src={image} />}
        <div style={{ marginTop: image ? 70 : 340 }}>
          <Headline text={scene.on_screen_text} size={96} delay={6} />
        </div>
      </AbsoluteFill>
    </SceneFrame>
  );
};

export const PointScene: React.FC<SceneProps> = ({ scene, reel, pointNumber }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const badge = spring({ frame, fps, config: { damping: 12 } });
  const image = imageFor(scene, reel);

  return (
    <SceneFrame>
      <AbsoluteFill style={{ paddingTop: 260, paddingLeft: SAFE_X, paddingRight: SAFE_X }}>
        <div
          style={{
            width: 120, height: 120, borderRadius: 32, border: `5px solid ${colors.teal}`,
            display: 'flex', alignItems: 'center', justifyContent: 'center',
            fontFamily: font, fontWeight: 900, fontSize: 56, color: colors.teal,
            transform: `scale(${badge}) rotate(${(1 - badge) * -20}deg)`,
          }}
        >
          {String(pointNumber).padStart(2, '0')}
        </div>
        <div style={{ marginTop: 50 }}>
          <Headline text={scene.on_screen_text} size={88} align="left" delay={4} />
        </div>
        <div style={{ marginTop: 60 }}>
          {scene.visual === 'code' && scene.visual_note.trim() !== '' ? (
            <CodeCard code={scene.visual_note} delay={10} />
          ) : image ? (
            <ImageCard src={image} delay={10} />
          ) : (
            <div
              style={{
                height: 12, borderRadius: 999, backgroundColor: colors.teal,
                width: `${interpolate(frame, [8, 26], [0, 60], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' })}%`,
              }}
            />
          )}
        </div>
      </AbsoluteFill>
    </SceneFrame>
  );
};

export const EndScene: React.FC<SceneProps> = ({ scene, reel }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const pill = spring({ frame: frame - 14, fps, config: { damping: 12 } });

  return (
    <SceneFrame>
      <AbsoluteFill style={{ alignItems: 'center', paddingTop: 300, paddingLeft: SAFE_X, paddingRight: SAFE_X, textAlign: 'center' }}>
        <Portrait src={reel.portrait} size={340} />
        <div style={{ marginTop: 50, fontFamily: font, fontWeight: 900, fontSize: 76, color: colors.text }}>{reel.brand.name}</div>
        <div style={{ marginTop: 8, fontFamily: font, fontWeight: 500, fontSize: 40, color: colors.muted }}>{reel.brand.role}</div>
        <div
          style={{
            marginTop: 60, padding: '28px 54px', borderRadius: 999, backgroundColor: colors.teal,
            fontFamily: font, fontWeight: 900, fontSize: 50, color: colors.midnight,
            transform: `scale(${pill})`, boxShadow: `0 20px 60px rgba(65,234,212,0.35)`,
          }}
        >
          {scene.on_screen_text || 'Full article: link in bio'}
        </div>
        <div style={{ marginTop: 40, fontFamily: font, fontWeight: 700, fontSize: 40, color: colors.soft }}>{reel.brand.handle}</div>
      </AbsoluteFill>
    </SceneFrame>
  );
};

export const sceneComponent = {
  hook: HookScene,
  context: ContextScene,
  point: PointScene,
  end: EndScene,
};
