#!/bin/sh
# Regenerates src/ from the pristine upstream proto. The full flow:
#
#   1. proto/upstream/gtfs-realtime.proto  — pristine copy of the official
#      file, at the google/transit commit named in proto/upstream/UPSTREAM_COMMIT.
#      To sync the spec, replace the file and the SHA together, then rerun this.
#   2. tools/convert.php                   — deterministic proto2 -> proto3
#      transform; fails loudly on anything it has not seen before.
#   3. protoc --php_out                    — with the exact version pinned in
#      PROTOC_VERSION. The protoc version decides which google/protobuf majors
#      the gencode may run on (see README); do not bump it casually.
#
# After regenerating, rebuild the snapshots and review their diffs — that
# review is the release gate, see RELEASING.md:
#
#   php tools/make-fixtures.php
#   php tools/api-surface.php > tests/api-surface.txt
#   composer test
set -e
cd "$(dirname "$0")"

WANT="$(cat PROTOC_VERSION)"
GOT="$(protoc --version 2>/dev/null | awk '{print $2}')" || {
  echo "protoc not found; install version $WANT" >&2; exit 1;
}
if [ "$GOT" != "$WANT" ]; then
  echo "protoc $GOT found, but this package pins $WANT (see PROTOC_VERSION)." >&2
  echo "Official binaries: https://github.com/protocolbuffers/protobuf/releases/tag/v$WANT" >&2
  exit 1
fi

php tools/convert.php
rm -rf src
mkdir src
protoc --proto_path=proto --php_out=src proto/gtfs-realtime.proto
echo "Generated $(find src -name '*.php' | wc -l | tr -d ' ') classes with protoc $GOT from upstream $(cat proto/upstream/UPSTREAM_COMMIT)."
