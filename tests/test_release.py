"""Packaging safety and deterministic promotion evidence, without network access."""

import importlib.util
import json
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile
import unittest
import zipfile

ROOT = Path(__file__).resolve().parent.parent
sys.path.insert(0, str(ROOT / 'scripts'))
SPEC = importlib.util.spec_from_file_location("builder", ROOT / "scripts/build-release.py")
assert SPEC and SPEC.loader
builder = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(builder)


class ReleaseTest(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory()
        self.addCleanup(self.temporary.cleanup)
        self.root = Path(self.temporary.name) / "source"
        self.root.mkdir()
        for name in ("version.txt", "compatibility.json", "ran-booster-scenarios.php", "catalog.php", "pairing.php", "journeys.php", "README.md", "CONTRIBUTING.md", "RELEASE.md", "readme.txt", "LICENSE", "LICENSE.starter", "release-contents.txt", ".release-please-manifest.json", "package.json"):
            shutil.copy2(ROOT / name, self.root / name)
        shutil.copytree(ROOT / "samples", self.root / "samples")
        shutil.copytree(ROOT / "docs", self.root / "docs")
        shutil.copytree(ROOT / "languages", self.root / "languages")

    def test_reproducible_assets_preserve_allowlist_and_seed_identity(self):
        first = Path(self.temporary.name) / "first"
        second = Path(self.temporary.name) / "second"
        output = builder.build(self.root, first, "a" * 40)
        builder.build(self.root, second, "a" * 40)
        self.assertEqual({p.name: p.read_bytes() for p in first.iterdir()}, {p.name: p.read_bytes() for p in second.iterdir()})
        manifest = json.loads((first / "ran-profile-b-promotion.json").read_text())
        self.assertEqual(len(manifest["assets"]), 6)
        self.assertEqual(manifest["source_commit"], "a" * 40)
        for item in manifest["assets"]:
            self.assertEqual(item["sha256"], builder.digest(first / item["name"]))
        with zipfile.ZipFile(output) as archive:
            self.assertTrue(all(name.startswith("ran-booster-scenarios/") for name in archive.namelist()))
            self.assertFalse(any("vendor/" in name or "tests/" in name or "scripts/" in name for name in archive.namelist()))
            self.assertIn("ran-booster-scenarios/archives/scenario-plugin.zip", archive.namelist())
            self.assertIn("ran-booster-scenarios/languages/ran-booster-scenarios.pot", archive.namelist())
        for slug, header in (("scenario-plugin", "scenario-plugin.php"), ("scenario-theme", "style.css")):
            with zipfile.ZipFile(first / f"{slug}-1.0.0.zip") as archive:
                self.assertIn(b"Version: 1.0.0", archive.read(f"{slug}/{header}"))

    def test_unit_command_prepares_missing_archives_in_a_fresh_source_tree(self):
        shutil.copy2(ROOT / "composer.json", self.root / "composer.json")
        shutil.copy2(ROOT / "phpunit.xml", self.root / "phpunit.xml")
        (self.root / "scripts").mkdir()
        shutil.copy2(ROOT / "scripts/build-release.py", self.root / "scripts/build-release.py")
        shutil.copy2(ROOT / "scripts/blueprint_fixtures.py", self.root / "scripts/blueprint_fixtures.py")
        (self.root / "tests").mkdir()
        for name in ("bootstrap.php", "ScenarioFixturePluginTest.php"):
            shutil.copy2(ROOT / "tests" / name, self.root / "tests" / name)
        shutil.copytree(ROOT / "tests/Support", self.root / "tests/Support")
        (self.root / "vendor").symlink_to(ROOT / "vendor", target_is_directory=True)
        self.assertFalse((self.root / "archives").exists())
        result = subprocess.run(["composer", "run-script", "test:unit", "--no-interaction"], cwd=self.root, text=True, capture_output=True)
        self.assertEqual(result.returncode, 0, result.stdout + result.stderr)
        self.assertIn("OK (", result.stdout)
        self.assertFalse((self.root / "build").exists(), "Unit checks must not prepare release promotion assets")
        for slug in builder.SAMPLES:
            with zipfile.ZipFile(self.root / "archives" / f"{slug}.zip") as archive:
                self.assertEqual({name: archive.read(name) for name in archive.namelist()}, {
                    slug + "/" + path.relative_to(self.root / "samples" / slug).as_posix(): path.read_bytes()
                    for path in (self.root / "samples" / slug).rglob("*") if path.is_file()
                })

    def test_version_drift_fails(self):
        (self.root / "version.txt").write_text("9.0.0\n")
        with self.assertRaises(ValueError):
            builder.build(self.root, Path(self.temporary.name) / "bad", "a" * 40)

    def test_stale_outputs_and_symlink_archive_directory_fail(self):
        output = Path(self.temporary.name) / "bad"
        output.mkdir()
        (output / "stale-secret.txt").write_text("must never upload")
        with self.assertRaises(ValueError):
            builder.build(self.root, output, "a" * 40)
        (self.root / "archives").symlink_to(output, target_is_directory=True)
        with self.assertRaises(ValueError):
            builder.build(self.root, Path(self.temporary.name) / "elsewhere", "a" * 40)

    def test_symlinks_and_escape_inputs_fail(self):
        (self.root / "samples/scenario-plugin/escape.php").symlink_to(self.root / "catalog.php")
        with self.assertRaises(ValueError):
            builder.build(self.root, Path(self.temporary.name) / "bad", "a" * 40)
        (self.root / "samples/scenario-plugin/escape.php").unlink()
        (self.root / "release-contents.txt").write_text("../outside\n")
        with self.assertRaises(ValueError):
            builder.build(self.root, Path(self.temporary.name) / "bad", "a" * 40)

    def test_workflows_keep_readonly_quality_and_shared_promotion(self):
        quality = (ROOT / ".github/workflows/quality.yml").read_text()
        release = (ROOT / ".github/workflows/release-please.yml").read_text()
        self.assertIn("contents: read", quality)
        self.assertNotIn(": write", quality)
        self.assertIn("github.event.pull_request.head.sha || github.sha", quality)
        self.assertIn("composer check", quality)
        self.assertIn("pnpm check", quality)
        self.assertIn("scripts/core_release.py --output-zip build/core.zip", quality)
        self.assertIn("${{ github.run_id }}-${{ github.run_attempt }}", quality)
        self.assertIn("release-profile-b.yml@593768db30a0101e940e85b9a084b2c773322785", release)
        self.assertNotRegex(release, r"(?m)^\s*run:")
        self.assertNotIn("secrets: inherit", release)
        config = json.loads((ROOT / "release-please-config.json").read_text())
        self.assertTrue(config["draft"])
        self.assertTrue(config["force-tag-creation"])


if __name__ == "__main__":
    unittest.main()
