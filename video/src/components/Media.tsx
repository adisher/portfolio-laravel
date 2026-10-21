import { Img, interpolate, spring, staticFile, useCurrentFrame, useVideoConfig } from 'remotion';
import { colors, mono } from '../brand';

export const Portrait: React.FC<{ src: string | null; size: number; delay?: number }> = ({ src, size, delay = 0 }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const p = spring({ frame: frame - delay, fps, config: { damping: 12, stiffness: 120 } });

  return (
    <div
      style={{
        width: size, height: size, borderRadius: 999, padding: 8,
        background: `conic-gradient(from ${frame * 2}deg, ${colors.teal}, ${colors.oceanLight}, ${colors.sunset}, ${colors.teal})`,
        transform: `scale(${p})`, boxShadow: '0 30px 80px rgba(0,0,0,0.5)',
      }}
    >
      {src ? (
        <Img src={staticFile(src)} style={{ width: '100%', height: '100%', borderRadius: 999, objectFit: 'cover', border: `6px solid ${colors.midnight}` }} />
      ) : (
        <div style={{ width: '100%', height: '100%', borderRadius: 999, backgroundColor: colors.ocean }} />
      )}
    </div>
  );
};

/**
 * A framed image that slowly zooms (Ken Burns), for article/product visuals.
 * 16:9 by default, the shape of most article and product images, so little
 * gets cropped.
 */
export const ImageCard: React.FC<{ src: string; height?: number; delay?: number }> = ({ src, height, delay = 0 }) => {
  const frame = useCurrentFrame();
  const { fps, durationInFrames } = useVideoConfig();
  const p = spring({ frame: frame - delay, fps, config: { damping: 16 } });
  const zoom = interpolate(frame, [0, durationInFrames], [1.02, 1.12]);

  return (
    <div
      style={{
        width: '100%', height, aspectRatio: height ? undefined : '16 / 9', borderRadius: 36, overflow: 'hidden',
        border: `3px solid rgba(65,234,212,0.35)`, boxShadow: '0 40px 90px rgba(0,0,0,0.55)',
        opacity: p, transform: `translateY(${(1 - p) * 60}px)`,
      }}
    >
      <Img src={staticFile(src)} style={{ width: '100%', height: '100%', objectFit: 'cover', transform: `scale(${zoom})` }} />
    </div>
  );
};

/** Editor-style card for a short code snippet. */
export const CodeCard: React.FC<{ code: string; delay?: number }> = ({ code, delay = 0 }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const p = spring({ frame: frame - delay, fps, config: { damping: 16 } });
  const lines = code.split('\n').slice(0, 8);

  return (
    <div
      style={{
        width: '100%', borderRadius: 32, overflow: 'hidden', backgroundColor: colors.midnightDark,
        border: `3px solid rgba(65,234,212,0.35)`, boxShadow: '0 40px 90px rgba(0,0,0,0.55)',
        opacity: p, transform: `translateY(${(1 - p) * 60}px)`,
      }}
    >
      <div style={{ display: 'flex', gap: 14, padding: '22px 28px', backgroundColor: colors.ocean }}>
        {[colors.sunset, '#F5C451', colors.teal].map((c) => (
          <div key={c} style={{ width: 22, height: 22, borderRadius: 999, backgroundColor: c }} />
        ))}
      </div>
      <div style={{ padding: '30px 36px 38px', fontFamily: mono, fontSize: 34, lineHeight: 1.45, color: colors.soft, whiteSpace: 'pre-wrap', wordBreak: 'break-word' }}>
        {lines.map((line, i) => {
          const shown = interpolate(frame - delay - 6 - i * 4, [0, 8], [0, 1], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' });
          const comment = /^\s*(\/\/|#|--)/.test(line);
          return (
            <div key={i} style={{ opacity: shown, color: comment ? colors.muted : colors.soft }}>
              <span style={{ color: colors.oceanLight, marginRight: 24 }}>{String(i + 1).padStart(2, ' ')}</span>
              {line || ' '}
            </div>
          );
        })}
      </div>
    </div>
  );
};
