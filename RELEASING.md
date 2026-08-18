# Releasing

## Syncing a spec change

Upstream changes land as pull requests on google/transit, announced on the
[GTFS Realtime Google Group](https://groups.google.com/forum/#!forum/gtfs-realtime)
with a minimum seven-day discussion before any vote — watch either, or the
commit feed for the file itself:
<https://github.com/google/transit/commits/master/gtfs-realtime/proto/gtfs-realtime.proto.atom>.
The repo cuts no releases and no usable tags. Changes are slow (one to three
substantive ones a year; many years fewer) and the amendment process requires
them to be backwards-compatible: fields are added, deprecated in place, never
removed or renumbered.

1. Download the new pristine file to `proto/upstream/gtfs-realtime.proto` and
   put the google/transit commit SHA it came from in
   `proto/upstream/UPSTREAM_COMMIT`.
2. `./regenerate.sh` — runs the conversion (which fails loudly if upstream
   did something the transform has no rule for) and protoc.
3. `php tools/make-fixtures.php && php tools/api-surface.php > tests/api-surface.txt`
4. `composer test`, then read two diffs before anything else:
   - `tests/api-surface.txt` — the BC contract. Added lines are the new spec
     surface; any removed or changed line is a compatibility break and needs
     a deliberate decision.
   - `tests/fixtures/*.json` — observable serialization behavior. A spec sync
     that only adds fields should not change these at all.
5. Test against both runtime majors:
   `composer update --prefer-lowest && composer test`, then
   `composer update && composer test`.
6. CHANGELOG entry citing the upstream commit range, then tag.

## Version rules

- New upstream fields, messages, or enum values: **minor**.
- Removed or renamed anything in `api-surface.txt`, or a raised
  `google/protobuf` floor, or a raised PHP floor: **major**.
- Runtime-constraint widening (e.g. appending `|| ^6.0`), regeneration with
  no surface change, doc/tooling changes: **patch**.

## The protoc / runtime coupling

The protoc version (pinned in `PROTOC_VERSION`) decides which google/protobuf
majors consumers may install. Protobuf's rule: gencode of major V runs on
runtimes V and V+1, never on a runtime older than the generating protoc, and
never on V+2. PHP majors map as: protoc 26.x–33.x → PHP runtime 4.x,
protoc 34.x+ → PHP runtime 5.x. PHP has no poison pill, so an illegal pairing
loads and misbehaves later instead of failing fast — the constraint in
composer.json is the only guard. Hence:

- protoc 33.6 (current) → gencode major 4 → constraint `^4.33.6 || ^5.0`.
  The floor 4.33.6 is protoc's own version and the CVE-2026-6409 fix.
- When protobuf 6.x runtimes ship (majors have landed every ~2 years, Q1),
  append `|| ^6.0` after testing — major-4 gencode is not supported there,
  so that event forces the move below.

## The planned editions move (v2)

The proto3 source is a workaround for protoc ≤ 33 lacking PHP editions
support. Regenerating from an editions-2023 conversion with protoc 34+ gives
back proto2's custom defaults (getters on unset fields return spec defaults
like `UNKNOWN_CAUSE` instead of 0) and cuts pure-PHP-runtime memory roughly
5× on large feeds — but the gencode becomes major-5, dropping every
`google/protobuf` 4.x consumer and requiring PHP ≥ 8.2. Do this as **v2.0.0**
when protobuf 4.x support ends (scheduled around Q1 2027) or when 6.x forces
a regeneration, whichever comes first. The editions transform was validated
against protoc 34.1 before v1 shipped; see git history of
`proto/gtfs-realtime.proto` for the working conversion.

## Publishing mechanics

Tag `vX.Y.Z`, push with tags. Packagist picks up new tags via the repository
webhook; verify the new version appears on the package page within minutes.
`composer.lock` is intentionally untracked (library), and `.gitattributes`
keeps tests, fixtures, and tooling out of the dist zip.
