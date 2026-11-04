#!/usr/bin/env bash
# Puts another version's files in panel/plugins/simplify-hosting the way the Hub's update does
# (PluginService::downloadPluginFromUrl): the new files, with the installed copy's meta (its status) kept.
# Usage, from the folder that holds panel/: bash swap.sh <folder with the other version>
set -euo pipefail
dest=panel/plugins/simplify-hosting
meta="$(php -r '$d = json_decode((string) @file_get_contents($argv[1]), true); echo json_encode($d["meta"] ?? []);' "$dest/plugin.json")"
rm -rf "$dest"
mkdir -p "$dest"
tar -C "$1" --exclude=.git --exclude=dist -cf - . | tar -C "$dest" -xf -
php -r '$f = $argv[1]; $d = json_decode(file_get_contents($f), true); $m = json_decode($argv[2], true); if ($m) { $d["meta"] = $m; } else { unset($d["meta"]); } file_put_contents($f, json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);' "$dest/plugin.json" "$meta"
