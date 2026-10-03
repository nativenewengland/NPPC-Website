from pathlib import Path

from PIL import Image, ImageEnhance, ImageFile, ImageOps


ROOT = Path(__file__).resolve().parent
SOURCE = ROOT / "source-images" / "longest-walk-rally-1978.jpg"
DESTINATION = ROOT / "source-images" / "advocacy-gap-cover.jpg"

# The archival JPEG's final scanline is slightly truncated, but the complete
# image data decodes correctly. Pillow otherwise rejects it before cropping.
ImageFile.LOAD_TRUNCATED_IMAGES = True


with Image.open(SOURCE) as original:
    image = ImageOps.exif_transpose(original).convert("RGB")
    width, height = image.size
    target_ratio = 16 / 9

    if width / height > target_ratio:
        crop_width = round(height * target_ratio)
        left = max(0, min(width - crop_width, round((width - crop_width) * 0.52)))
        box = (left, 0, left + crop_width, height)
    else:
        crop_height = round(width / target_ratio)
        top = max(0, min(height - crop_height, round((height - crop_height) * 0.43)))
        box = (0, top, width, top + crop_height)

    image = image.crop(box).resize((1600, 900), Image.Resampling.LANCZOS)
    image = ImageEnhance.Contrast(image).enhance(1.04)
    image.save(DESTINATION, "JPEG", quality=91, optimize=True, progressive=True)

print(f"wrote {DESTINATION} ({DESTINATION.stat().st_size} bytes)")
