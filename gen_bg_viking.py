"""
Viking battlefield background - dark atmospheric scene
"""
import numpy as np
from PIL import Image, ImageFilter, ImageDraw
import math, random

W, H = 1920, 1080
rng = random.Random(77)

# === SKY: stormy dark blue-grey ===
img = np.zeros((H, W, 3), dtype=np.float32)
for y in range(H):
    t = y / H
    # Dark stormy sky: deep blue-grey at top, dark orange-red at horizon
    img[y, :, 0] = 0.02 + t * 0.22
    img[y, :, 1] = 0.02 + t * 0.06
    img[y, :, 2] = 0.04 + t * 0.04

# Storm clouds - dark patches
for _ in range(12):
    cx = rng.randint(0, W)
    cy = rng.randint(0, int(H*0.45))
    cw = rng.randint(200, 500)
    ch = rng.randint(60, 140)
    for dy in range(-ch, ch):
        for dx in range(-cw, cw):
            if 0 <= cy+dy < H and 0 <= cx+dx < W:
                dist = (dx/cw)**2 + (dy/ch)**2
                if dist < 1.0:
                    fade = (1 - dist) * 0.06
                    img[cy+dy, cx+dx, :] -= fade

# Horizon fire glow - battle fires burning
hy = int(H * 0.58)
for y in range(H):
    d = abs(y - hy) / (H * 0.15)
    g = max(0.0, 1.0 - d**1.8) * 0.45
    img[y, :, 0] += g * 1.0
    img[y, :, 1] += g * 0.18
    img[y, :, 2] += g * 0.0

# Multiple fire spots along horizon
for fx in [300, 500, 650, 800, 1050, 1200, 1400, 1600]:
    fw = rng.randint(40, 100)
    for y in range(H):
        for x in range(max(0,fx-fw), min(W,fx+fw)):
            dx = abs(x - fx) / fw
            dy = abs(y - hy) / (H * 0.10)
            g = max(0.0, 1.0 - (dx**2 + dy**2)) * 0.35
            if 0 <= y < H and 0 <= x < W:
                img[y, x, 0] += g * 0.9
                img[y, x, 1] += g * 0.3

img = np.clip(img * 255, 0, 255).astype(np.uint8)
pil = Image.fromarray(img)
draw = ImageDraw.Draw(pil)

SIL = (3, 1, 2)
horizon_y = int(H * 0.58)

# === GROUND - dark muddy battlefield ===
draw.rectangle([0, horizon_y, W, H], fill=(8, 3, 2))

# Mud/terrain variations
for i in range(80):
    gx = rng.randint(0, W)
    gw = rng.randint(30, 200)
    gh = rng.randint(2, 15)
    gy = rng.randint(horizon_y, H)
    shade = rng.randint(5, 14)
    draw.ellipse([gx-gw, gy-gh, gx+gw, gy+gh], fill=(shade, shade//2, shade//3))

# === LONGSHIPS silhouettes on LEFT ===
# Viking longship 1
ship1_x, ship1_y = 80, horizon_y - 20
draw.polygon([
    (ship1_x, ship1_y),
    (ship1_x + 20, ship1_y - 30),
    (ship1_x + 180, ship1_y - 35),
    (ship1_x + 220, ship1_y - 20),
    (ship1_x + 240, ship1_y),
], fill=SIL)
# keel
draw.polygon([
    (ship1_x + 10, ship1_y),
    (ship1_x + 5, ship1_y + 25),
    (ship1_x + 235, ship1_y + 25),
    (ship1_x + 230, ship1_y),
], fill=SIL)
# mast
draw.line([(ship1_x + 120, ship1_y - 35), (ship1_x + 120, ship1_y - 180)], fill=SIL, width=5)
# sail (torn)
draw.polygon([
    (ship1_x + 120, ship1_y - 175),
    (ship1_x + 120, ship1_y - 60),
    (ship1_x + 185, ship1_y - 80),
    (ship1_x + 190, ship1_y - 155),
], fill=(20, 8, 5))
# dragon head
draw.polygon([
    (ship1_x + 220, ship1_y - 20),
    (ship1_x + 255, ship1_y - 50),
    (ship1_x + 265, ship1_y - 40),
    (ship1_x + 250, ship1_y - 25),
    (ship1_x + 260, ship1_y - 15),
    (ship1_x + 240, ship1_y - 10),
], fill=SIL)
# oars
for ox in range(ship1_x + 30, ship1_x + 210, 22):
    draw.line([(ox, ship1_y + 10), (ox - 15, ship1_y + 45)], fill=SIL, width=3)

# Longship 2 (partially behind, smaller)
ship2_x = 270
draw.polygon([
    (ship2_x, horizon_y - 5),
    (ship2_x + 15, horizon_y - 22),
    (ship2_x + 140, horizon_y - 26),
    (ship2_x + 170, horizon_y - 12),
    (ship2_x + 185, horizon_y),
], fill=(8, 3, 4))
draw.line([(ship2_x + 90, horizon_y - 26), (ship2_x + 90, horizon_y - 140)], fill=SIL, width=4)

# === WARRIORS - CENTER AND RIGHT ===
def viking_warrior(draw, wx, wy, scale=1.0, facing=1):
    h = int(70 * scale)
    bw = int(12 * scale)
    col = (4, 1, 2)
    # body
    draw.rectangle([wx - bw//2, wy - h, wx + bw//2, wy], fill=col)
    # head with helmet
    draw.ellipse([wx - int(7*scale), wy - h - int(13*scale),
                  wx + int(7*scale), wy - h + int(3*scale)], fill=col)
    # horned helmet
    horn_dir = facing
    draw.polygon([
        (wx - int(8*scale), wy - h - int(10*scale)),
        (wx - int(15*scale) * horn_dir, wy - h - int(25*scale)),
        (wx - int(10*scale) * horn_dir, wy - h - int(5*scale)),
    ], fill=col)
    draw.polygon([
        (wx + int(8*scale), wy - h - int(10*scale)),
        (wx + int(15*scale) * horn_dir, wy - h - int(25*scale)),
        (wx + int(10*scale) * horn_dir, wy - h - int(5*scale)),
    ], fill=col)
    # shield
    sx = wx - int(16*scale) * facing
    draw.ellipse([sx - int(10*scale), wy - h + int(5*scale),
                  sx + int(10*scale), wy - int(20*scale)], fill=col)
    # spear/axe
    spx = wx + int(16*scale) * facing
    draw.line([(spx, wy), (spx, wy - h - int(30*scale))], fill=col, width=int(3*scale))
    # blade
    draw.polygon([
        (spx, wy - h - int(25*scale)),
        (spx + int(12*scale)*facing, wy - h - int(15*scale)),
        (spx, wy - h - int(5*scale)),
    ], fill=col)

# Battle lines - warriors clashing
positions_left = [(480+i*38, horizon_y, 0.9+rng.uniform(-0.1,0.1), 1)  for i in range(14)]
positions_right = [(1300+i*38, horizon_y, 0.9+rng.uniform(-0.1,0.1), -1) for i in range(14)]

for wx, wy, sc, facing in positions_left + positions_right:
    viking_warrior(draw, int(wx), wy, sc, facing)

# Fallen warriors
for _ in range(12):
    fx = rng.randint(500, 1300)
    fy = horizon_y + rng.randint(10, 60)
    fw = rng.randint(35, 60)
    draw.ellipse([fx - fw, fy - 8, fx + fw, fy + 8], fill=SIL)
    # helmet
    draw.ellipse([fx + fw - 10, fy - 14, fx + fw + 10, fy - 4], fill=SIL)

# === RAVENS in sky ===
for _ in range(8):
    rx = rng.randint(200, 1700)
    ry = rng.randint(80, int(H*0.35))
    rs = rng.uniform(0.6, 1.2)
    # simple bird shape
    draw.arc([int(rx - 18*rs), int(ry - 5*rs), int(rx), int(ry + 5*rs)],
             start=190, end=350, fill=SIL, width=int(2*rs))
    draw.arc([int(rx), int(ry - 5*rs), int(rx + 18*rs), int(ry + 5*rs)],
             start=190, end=350, fill=SIL, width=int(2*rs))

# === BATTLE FIRES / TORCHES along horizon ===
for fx in [350, 520, 750, 900, 1100, 1350, 1550]:
    fh = rng.randint(25, 55)
    # torch pole
    draw.line([(fx, horizon_y), (fx, horizon_y - fh - 20)], fill=SIL, width=3)
    # flame glow
    for fr in range(20, 0, -1):
        alpha = int((20 - fr) / 20 * 60)
        fc = (min(255, 200 + fr*2), max(0, 80 - fr*3), 0)
        draw.ellipse([fx - fr, horizon_y - fh - fr*2,
                      fx + fr, horizon_y - fh + fr//2], fill=fc)

# === BROKEN WEAPONS on ground ===
for _ in range(20):
    wx2 = rng.randint(400, 1500)
    wy2 = horizon_y + rng.randint(15, 80)
    angle = rng.uniform(-60, 60)
    le = rng.randint(25, 60)
    ex2 = wx2 + int(le * math.sin(math.radians(angle)))
    ey2 = wy2 - int(le * math.cos(math.radians(angle)))
    draw.line([(wx2, wy2), (ex2, ey2)], fill=SIL, width=2)

# === ATMOSPHERIC BLUR ===
pil = pil.filter(ImageFilter.GaussianBlur(radius=1.2))

# === VIGNETTE via numpy ===
arr = np.array(pil).astype(np.float32)
xx, yy = np.meshgrid(np.linspace(-1, 1, W), np.linspace(-1, 1, H))
v = np.clip(1.0 - (xx**2 + yy**2) * 0.55, 0.25, 1.0)
arr = arr * v[:, :, np.newaxis]
pil = Image.fromarray(np.clip(arr, 0, 255).astype(np.uint8))

out = '/home/michael/projects/thefinalchapter/assets/img/bg-viking.jpg'
pil.save(out, quality=92)
print(f"Saved: {out}")
