"""
Better battlefield background - smooth silhouettes with Bezier-style curves
"""
import numpy as np
from PIL import Image, ImageFilter, ImageDraw
import math, random

W, H = 1920, 1080
rng = random.Random(99)

# === SKY: deep dark gradient ===
img = np.zeros((H, W, 3), dtype=np.float32)
for y in range(H):
    t = y / H
    img[y, :, 0] = 0.03 + t * 0.20
    img[y, :, 1] = 0.005 + t * 0.018
    img[y, :, 2] = 0.05 + t * 0.03

# === HORIZON GLOW ===
hy = int(H * 0.56)
for y in range(H):
    d = abs(y - hy) / (H * 0.20)
    g = max(0.0, 1.0 - d**2) * 0.32
    img[y, :, 0] += g * 0.95
    img[y, :, 1] += g * 0.10
    img[y, :, 2] += g * 0.02

img = np.clip(img * 255, 0, 255).astype(np.uint8)
pil = Image.fromarray(img)
draw = ImageDraw.Draw(pil)

SIL = (4, 1, 2)
horizon_y = int(H * 0.57)

# === GOTHIC CATHEDRAL - LEFT ===
def gothic_arch(draw, cx, base_y, w, h, col):
    # pointed arch
    pts = [(cx - w//2, base_y)]
    steps = 40
    for i in range(steps + 1):
        a = math.pi * i / steps
        x = cx - w//2 * math.cos(a)
        y = base_y - h * math.sin(a) * 0.7 - h * 0.3
        pts.append((int(x), int(y)))
    pts.append((cx + w//2, base_y))
    draw.polygon(pts, fill=col)

# Main cathedral body
draw.rectangle([60, horizon_y - 320, 280, horizon_y], fill=SIL)
# Main spire
draw.polygon([(155, horizon_y-320), (170, horizon_y-500), (185, horizon_y-320)], fill=SIL)
# Side spires
draw.polygon([(90, horizon_y-280), (100, horizon_y-380), (110, horizon_y-280)], fill=SIL)
draw.polygon([(235, horizon_y-260), (245, horizon_y-360), (255, horizon_y-260)], fill=SIL)
# Arched windows cutouts (lighter)
for wy in [horizon_y-260, horizon_y-180, horizon_y-100]:
    # pointy arch window
    draw.ellipse([148, wy-30, 190, wy], fill=(18, 5, 6))
    draw.rectangle([148, wy, 190, wy+20], fill=(18, 5, 6))
# Flying buttresses
draw.polygon([(60, horizon_y), (60, horizon_y-120), (30, horizon_y-80), (30, horizon_y)], fill=SIL)
draw.polygon([(280, horizon_y), (280, horizon_y-100), (310, horizon_y-60), (310, horizon_y)], fill=SIL)

# === RUINED WALL - CENTER ===
# Jagged broken wall
wall = [(480, horizon_y)]
x = 480
while x < 1450:
    hv = rng.randint(30, 160)
    wall.append((x, horizon_y - hv))
    x += rng.randint(12, 40)
    wall.append((x, horizon_y - rng.randint(20, hv + 20)))
    x += rng.randint(5, 20)
wall.append((x, horizon_y))
if len(wall) >= 3:
    draw.polygon(wall, fill=SIL)

# Tall broken tower CENTER
draw.rectangle([910, horizon_y-340, 970, horizon_y], fill=SIL)
# jagged top
draw.polygon([(910, horizon_y-340), (925, horizon_y-420), (940, horizon_y-395),
              (950, horizon_y-440), (960, horizon_y-410), (970, horizon_y-340)], fill=SIL)
# window
draw.ellipse([930, horizon_y-300, 950, horizon_y-260], fill=(18, 5, 6))

# === DEAD TREES - RIGHT ===
def dead_tree(draw, tx, ty, h, w=4):
    draw.line([(tx, ty), (tx, ty-h)], fill=SIL, width=w)
    for i in range(5):
        branch_y = ty - int(h * (0.25 + i*0.15))
        blen = int(h * (0.28 - i*0.04))
        for side in [-1, 1]:
            angle = side * (40 + rng.randint(-10, 10))
            bx = tx + int(blen * math.sin(math.radians(angle)))
            by = branch_y - int(blen * math.cos(math.radians(abs(angle))))
            draw.line([(tx, branch_y), (bx, by)], fill=SIL, width=max(1, w-i))
            if i < 3:
                sb = blen // 2
                sa = angle + rng.choice([-1,1]) * 30
                draw.line([(bx, by),
                           (bx + int(sb*math.sin(math.radians(sa))),
                            by - int(sb*0.6))], fill=SIL, width=1)

for tx, h, w in [(1380,300,5),(1460,250,4),(1560,330,5),(1650,210,3),(1740,280,5),(1840,310,4),(1900,230,3)]:
    dead_tree(draw, tx, horizon_y, h, w)

# === WARRIOR SILHOUETTES ===
for wx in [560, 620, 665, 710, 760, 810, 855, 1040, 1090, 1140, 1200, 1270, 1310]:
    wh = rng.randint(38, 58)
    draw.rectangle([wx-4, horizon_y-wh, wx+4, horizon_y], fill=(3,1,2))
    draw.ellipse([wx-5, horizon_y-wh-11, wx+5, horizon_y-wh+1], fill=(3,1,2))
    if rng.random() > 0.35:
        draw.line([(wx+2, horizon_y-wh-6), (wx+2, horizon_y-wh-48)], fill=(3,1,2), width=2)

# === EMBERS ===
for _ in range(300):
    ex = rng.randint(200, 1700)
    ey = rng.randint(int(H*0.30), int(H*0.72))
    r2 = rng.randint(1, 4)
    rc = (rng.randint(190,255), rng.randint(50,110), 0)
    draw.ellipse([ex-r2, ey-r2, ex+r2, ey+r2], fill=rc)

# === BLUR for atmosphere ===
pil = pil.filter(ImageFilter.GaussianBlur(radius=1.5))

# === VIGNETTE ===
vign = Image.new('RGB', (W, H), (0,0,0))
vd = ImageDraw.Draw(vign)
steps = 120
for i in range(steps):
    t = i / steps
    alpha = int((1 - t) * 180)
    vd.rectangle([i*5, i*3, W-i*5, H-i*3], outline=(0,0,0))
from PIL import ImageChops
pil = Image.blend(pil, Image.new('RGB', (W,H), (0,0,0)), alpha=0.0)
# simple vignette via numpy
arr = np.array(pil).astype(np.float32)
cx, cy = W/2, H/2
xx, yy = np.meshgrid(np.linspace(-1,1,W), np.linspace(-1,1,H))
v = np.clip(1.0 - (xx**2 + yy**2) * 0.5, 0.3, 1.0)
arr = arr * v[:,:,np.newaxis]
pil = Image.fromarray(np.clip(arr, 0, 255).astype(np.uint8))

out = '/home/michael/projects/thefinalchapter/assets/img/bg-battlefield2.jpg'
pil.save(out, quality=90)
print(f"Saved: {out} ({pil.size})")
