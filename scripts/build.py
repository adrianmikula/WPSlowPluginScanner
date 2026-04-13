#!/usr/bin/env python3
import os
import zipfile

script_dir = os.path.dirname(os.path.abspath(__file__))
project_root = os.path.dirname(script_dir)

plugin_dir = os.path.join(project_root, "slow-plugin-scanner")

env_file = os.path.join(project_root, "slow-plugin-scanner", ".env")
mode = "free"
if os.path.exists(env_file):
    with open(env_file) as f:
        for line in f:
            if line.startswith("PIA_MODE="):
                mode = line.split("=")[1].strip()

output_file = os.path.join(project_root, f"build/slow-plugin-scanner-{mode}.zip")
exclude_patterns = {
    ".gitignore",
    ".distignore",
    ".phpunit.result.cache",
    "composer-setup.php",
    ".phpunit.xml",
    "composer.json",
    "composer.lock",
    "README.md",
}
exclude_dirs = {"tests", "vendor", ".git"}

os.makedirs(os.path.join(project_root, "build"), exist_ok=True)

with zipfile.ZipFile(output_file, "w", zipfile.ZIP_DEFLATED) as zf:
    for root, dirs, files in os.walk(plugin_dir):
        dirs[:] = [d for d in dirs if d not in exclude_dirs]

        for file in files:
            if file in exclude_patterns:
                continue
            filepath = os.path.join(root, file)
            arcname = os.path.relpath(filepath, plugin_dir)
            zf.write(filepath, arcname)
            print(f"Added: {arcname}")

print(f"\nBuilt: {output_file}")
