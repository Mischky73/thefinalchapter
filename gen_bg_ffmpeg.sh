#!/bin/bash
# Minimal reliable ffmpeg battlefield background generator
# Generates: dark stormy sky, horizon ember glow, moon glow, dark ground, vignette
# Then overlays black rectangles/shapes for silhouettes

OUT="/home/michael/projects/thefinalchapter/assets/img/bg-battlefield2.jpg"
W=1920; H=1080
TMP="/tmp/bg_base.png"

echo "Generating dark battlefield background..."

# Step 1: Base atmospheric image using geq filter
# All in one expression - keep math simple and reliable
ffmpeg -y \
  -f lavfi -i "color=c=black:s=${W}x${H}:r=1" \
  -vf "
geq=r='
  clip(
    (
      lerp(6, 20, max(0, min(1, 1-(Y/H)/0.43))) +
      exp(-pow(Y/H-0.40, 2) * 350) * 75 +
      exp(-pow(Y/H-0.40, 2) * 70) * 25 +
      exp(-(pow(X/W-0.78, 2) + pow((Y/H-0.12)*1.6, 2)) * 75) * 22 +
      if(lt(Y/H, 0.55),
        max(0, (sin(X/W*20+sin(Y/H*13+1))*0.5+0.5)*0.6 + (sin(X/W*33+sin(Y/H*22+0.7))*0.5+0.5)*0.4 - 0.42) *
        max(0, 1-(Y/H)/0.55) * 25,
        0
      ) +
      if(gt(Y/H, 0.38),
        max(0, min(1, (Y/H-0.38)/0.08)) * (
          (sin(X/W*48+Y/H*32+0.5)*0.5+0.5)*16 +
          (sin(X/W*85+Y/H*55+1.3)*0.5+0.5)*6 + 6
        ),
        0
      )
    ) *
    max(0.55, min(1, 1-(pow((X/W-0.5)*2, 2)*0.18+pow((Y/H-0.5)*2, 2)*0.14))) *
    max(0, min(1, 1-max(0, min(1, 1-Y/H*4))*0.3)) *
    0.88
  , 0, 255)
':g='
  clip(
    (
      lerp(3, 9, max(0, min(1, 1-(Y/H)/0.43))) +
      exp(-pow(Y/H-0.40, 2) * 350) * 11 +
      exp(-pow(Y/H-0.40, 2) * 70) * 7 +
      exp(-(pow(X/W-0.78, 2) + pow((Y/H-0.12)*1.6, 2)) * 75) * 26 +
      if(lt(Y/H, 0.55),
        max(0, (sin(X/W*20+sin(Y/H*13+1))*0.5+0.5)*0.6 + (sin(X/W*33+sin(Y/H*22+0.7))*0.5+0.5)*0.4 - 0.42) *
        max(0, 1-(Y/H)/0.55) * 16,
        0
      ) +
      if(gt(Y/H, 0.38),
        max(0, min(1, (Y/H-0.38)/0.08)) * (
          (sin(X/W*48+Y/H*32+0.5)*0.5+0.5)*9 +
          (sin(X/W*85+Y/H*55+1.3)*0.5+0.5)*3 + 4
        ),
        0
      )
    ) *
    max(0.55, min(1, 1-(pow((X/W-0.5)*2, 2)*0.18+pow((Y/H-0.5)*2, 2)*0.14))) *
    max(0, min(1, 1-max(0, min(1, 1-Y/H*4))*0.3)) *
    0.88
  , 0, 255)
':b='
  clip(
    (
      lerp(5, 16, max(0, min(1, 1-(Y/H)/0.43))) +
      exp(-pow(Y/H-0.40, 2) * 350) * 4 +
      exp(-(pow(X/W-0.78, 2) + pow((Y/H-0.12)*1.6, 2)) * 75) * 32 +
      if(lt(Y/H, 0.55),
        max(0, (sin(X/W*20+sin(Y/H*13+1))*0.5+0.5)*0.6 + (sin(X/W*33+sin(Y/H*22+0.7))*0.5+0.5)*0.4 - 0.42) *
        max(0, 1-(Y/H)/0.55) * 24,
        0
      ) +
      if(gt(Y/H, 0.38),
        max(0, min(1, (Y/H-0.38)/0.08)) * (
          (sin(X/W*48+Y/H*32+0.5)*0.5+0.5)*6 +
          (sin(X/W*85+Y/H*55+1.3)*0.5+0.5)*2 + 3
        ),
        0
      )
    ) *
    max(0.55, min(1, 1-(pow((X/W-0.5)*2, 2)*0.18+pow((Y/H-0.5)*2, 2)*0.14))) *
    max(0, min(1, 1-max(0, min(1, 1-Y/H*4))*0.3)) *
    0.88
  , 0, 255)
'
  " \
  -frames:v 1 "$TMP" 2>&1

echo "geq exit code: $?"

if [ ! -f "$TMP" ]; then
    echo "geq failed, trying simplified gradient..."
    # Ultra-simple fallback: just gradient + some sine noise
    ffmpeg -y -f lavfi -i "color=c=black:s=${W}x${H}:r=1" \
      -vf "geq=r='clip(lerp(6,20,max(0,1-Y/H/0.43))+exp(-pow(Y/H-0.40,2)*300)*70+exp(-(pow(X/W-0.78,2)+pow((Y/H-0.12)*1.5,2))*70)*22,0,255)':g='clip(lerp(3,9,max(0,1-Y/H/0.43))+exp(-pow(Y/H-0.40,2)*300)*10+exp(-(pow(X/W-0.78,2)+pow((Y/H-0.12)*1.5,2))*70)*25,0,255)':b='clip(lerp(5,16,max(0,1-Y/H/0.43))+exp(-(pow(X/W-0.78,2)+pow((Y/H-0.12)*1.5,2))*70)*30,0,255)'" \
      -frames:v 1 "$TMP" 2>&1 | tail -5
fi

if [ ! -f "$TMP" ]; then
    echo "ERROR: Could not generate base image"
    exit 1
fi

echo "Step 2: Adding silhouettes with drawbox..."
# Add dark gothic silhouettes using drawbox/drawtext
# Cathedral ruin wall (center-left), tower (far left), ruin wall (right), dead trees
ffmpeg -y -i "$TMP" \
  -vf "
    drawbox=x=$(echo "$W*0.18/1" | bc):y=$(echo "$H*0.22/1" | bc):w=$(echo "$W*0.20/1" | bc):h=$(echo "$H*0.30/1" | bc):color=black@0.95:t=fill,
    drawbox=x=$(echo "$W*0.04/1" | bc):y=$(echo "$H*0.18/1" | bc):w=$(echo "$W*0.05/1" | bc):h=$(echo "$H*0.34/1" | bc):color=black@0.95:t=fill,
    drawbox=x=$(echo "$W*0.62/1" | bc):y=$(echo "$H*0.30/1" | bc):w=$(echo "$W*0.10/1" | bc):h=$(echo "$H*0.22/1" | bc):color=black@0.95:t=fill,
    drawbox=x=$(echo "$W*0.74/1" | bc):y=$(echo "$H*0.26/1" | bc):w=$(echo "$W*0.08/1" | bc):h=$(echo "$H*0.26/1" | bc):color=black@0.95:t=fill,
    drawbox=x=$(echo "$W*0.234/1" | bc):y=$(echo "$H*0.08/1" | bc):w=16:h=$(echo "$H*0.16/1" | bc):color=black@0.95:t=fill,
    drawbox=x=$(echo "$W*0.285/1" | bc):y=$(echo "$H*0.14/1" | bc):w=14:h=$(echo "$H*0.10/1" | bc):color=black@0.95:t=fill,
    drawbox=x=460:y=238:w=2:h=$(echo "$H*0.20/1" | bc):color=black@0.9:t=fill,
    drawbox=x=998:y=297:w=2:h=160:color=black@0.9:t=fill,
    drawbox=x=1003:y=297:w=20:h=2:color=black@0.9:t=fill,
    drawbox=x=1058:y=302:w=2:h=160:color=black@0.9:t=fill,
    drawbox=x=1634:y=302:w=2:h=160:color=black@0.9:t=fill,
    drawbox=x=1639:y=302:w=20:h=2:color=black@0.9:t=fill,
    drawbox=x=1694:y=308:w=2:h=155:color=black@0.9:t=fill,
    drawbox=x=$(echo "$W*0.42/1" | bc):y=$(echo "$H*0.44/1" | bc):w=$(echo "$W*0.21/1" | bc):h=$(echo "$H*0.09/1" | bc):color=black@0.85:t=fill
  " \
  -frames:v 1 -q:v 2 "$OUT" 2>&1 | tail -3

rm -f "$TMP"

if [ -f "$OUT" ]; then
    SIZE=$(stat -c%s "$OUT")
    echo "SUCCESS: $OUT"
    echo "Size: ${SIZE} bytes ($(echo "scale=0; $SIZE/1024" | bc) KB)"
else
    echo "FAILED to create output"
    exit 1
fi
