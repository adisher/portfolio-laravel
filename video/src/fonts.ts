import { continueRender, delayRender, staticFile } from 'remotion';

// Satoshi ships with the site (public/fonts/satoshi); the render job copies it
// into video/public/fonts. Hold the first frame until the faces are loaded.
const weights: Array<[string, string]> = [
  ['Satoshi-Regular.woff2', '400'],
  ['Satoshi-Medium.woff2', '500'],
  ['Satoshi-Bold.woff2', '700'],
  ['Satoshi-Black.woff2', '900'],
];

let started = false;

export const loadBrandFonts = () => {
  if (started || typeof document === 'undefined') return;
  started = true;

  const handle = delayRender('Loading Satoshi');
  Promise.all(
    weights.map(([file, weight]) => {
      const face = new FontFace('Satoshi', `url(${staticFile(`fonts/satoshi/${file}`)}) format('woff2')`, { weight });
      (document.fonts as unknown as { add: (f: FontFace) => void }).add(face);
      return face.load();
    }),
  )
    .catch(() => undefined) // fall back to the system sans rather than fail the render
    .finally(() => continueRender(handle));
};
