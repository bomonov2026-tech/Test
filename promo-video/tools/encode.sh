#!/usr/bin/env bash
# Encodes frames + soundtrack into the final 9:16 MP4 (1080x1920, 30 fps, 40 s).
set -euo pipefail
cd "$(dirname "$0")/.."
mkdir -p out
ffmpeg -y -hide_banner -loglevel error \
  -framerate 30 -i build/frames/f%05d.jpg \
  -i build/mix.wav \
  -vf "eq=contrast=1.05:saturation=1.08:gamma=1.02,colorbalance=rs=0.05:gs=0.02:bs=-0.06:rm=0.04:bm=-0.02,vignette=angle=PI/4.5,noise=alls=5:allf=t" \
  -c:v libx264 -preset slow -crf 17 -pix_fmt yuv420p -profile:v high -level 4.2 -r 30 \
  -c:a aac -b:a 192k -ar 48000 \
  -t 40 -movflags +faststart \
  out/polathon_promo_1080x1920.mp4
ffprobe -v error -show_entries stream=codec_name,width,height,r_frame_rate,duration -of default=nw=1 out/polathon_promo_1080x1920.mp4
