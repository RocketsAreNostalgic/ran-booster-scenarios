"""Fixed inert Blueprint V2 inputs for genuine Transporter upload journeys.

These archives carry configuration, never credentials or executable definitions.
They follow Core's canonical JSON/single-entry ZIP format; they do not run Core,
change management or establish installed equality. Password cases require a real
Core export using its AES-256 archive writer and remain manual prerequisites.
"""

import io
import json
from pathlib import Path
import tempfile
import zipfile


COMMIT = "a" * 40
BLUEPRINT_FILES = (
    'blueprint-branch-plugin.zip', 'blueprint-branch-theme.zip', 'blueprint-branch-mixed.zip',
    'blueprint-selected-plugin.zip', 'blueprint-selected-theme.zip', 'blueprint-selected-mixed.zip',
    'blueprint-unsupported-release.zip', 'blueprint-mixed-partial.zip',
    'blueprint-malformed.zip', 'blueprint-unsupported-version.zip',
)


def _package(kind: str, selected: bool = False, release: bool = False) -> dict:
    slug, identifier, repository_id = {
        "plugin": ("scenario-plugin", "scenario-plugin/scenario-plugin.php", "940001"),
        "theme": ("scenario-theme", "scenario-theme", "940002"),
    }[kind]
    package = {
        "type": kind,
        "identifier": identifier,
        "display_name": "Booster Scenario Target",
        "provider": "gh",
        "provider_repository_id": repository_id,
        "repository": "ran-booster-scenarios/" + slug,
        "source": {
            "kind": "release_asset", "channel": "stable", "package_root": slug,
            "metadata_file": "scenario-plugin.php" if kind == "plugin" else "style.css",
        } if release else {"kind": "branch", "branch": "main", "subdirectory": None},
    }
    if selected:
        package["selection"] = {"commit": COMMIT}
        package["requirements"] = {
            "basis": "selected_package",
            "observed_identity": {"commit": COMMIT, "version": "2.0.0"},
            "declarations": {
                "requires_php": "8.4", "requires_wordpress": "7.0",
                "requires_plugins" if kind == "plugin" else "parent_theme": [] if kind == "plugin" else None,
            },
        }
    return package


def _document(packages: list[dict], version: int = 2) -> bytes:
    return json.dumps({
        "format": "ran-booster-package-blueprint", "version": version,
        "packages": sorted(packages, key=lambda item: (item["type"], item["identifier"])),
        "credentials": [],
    }, ensure_ascii=False, sort_keys=True, separators=(",", ":")).encode("utf-8")


def _archive(document: bytes) -> bytes:
    output = io.BytesIO()
    with zipfile.ZipFile(output, "w", compression=zipfile.ZIP_STORED) as archive:
        entry = zipfile.ZipInfo("blueprint.json", date_time=(2026, 1, 1, 0, 0, 0))
        entry.create_system = 3
        entry.external_attr = 0o100644 << 16
        archive.writestr(entry, document)
    return output.getvalue()


def build_blueprints(root: Path) -> dict[str, bytes]:
    """Write fixed named ZIPs into root/archives; return filename-to-ZIP bytes.

    Branch fixtures can exercise initial installation or existing-target review.
    Selected fixtures require absent targets: Core refuses selected adoption.
    Release fixtures are structurally valid, but execution remains unsupported;
    the mixed fixture retains its blocked row beside a selected branch row.
    """
    documents = {}
    for selected, prefix in ((False, "branch"), (True, "selected")):
        for kind in ("plugin", "theme"):
            documents[f"blueprint-{prefix}-{kind}.zip"] = _document([_package(kind, selected)])
        documents[f"blueprint-{prefix}-mixed.zip"] = _document([
            _package("plugin", selected), _package("theme", selected),
        ])
    documents["blueprint-unsupported-release.zip"] = _document([_package("plugin", release=True)])
    documents["blueprint-mixed-partial.zip"] = _document([
        _package("plugin", selected=True), _package("theme", release=True),
    ])
    documents["blueprint-malformed.zip"] = b'{"format":'
    documents["blueprint-unsupported-version.zip"] = _document([_package("plugin")], version=999)
    outputs = {name: _archive(document) for name, document in documents.items()}
    if set(outputs) != set(BLUEPRINT_FILES):
        raise ValueError('Generated Blueprint inventory differs from its fixed declaration')
    directory = root / "archives"
    if root.is_symlink() or not root.is_dir() or directory.is_symlink():
        raise ValueError("Blueprint output requires a regular source directory")
    for name in outputs:
        path = directory / name
        if path.is_symlink() or (path.exists() and not path.is_file()):
            raise ValueError("Unsafe Blueprint archive destination")
    directory.mkdir(exist_ok=True)
    for name, data in outputs.items():
        temporary = None
        try:
            with tempfile.NamedTemporaryFile(dir=directory, prefix=".blueprint-", delete=False) as stream:
                temporary = Path(stream.name)
                stream.write(data)
            temporary.replace(directory / name)
        finally:
            if temporary is not None:
                temporary.unlink(missing_ok=True)
    return outputs
