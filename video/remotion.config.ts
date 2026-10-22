import { Config } from '@remotion/cli/config';

// H.264 at a quality that keeps a 45 s 1080x1920 reel around 10 MB.
Config.setVideoImageFormat('jpeg');
Config.setCodec('h264');
Config.setCrf(23);
Config.setPixelFormat('yuv420p');
