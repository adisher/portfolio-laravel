import { AbsoluteFill, Img, staticFile, useCurrentFrame, useVideoConfig } from 'remotion';
import { colors, font, SAFE_X } from '../brand';

/** Thin teal progress bar along the top edge. */
export const ProgressBar: React.FC = () => {
  const frame = useCurrentFrame();
  const { durationInFrames } = useVideoConfig();

  return (
    <AbsoluteFill style={{ pointerEvents: 'none' }}>
      <div style={{ position: 'absolute', top: 0, left: 0, right: 0, height: 10, backgroundColor: 'rgba(255,255,255,0.08)' }} />
      <div
        style={{
          position: 'absolute', top: 0, left: 0, height: 10,
          width: `${(frame / Math.max(1, durationInFrames - 1)) * 100}%`,
          backgroundColor: colors.teal,
          boxShadow: `0 0 24px ${colors.teal}`,
        }}
      />
    </AbsoluteFill>
  );
};

/** Small avatar + domain in the top-left, on every frame. */
export const BrandChip: React.FC<{ portrait: string | null; handle: string }> = ({ portrait, handle }) => (
  <div
    style={{
      position: 'absolute', top: 70, left: SAFE_X,
      display: 'flex', alignItems: 'center', gap: 18,
      padding: '12px 26px 12px 12px', borderRadius: 999,
      backgroundColor: 'rgba(6,16,26,0.72)', border: '2px solid rgba(65,234,212,0.35)',
      fontFamily: font, fontSize: 32, fontWeight: 700, color: colors.text,
    }}
  >
    {portrait ? (
      <Img src={staticFile(portrait)} style={{ width: 56, height: 56, borderRadius: 999, objectFit: 'cover' }} />
    ) : (
      <div style={{ width: 56, height: 56, borderRadius: 999, backgroundColor: colors.teal }} />
    )}
    {handle}
  </div>
);
