"""Package reviewed guide updates for the theme deployment."""
import hashlib
import json
from pathlib import Path
import re
import shutil

ROOT = Path(__file__).resolve().parents[1]
source = ROOT / "content/guide-updates"
target = ROOT / "wp-content/themes/kurashiup-theme/releases/guide-updates"
if target.exists():
    shutil.rmtree(target)

for directory in sorted(source.glob("*/")):
    guide = json.loads((directory / "guide.json").read_text(encoding="utf-8"))
    if guide["slug"] != directory.name or not re.fullmatch(r"(?:[a-z0-9-]|%[0-9a-f]{2})+", directory.name):
        raise ValueError(f"Invalid guide slug: {directory.name}")
    names = set(re.findall(r"%%GUIDE_UPLOADS%%/([a-f0-9]{64}\.(?:jpg|png|webp|gif|avif))", guide["content"]))
    if guide["featured"]:
        names.add(guide["featured"])
    output = target / directory.name
    output.mkdir(parents=True)
    for name in sorted(names):
        if not re.fullmatch(r"[a-f0-9]{64}\.(?:jpg|png|webp|gif|avif)", name):
            raise ValueError(f"Invalid image name: {name}")
        image = directory / "images" / name
        if hashlib.sha256(image.read_bytes()).hexdigest() != name.split(".")[0]:
            raise ValueError(f"Invalid image checksum: {image}")
        shutil.copyfile(image, output / name)
    guide["images"] = sorted(names)
    (output / "manifest.json").write_text(json.dumps(guide, ensure_ascii=False) + "\n", encoding="utf-8")
