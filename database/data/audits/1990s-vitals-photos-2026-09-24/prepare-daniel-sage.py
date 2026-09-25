from pathlib import Path

from PIL import Image


source = Path(__file__).with_name("daniel-sage-legacy.jpg")
destination = Path(__file__).parents[2] / "photos" / "1990s-research" / "daniel-sage.jpg"

# Legacy serves the 120x160 obituary portrait centered in a 900x1200 white canvas.
# Crop the supplied pixels exactly, then resize for consistent web display.
with Image.open(source) as image:
    portrait = image.convert("RGB").crop((390, 520, 510, 680))
    portrait = portrait.resize((600, 800), Image.Resampling.LANCZOS)
    destination.parent.mkdir(parents=True, exist_ok=True)
    portrait.save(destination, "JPEG", quality=92, optimize=True)

print(destination)

rita_source = Path(__file__).with_name("rita-steinhagen-wild-reed.jpg")
rita_destination = Path(__file__).parents[2] / "photos" / "1990s-research" / "rita-steinhagen.jpg"

# Michael Bayly identifies the central subject as Sister Rita in his account of
# taking this photograph at the November 1997 SOA protest.
with Image.open(rita_source) as image:
    portrait = image.convert("RGB").crop((62, 86, 190, 310))
    portrait = portrait.resize((512, 896), Image.Resampling.LANCZOS)
    portrait.save(rita_destination, "JPEG", quality=92, optimize=True)

print(rita_destination)
