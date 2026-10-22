import type { Word } from '../types';

/**
 * Which word is being said at time t (seconds from the scene start).
 *
 * Between two words (Whisper's timings leave small gaps) the word that was
 * just said stays current. Returns -1 before the first word starts.
 *
 * The previous version fell back to the scene's LAST word whenever t sat in a
 * gap, so captions jumped to the final line and back several times a sentence.
 */
export const currentWordIndex = (words: Word[], t: number): number => {
  let current = -1;
  for (let i = 0; i < words.length; i++) {
    if (words[i].start <= t) current = i;
    else break;
  }
  return current;
};
