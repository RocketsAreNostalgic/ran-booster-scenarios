#!/usr/bin/env python3
"""Build the six deterministic scenario assets and exact Profile B manifest."""

import argparse
import hashlib
import json
from pathlib import Path
import re
import subprocess
import zipfile


ROOT = Path(__file__).resolve().parent.parent
SLUG = "ran-booster-scenarios"
SAMPLES = ("scenario-plugin", "scenario-theme")


def digest(path: Path) -> str:
    return hashlib.sha256(path.read_bytes()).hexdigest()


def write_zip(output: Path, files: dict[str, bytes]) -> None:
    output.parent.mkdir(parents=True, exist_ok=True)
    with zipfile.ZipFile(output, "w", compression=zipfile.ZIP_DEFLATED) as archive:
        for name, data in sorted(files.items()):
            info = zipfile.ZipInfo(name, date_time=(2026, 1, 1, 0, 0, 0))
            info.create_system = 3
            info.compress_type = zipfile.ZIP_DEFLATED
            info.external_attr = 0o100644 << 16
            archive.writestr(info, data, compresslevel=9)


def package_files(root: Path) -> list[Path]:
    result: list[Path] = []
    for item in (root / "release-contents.txt").read_text().splitlines():
        item = item.strip()
        if not item or item.startswith("#"):
            continue
        relative = Path(item)
        if relative.is_absolute() or ".." in relative.parts or any(part.startswith(".") for part in relative.parts):
            raise ValueError("Unsafe release input")
        source = root / relative
        if not source.exists():
            raise ValueError(f"Missing release input: {item}")
        candidates = [source, *source.rglob("*")] if source.is_dir() else [source]
        for path in candidates:
            if path.is_symlink() or any(parent.is_symlink() for parent in path.parents if parent != root.parent):
                raise ValueError("Symlinks are not release inputs")
            if path.is_file():
                if any(part.startswith(".") for part in path.relative_to(root).parts):
                    raise ValueError("Hidden release input")
                result.append(path)
    if len(set(result)) != len(result):
        raise ValueError("Duplicate release input")
    return sorted(result)


def build_samples(root: Path) -> dict[str, dict[str, bytes]]:
    """Prepare deterministic inert archives without release or Git prerequisites."""
    for source in (root, root / "samples", root / "archives", *(root / "samples" / slug for slug in SAMPLES)):
        if source.is_symlink():
            raise ValueError("Symlinks are not fixture inputs or archive destinations")
    samples = {}
    for slug in SAMPLES:
        source = root / "samples" / slug
        files = {}
        for path in sorted(source.rglob("*")):
            if path.is_symlink():
                raise ValueError("Symlink in sample")
            if path.is_file():
                files[slug + "/" + path.relative_to(source).as_posix()] = path.read_bytes()
        if not files:
            raise ValueError("Missing sample")
        if (root / "archives" / f"{slug}.zip").is_symlink():
            raise ValueError("Symlink archive destination")
        write_zip(root / "archives" / f"{slug}.zip", files)
        samples[slug] = files
    return samples


def build(root: Path, destination: Path, commit: str | None = None) -> Path:
    for source in (root, root / "samples", root / "archives", *(root / "samples" / slug for slug in SAMPLES)):
        if source.is_symlink():
            raise ValueError("Symlinks are not fixture inputs or archive destinations")
    compatibility = json.loads((root / "compatibility.json").read_text())
    version = (root / "version.txt").read_text().strip()
    if not re.fullmatch(r"\d+\.\d+\.\d+(?:-[A-Za-z0-9.-]+)?", version):
        raise ValueError("Invalid fixture version")
    if compatibility["fixture_version"] != version:
        raise ValueError("Fixture versions differ")
    core = compatibility["core_version"]
    if not re.fullmatch(r"\d+\.\d+\.\d+(?:-[A-Za-z0-9.-]+)?", core):
        raise ValueError("Invalid Core version")
    if not re.fullmatch(r"[a-f0-9]{40}", compatibility["core_commit"]) or not re.fullmatch(r"[a-f0-9]{64}", compatibility["core_zip_sha256"]):
        raise ValueError("Exact Core source and archive identities are required")
    header = (root / f"{SLUG}.php").read_text()
    if not re.search(r"^\s*\* Version:\s*" + re.escape(version) + r"\s*$", header, re.M):
        raise ValueError("Plugin header version differs")
    if not re.search(r"^Stable tag:\s*" + re.escape(version) + r"\s*$", (root / "readme.txt").read_text(), re.M):
        raise ValueError("Packaged readme version differs")
    for name in (".release-please-manifest.json", "package.json"):
        metadata = json.loads((root / name).read_text())
        if metadata["." if name.startswith(".") else "version"] != version:
            raise ValueError(f"Version differs in {name}")
    if commit is None:
        commit = subprocess.check_output(["git", "rev-parse", "HEAD"], cwd=root, text=True).strip()
    if not re.fullmatch(r"[a-f0-9]{40}", commit):
        raise ValueError("Exact source commit is required")
    destination.mkdir(parents=True, exist_ok=True)
    expected_names = {f"{slug}-1.0.0.zip" for slug in SAMPLES}
    expected_names.add(f"{SLUG}-{version}-core-{core}.zip")
    expected_names |= {name + ".sha256" for name in list(expected_names)}
    expected_names.add("ran-profile-b-promotion.json")
    if any(path.is_symlink() or path.name not in expected_names or not path.is_file() for path in destination.iterdir()):
        raise ValueError("Unexpected existing release output")
    assets = []
    for slug, files in build_samples(root).items():
        seed = destination / f"{slug}-1.0.0.zip"
        write_zip(seed, {name: data.replace(b"Version: 2.0.0", b"Version: 1.0.0") for name, data in files.items()})
        assets.append(seed)
    output = destination / f"{SLUG}-{version}-core-{core}.zip"
    write_zip(output, {SLUG + "/" + path.relative_to(root).as_posix(): path.read_bytes() for path in package_files(root)})
    assets.append(output)
    for archive in list(assets):
        checksum = archive.with_suffix(".zip.sha256")
        checksum.write_text(f"{digest(archive)}  {archive.name}\n")
        assets.append(checksum)
    manifest = {"schema": "ran-profile-b-promotion", "schema_version": 1,
                "repository": "RocketsAreNostalgic/ran-booster-scenarios", "quality_commit": commit,
                "source_commit": commit, "tag": f"v{version}",
                "assets": [{"name": path.name, "sha256": digest(path)} for path in sorted(assets)]}
    (destination / "ran-profile-b-promotion.json").write_text(json.dumps(manifest, indent=2) + "\n")
    return output


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--output-dir", type=Path, default=ROOT / "build/release")
    parser.add_argument("--samples-only", action="store_true", help="Prepare only the inert archives required by source tests")
    args = parser.parse_args()
    if args.samples_only:
        build_samples(ROOT)
        print("Inert sample archives prepared.")
    else:
        print(build(ROOT, args.output_dir.resolve()))
