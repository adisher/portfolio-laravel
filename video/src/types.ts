export type Visual = 'featured_image' | 'portrait' | 'screenshot' | 'text' | 'code';
export type SceneType = 'hook' | 'context' | 'point' | 'end';

/** A spoken word, timed in seconds from the start of its scene. */
export type Word = { text: string; start: number; end: number };

export type Scene = {
  type: SceneType;
  on_screen_text: string;
  voice_line: string;
  visual: Visual;
  visual_note: string;
  duration_seconds: number;
  words: Word[];
};

export type ReelProps = {
  title: string;
  scenes: Scene[];
  /** Paths relative to video/public (passed to staticFile), or null. */
  voice: string | null;
  featuredImage: string | null;
  portrait: string | null;
  brand: { name: string; role: string; handle: string };
};
