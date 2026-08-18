#!/bin/bash
# Generate bg-battlefield2.jpg for The Final Chapter website
set -e
cd "$(dirname "$0")"

echo "[gen_bg.sh] Checking for numpy..."
# Try to install numpy in a temp venv for speed
VENV_DIR=/tmp/gen_bg_venv
if [ ! -d "$VENV_DIR" ]; then
    python3 -m venv "$VENV_DIR" --system-site-packages
    "$VENV_DIR/bin/pip" install -q numpy pillow 2>/dev/null || true
fi

echo "[gen_bg.sh] Running generator..."
"$VENV_DIR/bin/python3" gen_bg.py || python3 gen_bg.py

echo "[gen_bg.sh] Done."
ls -lh assets/img/bg-battlefield2.jpg 2>/dev/null || ls -lh assets/img/bg-battlefield2.png 2>/dev/null
