#!/usr/bin/env python3
"""
Fast battlefield background generator for The Final Chapter Heavy Metal website.
Uses numpy if available for speed, otherwise pure Python (slower but works).
Output: 1920x1080 dark gothic ruins/battlefield JPEG
"""

import os, struct, zlib, math, sys

OUT = '/home/michael/projects/thefinalchapter/assets/img/bg-battlefield2.jpg'
PNG = OUT.replace('.jpg', '.png')
W, H = 1920, 1080

def lerp(a, b, t): return a + (b - a) * t
def clamp(v, lo=0.0, hi=1.0): return max(lo, min(hi, v))

def h2(ix, iy, s=0):
    n = ix * 1619 + iy * 31337 + s * 1013904223
    n = ((n ^ (n >> 13)) * 1664525 + 1013904223) & 0xFFFFFFFF
    return (n & 0xFFFF) / 65535.0

def vnoise(x, y, s=0):
    xi, yi = int(math.floor(x)), int(math.floor(y))
    xf, yf = x - xi, y - yi
    xf2 = xf*xf*xf*(xf*(xf*6-15)+10)
    yf2 = yf*yf*yf*(yf*(yf*6-15)+10)
    return lerp(lerp(h2(xi,yi,s),h2(xi+1,yi,s),xf2), lerp(h2(xi,yi+1,s),h2(xi+1,yi+1,s),xf2), yf2)

def fbm(x, y, o=5, p=0.5, l=2.0, s=0):
    v, a, f, t = 0.0, 1.0, 1.0, 0.0
    for i in range(o):
        v += vnoise(x*f, y*f, s+i*997)*a; t += a; a *= p; f *= l
    return v / t

print(f"Generating {W}x{H} dark battlefield image...", flush=True)

# Use numpy if available for much faster execution
try:
    import numpy as np
    print("Using numpy for fast generation...", flush=True)

    xs = np.linspace(0, 1, W)
    ys = np.linspace(0, 1, H)
    NX, NY = np.meshgrid(xs, ys)  # (H, W)

    def np_h2(ix, iy, s=0):
        n = ix * 1619 + iy * 31337 + s * 1013904223
        n = ((n ^ (n >> 13)) * 1664525 + 1013904223) & 0xFFFFFFFF
        return (n & 0xFFFF) / 65535.0

    def np_vnoise(x, y, s=0):
        xi = np.floor(x).astype(np.int64)
        yi = np.floor(y).astype(np.int64)
        xf = x - xi; yf = y - yi
        xf2 = xf**3*(xf*(xf*6-15)+10); yf2 = yf**3*(yf*(yf*6-15)+10)
        v00 = np_h2(xi,yi,s); v10 = np_h2(xi+1,yi,s)
        v01 = np_h2(xi,yi+1,s); v11 = np_h2(xi+1,yi+1,s)
        return (v00*(1-xf2)+v10*xf2)*(1-yf2) + (v01*(1-xf2)+v11*xf2)*yf2

    def np_fbm(x, y, o=5, p=0.5, l=2.0, s=0):
        v = np.zeros_like(x); a = 1.0; f = 1.0; t = 0.0
        for i in range(o):
            v += np_vnoise(x*f, y*f, s+i*997)*a; t += a; a *= p; f *= l
        return v / t

    # Sky gradient
    HZ = 0.40
    sky_t = np.clip(1.0 - NY / HZ, 0, 1)
    sky_r = 22 - sky_t * 16
    sky_g = 10 - sky_t * 7
    sky_b = 18 - sky_t * 13

    # Horizon glow
    hz_d = np.abs(NY - HZ)
    hz = np.exp(-hz_d**2 * 400)
    hz2 = np.exp(-hz_d**2 * 80) * 0.5
    sky_r += hz * 80 + hz2 * 30
    sky_g += hz * 12 + hz2 * 8
    sky_b += hz * 5

    # Moon glow
    mcx, mcy = 0.78, 0.12
    md = np.sqrt((NX - mcx)**2 + ((NY - mcy)*1.5)**2)
    mg = np.exp(-md**2 * 80) * 0.7
    mh = np.exp(-md * 12) * 0.3
    sky_r += mg*25 + mh*8; sky_g += mg*28 + mh*12; sky_b += mg*35 + mh*18

    # Storm clouds
    print("  clouds...", flush=True)
    cn1 = np_fbm(NX*3.0+0.2, NY*2.5+0.1, o=6, p=0.55, s=11)
    cn2 = np_fbm(NX*5.5-0.4, NY*4.0+0.3, o=4, p=0.5, s=23)
    cv = cn1*0.65 + cn2*0.35
    cm = np.clip(1.0 - NY/0.55, 0, 1)**0.7
    cd = np.clip((cv - 0.30)*2.5, 0, 1) * cm
    cr = sky_r + (28 + mg*15 - sky_r)*cd
    cg = sky_g + (18 + mg*12 - sky_g)*cd
    cb = sky_b + (26 + mg*20 - sky_b)*cd

    # Ground terrain
    print("  terrain...", flush=True)
    gs = HZ - 0.03
    gf = np.clip((NY - gs) / (1.0 - gs), 0, 1)
    is_g = NY > gs
    tn1 = np_fbm(NX*5.0, NY*3.0+2.0, o=5, p=0.58, s=5)
    tn2 = np_fbm(NX*11.0, NY*7.0, o=3, p=0.45, s=19)
    gr_r = 8 + tn1*18 + tn2*7
    gr_g = 5 + tn1*10 + tn2*4
    gr_b = 4 + tn1*7  + tn2*3
    bt = np.clip(1.0 - gf/0.4, 0, 1)*0.5
    gr_r += np.where(gf < 0.4, bt*20, 0)
    gr_g += np.where(gf < 0.4, bt*2, 0)
    blend = np.clip(gf*3.0, 0, 1)
    R = np.where(is_g, cr + (gr_r-cr)*blend, cr)
    G = np.where(is_g, cg + (gr_g-cg)*blend, cg)
    B = np.where(is_g, cb + (gr_b-cb)*blend, cb)

    # Silhouettes (simplified but effective)
    print("  silhouettes...", flush=True)
    sil = np.zeros((H, W))

    # Cathedral ruin walls (vectorized approximation)
    wx1, wx2, wtop, wbase = 0.18, 0.38, 0.22, 0.52
    in_wall = (NX > wx1) & (NX < wx2) & (NY > wtop) & (NY < wbase)
    rel_wx = (NX - wx1) / (wx2 - wx1)
    # Main arch window cutout
    win_dx = np.abs(rel_wx - 0.5) / 0.11
    win_ny = (NY - (wtop + (wbase-wtop)*0.08)) / ((wbase-wtop)*0.75)
    arch_open = (1.0 - np.clip(win_dx, 0, 1)**2) * (1.0 - np.clip(win_ny,0,1)*0.3) > 0.7
    wall_sil = in_wall & ~arch_open
    sil = np.where(wall_sil, 1.0, sil)

    # Left spire
    for sx, st, sw in [(0.24, 0.08, 0.008), (0.29, 0.14, 0.007)]:
        tip = np.clip((NY - st)/0.06, 0, 1)
        spire = (np.abs(NX - sx) < sw*(0.2 + tip*0.8)) & (NY > st)
        sil = np.where(spire, 1.0, sil)

    # Broken tower left
    tcx, tw, tt = 0.06, 0.028, 0.18
    tower = (np.abs(NX - tcx) < tw) & (NY > tt + np_vnoise(NX*30, np.full_like(NX, 0.3), s=33)*0.06)
    sil = np.where(tower, 1.0, sil)

    # Right ruin wall
    rn_wall = np_fbm(NX*20, np.full_like(NX, 0.7), o=2, s=44)*0.10
    right_wall = (NX > 0.62) & (NX < 0.72) & (NY > 0.30 + rn_wall)
    sil = np.where(right_wall, 1.0, sil)

    # Dead trees
    for tx, tb, trw in [(0.48,0.42,0.011),(0.52,0.39,0.009),(0.55,0.44,0.013),(0.85,0.40,0.010),(0.88,0.43,0.012),(0.92,0.38,0.008)]:
        dx = np.abs(NX - tx)
        trunk = (dx < trw) & (NY > tb-0.16) & (NY < tb)
        sil = np.where(trunk, 1.0, sil)
        for bi in range(3):
            bh = tb - 0.16 + bi*0.045
            bspr = trw * (3.5 - bi*0.6)
            branch = (np.abs(NY - bh) < 0.01) & (dx < bspr)
            sil = np.where(branch, np.maximum(sil, 0.9), sil)

    # Warrior silhouettes at horizon
    wbase_y = 0.52; wh = 0.09
    for wx_c, ww in [(0.42,0.005),(0.44,0.004),(0.46,0.005),(0.56,0.004),(0.58,0.005),(0.60,0.004),(0.62,0.005)]:
        dx_w = np.abs(NX - wx_c)
        wy = (NY - (wbase_y - wh)) / wh
        body = (dx_w < ww) & (NY > wbase_y-wh) & (NY < wbase_y)
        sil = np.where(body, np.maximum(sil, wy*0.2 + 0.8), sil)

    R = R * (1.0 - sil*0.96)
    G = G * (1.0 - sil*0.96)
    B = B * (1.0 - sil*0.96)

    # Fog
    print("  fog...", flush=True)
    f1 = np_fbm(NX*3.5+0.3, NY*1.8+0.5, o=4, p=0.52, s=29)
    f2 = np_fbm(NX*6.0-0.6, NY*3.0+0.9, o=3, p=0.48, s=41)
    fv = f1*0.6 + f2*0.4
    fhz = np.exp(-(NY-HZ)**2 / 0.008) * 0.9
    fgnd = np.where(is_g, gf*0.5, 0.0)
    ft = np.clip(fv*(fhz + fgnd*0.6)*1.4, 0, 1)
    fr = 20 + hz*15; fg_col = 16 + hz*5; fb_col = 22
    R = R*(1-ft) + fr*ft; G = G*(1-ft) + fg_col*ft; B = B*(1-ft) + fb_col*ft

    # Embers
    en = np_vnoise(NX*120+0.7, NY*80+0.3, s=67)
    ember_m = (en > 0.97) & (NY > 0.38) & (NY < 0.65)
    eb = np.clip((en - 0.97)/0.03, 0, 1)
    R = np.where(ember_m, R + eb*120, R); G = np.where(ember_m, G + eb*40, G)

    # Vignette
    vx = (NX - 0.5)*2; vy = (NY - 0.5)*2
    vig = np.clip(1.0 - (vx**2*0.18 + vy**2*0.14), 0.55, 1.0)
    vig *= np.clip(1.0 - np.clip(1.0 - NY*4.0,0,1)*0.3, 0, 1)
    luma = R*0.299 + G*0.587 + B*0.114
    R = (R*0.85 + luma*0.15)*0.88*vig
    G = (G*0.85 + luma*0.15)*0.88*vig
    B = (B*0.85 + luma*0.15)*0.88*vig

    # Convert to uint8
    img_arr = np.stack([
        np.clip(R, 0, 255).astype(np.uint8),
        np.clip(G, 0, 255).astype(np.uint8),
        np.clip(B, 0, 255).astype(np.uint8)
    ], axis=2)

    print("Writing image...", flush=True)

    # Save
    try:
        from PIL import Image
        img = Image.fromarray(img_arr, 'RGB')
        img.save(OUT, 'JPEG', quality=92, optimize=True, progressive=True)
        print(f"Saved via PIL: {OUT} ({os.path.getsize(OUT):,} bytes)")
    except ImportError:
        # Write as PNG, convert with ffmpeg
        def write_png(fn, arr, w, h):
            def chunk(n,d): c=n+d; return struct.pack('>I',len(d))+c+struct.pack('>I',zlib.crc32(c)&0xffffffff)
            raw=bytearray()
            for row in arr:
                raw.append(0)
                raw.extend(row.tobytes())
            sig=b'\x89PNG\r\n\x1a\n'
            hdr=chunk(b'IHDR',struct.pack('>IIBBBBB',w,h,8,2,0,0,0))
            dat=chunk(b'IDAT',zlib.compress(bytes(raw),7))
            end=chunk(b'IEND',b'')
            with open(fn,'wb') as f: f.write(sig+hdr+dat+end)
        write_png(PNG, img_arr, W, H)
        import subprocess
        r = subprocess.run(['ffmpeg','-y','-i',PNG,'-q:v','3',OUT],capture_output=True)
        if r.returncode == 0:
            os.remove(PNG)
            print(f"Saved via ffmpeg: {OUT} ({os.path.getsize(OUT):,} bytes)")
        else:
            print(f"ffmpeg error: {r.stderr.decode()[-300:]}")
            os.rename(PNG, OUT)
            print(f"Saved as PNG: {OUT}")

except ImportError:
    # Pure Python fallback (slower)
    print("numpy not available, using pure Python (this will take ~30-60s)...", flush=True)

    def fbm_fast(x, y, o=4, p=0.5, l=2.0, s=0):
        v,a,f,t=0.0,1.0,1.0,0.0
        for i in range(o):
            v+=vnoise(x*f,y*f,s+i*997)*a; t+=a; a*=p; f*=l
        return v/t

    img_data = bytearray()
    for py in range(H):
        if py % 108 == 0: print(f"  {py//108*10}%...", flush=True)
        img_data.append(0)  # PNG row filter
        ny = py / (H-1)
        for px in range(W):
            nx = px / (W-1)
            HZ = 0.40
            sky_t = clamp(1.0 - ny/HZ)
            sr = 22 - sky_t*16; sg = 10 - sky_t*7; sb = 18 - sky_t*13
            hz_d = abs(ny - HZ); hz = math.exp(-hz_d**2*400)
            hz2 = math.exp(-hz_d**2*80)*0.5
            sr += hz*80+hz2*30; sg += hz*12+hz2*8; sb += hz*5
            md = math.sqrt((nx-0.78)**2+((ny-0.12)*1.5)**2)
            mg = math.exp(-md**2*80)*0.7
            sr += mg*25; sg += mg*28; sb += mg*35
            cn = fbm_fast(nx*3.0+0.2, ny*2.5+0.1, o=4, p=0.55, s=11)*0.65 + fbm_fast(nx*5.5-0.4, ny*4.0+0.3, o=3, p=0.5, s=23)*0.35
            cm = clamp(1.0-ny/0.55)**0.7
            cd = clamp((cn-0.30)*2.5)*cm
            cr2 = lerp(sr, 28+mg*15, cd); cg2 = lerp(sg, 18+mg*12, cd); cb2 = lerp(sb, 26+mg*20, cd)
            gs = HZ-0.03; gf = clamp((ny-gs)/(1.0-gs)); is_g = ny > gs
            if is_g:
                tn = fbm_fast(nx*5.0, ny*3.0+2.0, o=4, p=0.58, s=5)
                tn2 = fbm_fast(nx*11.0, ny*7.0, o=2, p=0.45, s=19)
                gr = 8+tn*18+tn2*7; gg = 5+tn*10+tn2*4; gb = 4+tn*7+tn2*3
                bl = clamp(gf*3.0)
                R = lerp(cr2,gr,bl); G = lerp(cg2,gg,bl); Bv = lerp(cb2,gb,bl)
            else:
                R,G,Bv = cr2,cg2,cb2
            # Simplified silhouette (just wall outline)
            sil = 0.0
            if 0.18 < nx < 0.38 and 0.22 < ny < 0.52:
                sil = 0.95
            elif 0.04 < nx < 0.09 and ny > 0.18:
                sil = 1.0
            elif 0.48 < nx < 0.57 and 0.28 < ny < 0.44:
                dx2 = abs(nx-0.52); sil = 1.0 if dx2 < 0.008 and 0.28 < ny < 0.44 else 0.0
            elif 0.62 < nx < 0.72 and ny > 0.32:
                sil = 0.95
            R*=(1-sil*0.96); G*=(1-sil*0.96); Bv*=(1-sil*0.96)
            # Fog
            f1 = fbm_fast(nx*3.5+0.3, ny*1.8+0.5, o=3, p=0.52, s=29)
            ft = clamp(f1 * math.exp(-(ny-HZ)**2/0.008) * 1.2)
            R=lerp(R,20+hz*15,ft); G=lerp(G,16+hz*5,ft); Bv=lerp(Bv,22,ft)
            # Vignette
            vx=(nx-0.5)*2; vy=(ny-0.5)*2
            vig=clamp(1.0-(vx**2*0.18+vy**2*0.14),0.55,1.0)
            luma=R*0.299+G*0.587+Bv*0.114
            R=(R*0.85+luma*0.15)*0.88*vig; G=(G*0.85+luma*0.15)*0.88*vig; Bv=(Bv*0.85+luma*0.15)*0.88*vig
            img_data.append(max(0,min(255,int(R))))
            img_data.append(max(0,min(255,int(G))))
            img_data.append(max(0,min(255,int(Bv))))

    print("Writing PNG...", flush=True)
    def chunk(n,d): c=n+d; return struct.pack('>I',len(d))+c+struct.pack('>I',zlib.crc32(c)&0xffffffff)
    sig=b'\x89PNG\r\n\x1a\n'
    hdr=chunk(b'IHDR',struct.pack('>IIBBBBB',W,H,8,2,0,0,0))
    dat=chunk(b'IDAT',zlib.compress(bytes(img_data),7))
    end=chunk(b'IEND',b'')
    with open(PNG,'wb') as f: f.write(sig+hdr+dat+end)
    import subprocess
    r=subprocess.run(['ffmpeg','-y','-i',PNG,'-q:v','3',OUT],capture_output=True)
    if r.returncode==0:
        os.remove(PNG)
        print(f"Saved: {OUT} ({os.path.getsize(OUT):,} bytes)")
    else:
        os.rename(PNG,OUT); print(f"Saved as PNG: {OUT}")

print("DONE.")
