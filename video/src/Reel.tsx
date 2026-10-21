import { AbsoluteFill, Audio, Sequence, staticFile } from 'remotion';
import { FPS } from './brand';
import { Background } from './components/Background';
import { BrandChip, ProgressBar } from './components/Chrome';
import { Captions } from './components/Captions';
import { sceneComponent } from './scenes/Scenes';
import { loadBrandFonts } from './fonts';
import type { ReelProps } from './types';

loadBrandFonts();

const sceneFrames = (seconds: number) => Math.max(1, Math.round(seconds * FPS));

/** Total length: every scene plus half a second to let the end card breathe. */
export const reelFrames = (props: ReelProps) =>
  props.scenes.reduce((total, s) => total + sceneFrames(s.duration_seconds), 0) + Math.round(FPS / 2);

export const Reel: React.FC<ReelProps> = (props) => {
  let from = 0;
  let points = 0;

  return (
    <AbsoluteFill>
      <Background />
      {props.voice && <Audio src={staticFile(props.voice)} />}

      {props.scenes.map((scene, index) => {
        const frames = sceneFrames(scene.duration_seconds);
        const start = from;
        from += frames;
        if (scene.type === 'point') points += 1;
        const Component = sceneComponent[scene.type] ?? sceneComponent.point;
        const isLast = index === props.scenes.length - 1;

        return (
          <Sequence key={index} from={start} durationInFrames={frames + (isLast ? Math.round(FPS / 2) : 0)} name={`${index + 1}. ${scene.type}`}>
            <Component scene={scene} reel={props} index={index} pointNumber={points} />
            {scene.type !== 'end' && <Captions words={scene.words} />}
          </Sequence>
        );
      })}

      <BrandChip portrait={props.portrait} handle={props.brand.handle} />
      <ProgressBar />
    </AbsoluteFill>
  );
};
