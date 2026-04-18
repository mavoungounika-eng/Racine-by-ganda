#!/usr/bin/env python3
"""
Generate a branded 1200x630 OpenGraph image for Racine by Ganda.

Output: public/images/og-image-racine.jpg

Brand charter:
  - Background:  #160D0C (racine-black)
  - Accent 1:    #ED5F1E (racine-orange)
  - Accent 2:    #FFB800 (racine-yellow)
  - Text:        #FFFFFF

The script is intentionally self-contained: it degrades gracefully if the
logo PNG or a bold TTF font is missing.
"""
from __future__ import annotations

import sys
from pathlib import Path

try:
    from PIL import Image, ImageDraw, ImageFont
except ImportError:
    sys.stderr.write(
        "Pillow is required. Install with: pip3 install --user Pillow\n"
    )
    sys.exit(1)

ROOT = Path(__file__).resolve().parent.parent
LOGO_PATH = ROOT / "public" / "images" / "logo-racine.png"
OUTPUT_PATH = ROOT / "public" / "images" / "og-image-racine.jpg"

WIDTH, HEIGHT = 1200, 630
BG = (22, 13, 12)
ORANGE = (237, 95, 30)
YELLOW = (255, 184, 0)
WHITE = (255, 255, 255)
WHITE_SOFT = (255, 255, 255, 210)


def load_font(size: int, bold: bool = False) -> ImageFont.FreeTypeFont:
    """Try a short list of known-good Linux fonts, fall back to default."""
    candidates_bold = [
        "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf",
        "/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf",
        "/usr/share/fonts/TTF/DejaVuSans-Bold.ttf",
    ]
    candidates_regular = [
        "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf",
        "/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf",
        "/usr/share/fonts/TTF/DejaVuSans.ttf",
    ]
    for path in (candidates_bold if bold else candidates_regular):
        if Path(path).exists():
            return ImageFont.truetype(path, size)
    return ImageFont.load_default()


def main() -> int:
    img = Image.new("RGB", (WIDTH, HEIGHT), BG)
    draw = ImageDraw.Draw(img, "RGBA")

    # Subtle diagonal gradient overlay (orange glow top-left)
    for i in range(0, 260, 2):
        alpha = int(60 * (1 - i / 260))
        draw.ellipse(
            [-200 - i, -200 - i, 500 + i, 500 + i],
            fill=(237, 95, 30, alpha),
        )

    # Vertical orange accent bar (left edge)
    draw.rectangle([0, 0, 18, HEIGHT], fill=ORANGE)

    # Yellow accent line under the wordmark
    draw.rectangle([90, 380, 90 + 120, 388], fill=YELLOW)

    # Logo (if present)
    logo_right_edge = 90
    if LOGO_PATH.exists():
        try:
            logo = Image.open(LOGO_PATH).convert("RGBA")
            # Fit logo to ~110px height
            target_h = 110
            ratio = target_h / logo.height
            logo = logo.resize(
                (int(logo.width * ratio), target_h),
                Image.Resampling.LANCZOS,
            )
            img.paste(logo, (90, 90), logo)
            logo_right_edge = 90 + logo.width + 24
        except Exception as exc:  # noqa: BLE001
            sys.stderr.write(f"[warn] failed to paste logo: {exc}\n")

    # Wordmark
    title_font = load_font(92, bold=True)
    subtitle_font = load_font(36, bold=False)
    tag_font = load_font(26, bold=True)

    draw.text((90, 240), "Racine by Ganda", fill=WHITE, font=title_font)
    draw.text(
        (90, 410),
        "Mode & lifestyle artisanal du Cameroun",
        fill=WHITE_SOFT,
        font=subtitle_font,
    )

    # Tagline pill (bottom-left)
    tag_text = "RACINE.CM"
    # Rough text bbox for pill sizing
    bbox = draw.textbbox((0, 0), tag_text, font=tag_font)
    tag_w = bbox[2] - bbox[0]
    tag_h = bbox[3] - bbox[1]
    pad_x, pad_y = 22, 14
    pill_x0, pill_y0 = 90, HEIGHT - 90 - (tag_h + pad_y * 2)
    pill_x1 = pill_x0 + tag_w + pad_x * 2
    pill_y1 = pill_y0 + tag_h + pad_y * 2
    draw.rounded_rectangle(
        [pill_x0, pill_y0, pill_x1, pill_y1],
        radius=(pill_y1 - pill_y0) // 2,
        fill=ORANGE,
    )
    draw.text(
        (pill_x0 + pad_x, pill_y0 + pad_y - 2),
        tag_text,
        fill=WHITE,
        font=tag_font,
    )

    # Corner signature (bottom-right)
    sig_font = load_font(20, bold=False)
    draw.text(
        (WIDTH - 280, HEIGHT - 50),
        "ganda-creators.com",
        fill=(255, 255, 255, 140),
        font=sig_font,
    )

    OUTPUT_PATH.parent.mkdir(parents=True, exist_ok=True)
    img.save(OUTPUT_PATH, "JPEG", quality=92, optimize=True, progressive=True)
    print(f"[ok] wrote {OUTPUT_PATH} ({OUTPUT_PATH.stat().st_size} bytes)")
    return 0


if __name__ == "__main__":
    sys.exit(main())
