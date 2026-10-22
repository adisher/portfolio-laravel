import { spring, useCurrentFrame, useVideoConfig } from 'remotion';
import { colors, font } from '../brand';

/**
 * The big on-screen line, revealed word by word. Words that contain a digit
 * are drawn in the accent colour so numbers stand out.
 */
export const Headline: React.FC<{ text: string; size?: number; delay?: number; align?: 'center' | 'left' }> = ({
  text, size = 92, delay = 0, align = 'center',
}) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const words = text.split(/\s+/).filter(Boolean);

  return (
    <div
      style={{
        fontFamily: font, fontWeight: 900, fontSize: size, lineHeight: 1.05,
        letterSpacing: -1.5, color: colors.text, textAlign: align,
        display: 'flex', flexWrap: 'wrap', gap: `0 ${size * 0.25}px`,
        justifyContent: align === 'center' ? 'center' : 'flex-start',
      }}
    >
      {words.map((w, i) => {
        const p = spring({ frame: frame - delay - i * 3, fps, config: { damping: 14, stiffness: 160 } });
        return (
          <span
            key={i}
            style={{
              display: 'inline-block',
              opacity: p,
              transform: `translateY(${(1 - p) * 40}px) scale(${0.9 + p * 0.1})`,
              color: /\d/.test(w) ? colors.teal : colors.text,
            }}
          >
            {w}
          </span>
        );
      })}
    </div>
  );
};
