import { AbsoluteFill, interpolate, useCurrentFrame } from 'remotion';
import { colors } from '../brand';

/** Midnight base with a slowly drifting teal glow and a faint grid. */
export const Background: React.FC = () => {
  const frame = useCurrentFrame();
  const x = 50 + Math.sin(frame / 90) * 18;
  const y = 30 + Math.cos(frame / 110) * 10;

  return (
    <AbsoluteFill style={{ backgroundColor: colors.midnight }}>
      <AbsoluteFill
        style={{
          background: `radial-gradient(circle at ${x}% ${y}%, rgba(65,234,212,0.18) 0%, rgba(65,234,212,0) 55%),
                       radial-gradient(circle at ${100 - x}% ${80 - y / 3}%, rgba(255,107,53,0.10) 0%, rgba(255,107,53,0) 45%)`,
        }}
      />
      <AbsoluteFill
        style={{
          opacity: interpolate(frame, [0, 30], [0, 0.35], { extrapolateRight: 'clamp' }),
          backgroundImage:
            'linear-gradient(rgba(195,208,216,0.06) 1px, transparent 1px), linear-gradient(90deg, rgba(195,208,216,0.06) 1px, transparent 1px)',
          backgroundSize: '90px 90px',
        }}
      />
    </AbsoluteFill>
  );
};
