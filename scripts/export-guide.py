"""Export a published guide from the local Compose WordPress into Git."""
import argparse
import base64
import json
from pathlib import Path
import re
import subprocess

ROOT = Path(__file__).resolve().parents[1]

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument("slug", help="Existing guide slug from the local URL")
parser.add_argument("--create", action="store_true", help="Allow this guide to be published for the first time")
args = parser.parse_args()
payload = subprocess.check_output(
    ["docker", "compose", "exec", "-T", "wordpress", "php", "--", args.slug],
    cwd=ROOT,
    input=(ROOT / "scripts/export-guide.php").read_bytes(),
)
export = json.loads(payload)
guide = export["guide"]
if not re.fullmatch(r"(?:[a-z0-9-]|%[0-9a-f]{2})+", guide["slug"]):
    raise SystemExit("Unexpected WordPress slug.")
destination = ROOT / "content/guide-updates" / guide["slug"]
images = destination / "images"
snapshot = destination / "guide.json"
if args.create or (snapshot.exists() and json.loads(snapshot.read_text(encoding="utf-8")).get("allow_create") is True):
    guide["allow_create"] = True
images.mkdir(parents=True, exist_ok=True)
for name, encoded in export["images"].items():
    (images / name).write_bytes(base64.b64decode(encoded, validate=True))
for path in images.iterdir():
    if path.is_file() and path.name not in export["images"]:
        path.unlink()
(destination / "guide.json").write_text(
    json.dumps(guide, ensure_ascii=False, indent=2) + "\n", encoding="utf-8"
)
print(destination.relative_to(ROOT))
