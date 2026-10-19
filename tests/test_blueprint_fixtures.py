"""Archive custody, canonical source contracts and negative Transporter inputs."""

import importlib.util
import io
import json
import os
from pathlib import Path
import re
import tempfile
import unittest
import zipfile


ROOT = Path(__file__).resolve().parent.parent
SPEC = importlib.util.spec_from_file_location("blueprints", ROOT / "scripts/blueprint_fixtures.py")
assert SPEC and SPEC.loader
blueprints = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(blueprints)


class BlueprintFixtureTest(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory()
        self.addCleanup(self.temporary.cleanup)
        self.root = Path(self.temporary.name)
        self.outputs = blueprints.build_blueprints(self.root)

    def document(self, name):
        with zipfile.ZipFile(io.BytesIO(self.outputs[name])) as archive:
            self.assertEqual(archive.namelist(), ["blueprint.json"])
            entry = archive.getinfo("blueprint.json")
            self.assertEqual(entry.flag_bits & 1, 0, "No credential/encryption simulation")
            self.assertLess(entry.file_size, 262144)
            return archive.read("blueprint.json")

    def test_exact_fixed_inventory_and_reproducible_archive_bytes(self):
        self.assertEqual(set(self.outputs), {
            "blueprint-branch-plugin.zip", "blueprint-branch-theme.zip", "blueprint-branch-mixed.zip",
            "blueprint-selected-plugin.zip", "blueprint-selected-theme.zip", "blueprint-selected-mixed.zip",
            "blueprint-unsupported-release.zip", "blueprint-mixed-partial.zip",
            "blueprint-malformed.zip", "blueprint-unsupported-version.zip",
        })
        self.assertEqual(self.outputs, blueprints.build_blueprints(self.root))
        self.assertEqual(self.outputs, {path.name: path.read_bytes() for path in (self.root / "archives").iterdir()})

    def test_real_core_json_shape_canonical_order_and_reserved_targets(self):
        for name in self.outputs:
            with self.subTest(name=name):
                raw = self.document(name)
                if name == "blueprint-malformed.zip":
                    with self.assertRaises(json.JSONDecodeError):
                        json.loads(raw)
                    continue
                document = json.loads(raw)
                self.assertEqual(set(document), {"credentials", "format", "packages", "version"})
                self.assertEqual(document["format"], "ran-booster-package-blueprint")
                self.assertEqual(document["credentials"], [])
                self.assertEqual(document["version"], 999 if name == "blueprint-unsupported-version.zip" else 2)
                self.assertEqual(raw, json.dumps(document, ensure_ascii=False, sort_keys=True, separators=(",", ":")).encode())
                self.assertTrue(raw.startswith(b'{"credentials":[],"format":'))
                for package in document["packages"]:
                    slug = "scenario-" + package["type"]
                    self.assertEqual(package["repository"], "ran-booster-scenarios/" + slug)
                    self.assertEqual(package["identifier"], slug + "/scenario-plugin.php" if package["type"] == "plugin" else slug)
                    self.assertEqual(package["provider"], "gh")
                    self.assertIn(package["provider_repository_id"], ("940001", "940002"))

    def test_selected_observations_match_bundled_source_headers(self):
        for package in json.loads(self.document("blueprint-selected-mixed.zip"))["packages"]:
            kind = package["type"]
            header = "scenario-plugin.php" if kind == "plugin" else "style.css"
            sample = (ROOT / "samples" / ("scenario-" + kind) / header).read_text()
            requirements = package["requirements"]
            self.assertEqual(package["source"], {"kind": "branch", "branch": "main", "subdirectory": None})
            self.assertEqual(package["selection"], {"commit": "a" * 40})
            self.assertEqual(requirements["basis"], "selected_package")
            self.assertEqual(requirements["observed_identity"]["commit"], package["selection"]["commit"])
            for label, value in (("Version", requirements["observed_identity"]["version"]),
                                 ("Requires PHP", requirements["declarations"]["requires_php"]),
                                 ("Requires at least", requirements["declarations"]["requires_wordpress"])):
                self.assertRegex(sample, r"(?m)^\s*(?:\*\s*)?" + label + r":\s*" + re.escape(value) + r"\s*$")
            self.assertNotRegex(sample, r"(?m)^\s*(?:\*\s*)?(?:Requires Plugins|Template):")

    def test_partial_fixture_preserves_supported_and_unsupported_source_rows(self):
        packages = json.loads(self.document("blueprint-mixed-partial.zip"))["packages"]
        self.assertEqual(len(packages), 2)
        self.assertEqual(packages[0]["source"]["kind"], "branch")
        self.assertEqual(packages[0]["selection"], {"commit": "a" * 40})
        self.assertEqual(packages[1]["source"], {
            "kind": "release_asset", "channel": "stable", "package_root": "scenario-theme", "metadata_file": "style.css",
        })
        self.assertNotIn("selection", packages[1])

    def test_symlink_destinations_refuse_without_changing_their_targets(self):
        archive = self.root / "archives/blueprint-selected-plugin.zip"
        victim = self.root / "unrelated.txt"
        victim.write_bytes(b"retain")
        archive.unlink()
        archive.symlink_to(victim)
        with self.assertRaises(ValueError):
            blueprints.build_blueprints(self.root)
        self.assertEqual(victim.read_bytes(), b"retain")
        archive.unlink()
        for path in (self.root / "archives").iterdir():
            path.unlink()
        (self.root / "archives").rmdir()
        (self.root / "archives").symlink_to(self.root, target_is_directory=True)
        with self.assertRaises(ValueError):
            blueprints.build_blueprints(self.root)

    def test_existing_hardlink_is_replaced_without_mutating_unrelated_file(self):
        archive = self.root / "archives/blueprint-selected-plugin.zip"
        victim = self.root / "unrelated.txt"
        victim.write_bytes(b"retain")
        archive.unlink()
        os.link(victim, archive)
        blueprints.build_blueprints(self.root)
        self.assertEqual(victim.read_bytes(), b"retain")
        self.assertEqual(archive.read_bytes(), self.outputs[archive.name])


if __name__ == "__main__":
    unittest.main()
