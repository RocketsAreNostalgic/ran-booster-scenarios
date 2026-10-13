"""Exercise copied skill entrypoints against real and conventional plugin headers."""

import json
from pathlib import Path
import re
import subprocess
import tempfile
import unittest

ROOT = Path(__file__).resolve().parent.parent
TRIAGE = ROOT / ".codex/skills/wp-project-triage/scripts/detect_wp_project.mjs"
PLUGINS = ROOT / ".codex/skills/wp-plugin-development/scripts/detect_plugins.mjs"


def detect(script, root):
    return json.loads(subprocess.check_output(["node", str(script)], cwd=root, text=True))


class SkillTest(unittest.TestCase):
    def test_real_plugin_is_discovered(self):
        self.assertIn("wp-plugin", detect(TRIAGE, ROOT)["project"]["kind"])
        plugin = next(item for item in detect(PLUGINS, ROOT)["plugins"]
                      if item["pluginFile"] == "ran-booster-scenarios.php")
        self.assertEqual(plugin["name"], "RAN Booster Scenarios (disposable sites only)")
        self.assertEqual(plugin["version"], json.loads((ROOT / "package.json").read_text())["version"])
        self.assertEqual(plugin["textDomain"], "ran-booster-scenarios")

    def test_starred_and_unstarred_headers(self):
        for prefix in ("", " * ", "\t*\t"):
            with self.subTest(prefix=prefix), tempfile.TemporaryDirectory() as temporary:
                root = Path(temporary)
                (root / "plugin.php").write_text(
                    "/**\n" + prefix + "Plugin Name: Example\n"
                    + prefix + "Version: 1.2.3\n" + prefix + "Text Domain: example\n */\n")
                self.assertIn("wp-plugin", detect(TRIAGE, root)["project"]["kind"])
                plugins = detect(PLUGINS, root)["plugins"]
                self.assertEqual(len(plugins), 1)
                self.assertEqual(plugins[0]["name"], "Example")
                self.assertEqual(plugins[0]["version"], "1.2.3")
                self.assertEqual(plugins[0]["textDomain"], "example")

    def test_documented_repository_paths_exist(self):
        paths = []
        for skill in ROOT.glob(".codex/skills/*/SKILL.md"):
            contents = skill.read_text()
            self.assertNotRegex(contents, r"`(?:node )?skills/")
            paths.extend(re.findall(r"`(?:node )?(\.codex/skills/[^` ]+)", contents))
        self.assertTrue(paths)
        for path in paths:
            self.assertTrue((ROOT / path).is_file(), path)


if __name__ == "__main__":
    unittest.main()
