import { Composition, Still } from 'remotion';
import { FPS, HEIGHT, WIDTH } from './brand';
import { Reel, reelFrames } from './Reel';
import { Cover } from './Cover';
import type { ReelProps } from './types';

const sample: ReelProps = {
  title: 'Why I moved my queue off Redis',
  voice: null,
  featuredImage: null,
  portrait: null,
  brand: { name: 'Adil Sher', role: 'Full Stack Developer', handle: 'adilsher.pro' },
  scenes: [
    { type: 'hook', on_screen_text: 'Redis lost 3,000 jobs', voice_line: 'Redis lost three thousand of my jobs in one night.', visual: 'portrait', visual_note: '', duration_seconds: 3.5, words: [] },
    { type: 'context', on_screen_text: 'The queue looked healthy', voice_line: 'Every dashboard was green while jobs vanished.', visual: 'featured_image', visual_note: '', duration_seconds: 3.5, words: [] },
    { type: 'point', on_screen_text: 'Eviction ate the queue', voice_line: 'The eviction policy deleted queued jobs to free memory.', visual: 'code', visual_note: 'maxmemory-policy allkeys-lru', duration_seconds: 4.5, words: [] },
    { type: 'end', on_screen_text: 'Full story: link in bio', voice_line: 'The full breakdown is in the link in my bio.', visual: 'portrait', visual_note: '', duration_seconds: 3.5, words: [] },
  ],
};

export const RemotionRoot: React.FC = () => (
  <>
    <Composition
      id="Reel"
      component={Reel}
      width={WIDTH}
      height={HEIGHT}
      fps={FPS}
      durationInFrames={reelFrames(sample)}
      defaultProps={sample}
      calculateMetadata={({ props }) => ({ durationInFrames: reelFrames(props) })}
    />
    <Still id="Cover" component={Cover} width={WIDTH} height={HEIGHT} defaultProps={sample} />
  </>
);
