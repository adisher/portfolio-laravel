import { AbsoluteFill } from 'remotion';
import { colors, font, SAFE_X } from './brand';
import { Background } from './components/Background';
import { BrandChip } from './components/Chrome';
import { Portrait } from './components/Media';
import { loadBrandFonts } from './fonts';
import type { ReelProps } from './types';

loadBrandFonts();

/**
 * Thumbnail for Instagram's grid and YouTube: the hook in big type with the
 * portrait. Static, so it reads at a glance.
 */
export const Cover: React.FC<ReelProps> = (props) => {
  const hook = props.scenes.find((s) => s.type === 'hook')?.on_screen_text || props.title;
  const words = hook.split(/\s+/).filter(Boolean);

  return (
    <AbsoluteFill>
      <Background />
      <AbsoluteFill style={{ alignItems: 'center', justifyContent: 'center', paddingLeft: SAFE_X, paddingRight: SAFE_X, textAlign: 'center' }}>
        <Portrait src={props.portrait} size={380} delay={-30} />
        <div
          style={{
            marginTop: 70, fontFamily: font, fontWeight: 900, fontSize: 120, lineHeight: 1.02, letterSpacing: -2,
            display: 'flex', flexWrap: 'wrap', justifyContent: 'center', gap: '0 28px',
          }}
        >
          {words.map((w, i) => (
            <span key={i} style={{ color: /\d/.test(w) ? colors.teal : colors.text }}>{w}</span>
          ))}
        </div>
        <div style={{ marginTop: 50, height: 12, width: 220, borderRadius: 999, backgroundColor: colors.teal }} />
      </AbsoluteFill>
      <BrandChip portrait={props.portrait} handle={props.brand.handle} />
    </AbsoluteFill>
  );
};
