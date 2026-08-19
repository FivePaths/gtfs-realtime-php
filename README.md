# gtfs-realtime-php

[![CI](https://github.com/FivePaths/gtfs-realtime-php/actions/workflows/ci.yml/badge.svg)](https://github.com/FivePaths/gtfs-realtime-php/actions/workflows/ci.yml)

PHP bindings for [GTFS Realtime](https://gtfs.org/documentation/realtime/reference/),
generated from the official protocol buffer definition, with a protobuf
runtime that receives security updates.

This package is a maintained replacement for `lowa/gtfs-realtime-php`, which
pins `google/protobuf` to 3.25.0. That version is affected by CVE-2026-6409,
a denial of service triggered by malicious protobuf messages — exactly the
kind of untrusted input a GTFS Realtime consumer parses. Composer 2.8+
refuses to install it.

## Install

```
composer require gtfs-media/gtfs-realtime-php
```

Requires PHP 8.1+ and installs `google/protobuf` at `^4.33.6 || ^5.0`,
whichever fits your project's other constraints. For large feeds (megabytes
of TripUpdates), the pure-PHP runtime is slow and memory-hungry; installing
the PECL `protobuf` extension removes both costs without code changes.

## Use

```php
use Google\Transit\Realtime\FeedMessage;

$feed = new FeedMessage();
$feed->mergeFromString(file_get_contents('https://example.com/vehicle-positions.pb'));

foreach ($feed->getEntity() as $entity) {
  if ($entity->hasVehicle()) {
    $position = $entity->getVehicle()->getPosition();
    printf("%s at %f, %f\n",
      $entity->getId(),
      $position->getLatitude(),
      $position->getLongitude());
  }
}

// Or convert the whole feed to JSON.
$json = $feed->serializeToJsonString();
```

## Compatibility with lowa/gtfs-realtime-php

Class names match: everything lives in `Google\Transit\Realtime`, so
replacing the package needs no code changes for typical use. What changes:

- The legacy underscore class names (`FeedHeader_Incrementality` and
  friends) are gone. Current protoc no longer generates them. Use the
  nested form, `FeedHeader\Incrementality`.
- Field presence is tracked for every singular field. Values a producer
  explicitly sets to a default — `direction_id: 0`,
  `schedule_relationship: SCHEDULED`, `delay: 0` — survive parsing and
  appear in JSON output. lowa's conversion silently dropped them, so JSON
  from this package can contain keys the old package omitted; the values
  were always in the feed.
- The spec is current: `TripModifications`, `Stop`, `Shape`,
  `TranslatedImage`, and everything else added upstream since 2024 is here.

Unchanged, for consumers relying on it: getters on **unset** fields return
zero values (`getCause()` gives `0`, not the spec default `UNKNOWN_CAUSE`),
because proto3 cannot express proto2's custom defaults. Check `has*()`
before trusting a getter, as with lowa. The planned v2 (see RELEASING.md)
restores spec defaults.

## How this package tracks the spec

The pristine official file lives at `proto/upstream/gtfs-realtime.proto`,
pinned to the google/transit commit recorded in
`proto/upstream/UPSTREAM_COMMIT`. The compiled `proto/gtfs-realtime.proto`
is produced from it by `tools/convert.php` — never edited by hand — and
`./regenerate.sh` runs the whole chain with the protoc version pinned in
`PROTOC_VERSION`. Committed snapshots (`tests/api-surface.txt`,
`tests/fixtures/*.json`) make every regeneration's behavior change visible
as a reviewable diff. RELEASING.md has the full procedure and versioning
rules.

## Changes from the official proto

The official definition uses proto2, which protoc cannot compile to PHP —
`required` fields and closed enums are unsupported, and the PHP generator
only accepts the successor "editions" format from protoc 34 on, whose
output would confine the package to `google/protobuf` 5.x. To run on both
supported runtime majors, `tools/convert.php` derives a proto3 file instead:

1. `syntax` becomes `proto3`; every singular field keeps an explicit
   `optional` label, which in proto3 means tracked presence — preserving
   proto2's behavior for values explicitly set to a default.
2. `required` labels (wire-identical to `optional`, unenforced in the PHP
   runtime anyway) become `optional`.
3. `option php_namespace` / `php_metadata_namespace` place generated code
   in `Google\Transit\Realtime` and `GPBMetadata`.
4. Custom defaults (`[default = UNKNOWN_CAUSE]`) are dropped — proto3
   forbids them. This affects only what getters return for unset fields,
   never the wire format or JSON output.
5. Extension range declarations are dropped — proto3 forbids them, and the
   PHP runtime has no extension API. Extension data in feeds still
   round-trips as unknown fields (covered by a test).
6. `Alert.Cause`, `Alert.Effect`, and `Alert.SeverityLevel` gain a zero
   value (`PROTO3_DEFAULT_CAUSE`, `PROTO3_DEFAULT_EFFECT`,
   `PROTO3_DEFAULT_SEVERITY`) because proto3 enums require one. The names
   match lowa's. These values never appear in spec-conforming feeds.

The proto package stays `transit_realtime`, so descriptors and any
cross-language tooling see the official names.

## License

Apache-2.0, matching the GTFS Realtime protocol definition this package is
derived from.
