# Batch 285 portrait sources

Two identified archival images, checked September 10, 2026. Unknown photographers; no open license or public-domain claim is made. Existing portraits are never replaced. The retained pixels are unchanged: no AI, retouching, resampling, sharpening or reconstruction.

## Ahmed Obafemi

- File: `ahmed-obafemi-institute-1991-crop.png` — 602 × 700 pixels.
- [New Afrikan Institute brochure, November 20, 1991](https://freedomarchives.org/Documents/Finder/DOC510_scans/New_Afrikan_Prisoners/510.new.afrikan.institute.ahmed.obafeni.pdf), PDF page 1, cover portrait explicitly labeled Ahmed Obafemi. Freedom Archives holds the scan.
- Source PDF SHA-256: `04dfd69c58e1a1d86166cb801b6e615216a5fc50e543a500656804f414d5bf83`.
- Extract the page’s `Im0.jpg` image (1695 × 2210) with pypdf, restore its displayed orientation by a lossless 90° clockwise transpose, and crop `(1504, 405, 2106, 1105)` from the 2210 × 1695 upright image. Coordinates are left, top, right, bottom, with exclusive right/bottom edges.
- Crop SHA-256: `2ca6cd39285ddeb635a026a04f78411ff4af87d0d667d3c75b4dedc5fcc73d1c`.
- Halftone texture and the source scan’s horizontal line are retained.

## Anthony “Kimu” White

- File: `anthony-kimu-white-bukhari-crop.png` — 394 × 530 pixels.
- [Safiya Bukhari, Lest We Forget](https://black-ink.info/wp-content/uploads/2022/08/bukhari.pdf), printed page 21 / PDF page 27. The left portrait is labeled Kimu Olugbala; the preceding page identifies that name as Anthony White. The neighboring portrait is Changa Olugbala and is excluded.
- Source PDF SHA-256: `7a3f64d8a56eb5d220133d759e0da1485fb58833d493726087a7004953927603`.
- Extract page 27’s `Im25.jpg` image (1191 × 1865) with pypdf. The embedded raster is vertically inverted relative to the displayed page; restore the page orientation with a lossless top-bottom transpose. Crop `(65, 60, 459, 590)` from the upright image, using exclusive right/bottom edges.
- Crop SHA-256: `b0244b99777ccc3ce6a9e05ef424a8ff63165cd31f603389e9aa31591a2f1277`.
- The coarse archival reproduction is preserved without invented facial detail.
