"""Bundle the explicitly approved watch article into the theme deployment."""
import hashlib
import json
from pathlib import Path
import shutil

ROOT = Path(__file__).resolve().parents[1]
SOURCE = ROOT / "content/review-guides/running-watch-8-compare"
TARGET = ROOT / "wp-content/themes/kurashiup-theme/releases/running-watch-20261111"
metadata = json.loads((SOURCE / "公開準備.json").read_text())
files = ["WordPress公開用本文.html", "kurashiup-running-watches-share.jpg"]
files += [str(path.relative_to(SOURCE)) for path in sorted((SOURCE / "assets").iterdir())]
assert len(files) == 10
TARGET.mkdir(parents=True, exist_ok=True)
checksums = {}
for name in files:
    destination = TARGET / name
    destination.parent.mkdir(parents=True, exist_ok=True)
    shutil.copyfile(SOURCE / name, destination)
    checksums[name] = hashlib.sha256(destination.read_bytes()).hexdigest()
manifest = {key: metadata[key] for key in (
    "title", "slug", "excerpt", "content_file", "featured_image_file",
    "featured_image_alt", "information_checked_date",
)}
manifest["files"] = checksums
(TARGET / "manifest.json").write_text(json.dumps(manifest, ensure_ascii=False, indent=2) + "\n")
print(f"Packaged {len(files)} checked files for running-watch-20261111")
