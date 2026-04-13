#!/usr/bin/env python3
import os
import zipfile

plugin_dir = "slow-plugin-scanner"
output_file = "build/slow-plugin-scanner.zip"
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

os.makedirs("build", exist_ok=True)

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
