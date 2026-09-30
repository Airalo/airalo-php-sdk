#!/usr/bin/env bash
# PostToolUse hook: syntax-check PHP files after Claude edits them.
# Exit 2 feeds the lint error back to Claude so it fixes it; anything else is silent.

file_path=$(php -r '$d = json_decode(stream_get_contents(STDIN), true); echo $d["tool_input"]["file_path"] ?? "";')

case "$file_path" in
  *.php) ;;
  *) exit 0 ;;
esac

[ -f "$file_path" ] || exit 0

if ! output=$(php -l "$file_path" 2>&1); then
  echo "php -l failed for $file_path:" >&2
  echo "$output" >&2
  exit 2
fi

exit 0
