from pathlib import Path
from PIL import Image, ImageOps
import hashlib, json, re

Image.MAX_IMAGE_PIXELS = None
ROOT = Path(__file__).resolve().parent
SOURCE = ROOT / "sources"
OUTPUT = ROOT / "photos"
OUTPUT.mkdir(exist_ok=True)
for old_crop in OUTPUT.glob("*.jpg"):
    old_crop.unlink()

# box values are normalized (left, top, right, bottom).  They are used only
# where a caption identifies the subject's position in a group photograph.
entries = [
    ("Jorge Luis Jimenez", "jorge-luis-jimenez", "32121116817", None),
    ("Manuel Rabago Torres", "manuel-rabago-torres", "33188405358", None),
    ("Juan Bernardo Lebron", "juan-bernardo-lebron", "40099164593", None),
    ("Armando Diaz Matos", "armando-diaz-matos", "47012457322", None),
    ("Maximino Pedraza Martinez", "maximino-pedraza-martinez", "46340973894", None),
    ("Esteban Quinones Escute", "esteban-quinones-escute", "46150163905", None),
    ("Angel Luis Arzola Velez", "angel-luis-arzola-velez", "47063671261", None),
    ("Antonio Herrera Moreno", "antonio-herrera-moreno", "47063460261", None),
    ("Julio Flores Medina", "julio-flores-medina", "40099164753", None),
    ("Miguel Vargas Nieves", "miguel-vargas-nieves", "33188405828", None),
    ("Reginald Booker", "reginald-booker", "49430697446", None),
    ("Sammie Abbott", "sammie-abbott", "50052186911", None),
    ("Laurence Henry", "laurence-henry", "18738610279", (0.00, 0.00, 0.42, 1.00)),
    ("Frederick C. Weaver", "frederick-c-weaver", "38686939440", None),
    ("Hugo Gellert", "hugo-gellert", "18708003175", (0.31, 0.12, 0.54, 0.57)),
    ("Livia Gellert", "livia-gellert", "18708003175", (0.68, 0.09, 0.92, 0.59)),
    ("Dion Diamond", "dion-diamond", "8394393448", (0.00, 0.00, 0.52, 1.00)),
    ("Walter Fauntroy", "walter-fauntroy", "49440731607", None),
    ("Coleman Young", "coleman-young", "33113183760", (0.15, 0.10, 0.58, 0.96)),
    ("Joseph Brinton “Brint” Dillingham", "joseph-brinton-brint-dillingham", "33561033751", (0.00, 0.00, 0.61, 1.00)),
    ("Patrick B. “Paddy” Whalen", "patrick-b-paddy-whalen", "15820019564", None),
    ("Jesse L. Jackson", "jesse-l-jackson", "33543869258", (0.03, 0.20, 0.49, 0.66)),
    ("Harry Belafonte", "harry-belafonte", "48716471002", (0.00, 0.00, 0.56, 1.00)),
    ("Coretta Scott King", "coretta-scott-king", "21015381546", (0.40, 0.22, 0.63, 1.00)),
    ("Leonard P. Matlovich", "leonard-p-matlovich", "47595369781", (0.47, 0.00, 1.00, 1.00)),
    ("Debbie Danielle", "debbie-danielle", "35338412690", (0.25, 0.08, 0.68, 0.64)),
    ("Joseph Winkowsky", "joseph-winkowsky", "26973410425", (0.00, 0.10, 0.58, 1.00)),
    ("Bill Bricker", "bill-bricker", "48550652996", (0.00, 0.00, 0.62, 0.70)),
    ("Carroll Carrozza", "carroll-carrozza", "8297283078", (0.00, 0.02, 0.40, 0.67)),
    ("Carolyn Banks", "carolyn-banks", "8297283078", (0.64, 0.04, 1.00, 1.00)),
    ("William “Preacherman” Fesperman", "william-preacherman-fesperman", "49691552157", (0.11, 0.16, 0.47, 1.00)),
    ("Judy Erickson", "judy-erickson", "49691552157", (0.47, 0.32, 0.76, 1.00)),
    ("Nancy Willis", "nancy-willis", "49691552157", (0.71, 0.30, 1.00, 1.00)),
    ("Carol J. Lawson", "carol-j-lawson", "49527168392", (0.00, 0.00, 0.30, 1.00)),
    ("Sheila P. Ryan", "sheila-p-ryan", "49527168392", (0.23, 0.00, 0.53, 1.00)),
    ("Pamela C. Haynes", "pamela-c-haynes", "49527168392", (0.46, 0.00, 0.76, 1.00)),
    ("J. Holmes Smith", "j-holmes-smith", "35615046081", (0.17, 0.12, 0.39, 0.75)),
    ("Ralph T. Templin", "ralph-t-templin", "35615046081", (0.30, 0.12, 0.52, 0.75)),
    ("Sandra Perrin", "sandra-perrin", "8306115890", (0.55, 0.30, 0.84, 1.00)),
    ("Theodore H. Parrish", "theodore-h-parrish", "8306115890", (0.41, 0.10, 0.58, 0.40)),
]

metadata = {}
for line in (ROOT / "photostream.jsonl").read_text(encoding="utf-8").splitlines():
    item = json.loads(line)
    metadata[item["id"]] = item

manifest = []
for name, slug, photo_id, box in entries:
    files = list(SOURCE.glob(f"{photo_id}.*"))
    if len(files) != 1:
        raise RuntimeError(f"Expected one source for {photo_id}, found {len(files)}")
    with Image.open(files[0]) as opened:
        image = ImageOps.exif_transpose(opened).convert("RGB")
        if box:
            w, h = image.size
            image = image.crop(tuple(round(v * dim) for v, dim in zip(box, (w, h, w, h))))
        image = ImageOps.fit(image, (800, 1000), method=Image.Resampling.LANCZOS, centering=(0.5, 0.44))
        output = OUTPUT / f"{slug}.jpg"
        image.save(output, "JPEG", quality=88, optimize=True, progressive=True)
    meta = metadata[photo_id]
    manifest.append({
        "name": name,
        "slug": slug,
        "filename": output.name,
        "photo": f"prisoners/flickr-washington-area-spark/{output.name}",
        "photo_id": photo_id,
        "source_url": f"https://www.flickr.com/photos/washington_area_spark/{photo_id}/",
        "source_title": meta["title"],
        "sha256": hashlib.sha256(output.read_bytes()).hexdigest(),
        "crop": box,
    })

(ROOT / "photo-manifest.json").write_text(json.dumps(manifest, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
print(json.dumps({"created": len(manifest), "directory": str(OUTPUT)}, indent=2))
