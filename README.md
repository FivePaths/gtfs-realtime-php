# gtfs-realtime-php

[![CI](https://github.com/FivePaths/gtfs-realtime-php/actions/workflows/ci.yml/badge.svg)](https://github.com/FivePaths/gtfs-realtime-php/actions/workflows/ci.yml)
[![Packagist](https://img.shields.io/packagist/v/gtfs-media/gtfs-realtime-php)](https://packagist.org/packages/gtfs-media/gtfs-realtime-php)
[![Downloads](https://img.shields.io/packagist/dt/gtfs-media/gtfs-realtime-php)](https://packagist.org/packages/gtfs-media/gtfs-realtime-php/stats)
[![PHP](https://img.shields.io/packagist/dependency-v/gtfs-media/gtfs-realtime-php/php?label=php)](https://packagist.org/packages/gtfs-media/gtfs-realtime-php)
[![License](https://img.shields.io/packagist/l/gtfs-media/gtfs-realtime-php)](https://github.com/FivePaths/gtfs-realtime-php/blob/main/LICENSE)

PHP classes for reading and writing [GTFS Realtime](https://gtfs.org/documentation/realtime/reference/)
feeds — vehicle positions, trip updates, and service alerts. Generated from
the official protocol buffer definition, kept current with it, and built on a
protobuf runtime that receives security updates.

## Install

```
composer require gtfs-media/gtfs-realtime-php
```

Requires PHP 8.1 or later. The protobuf runtime dependency is
`google/protobuf ^4.33.6 || ^5.0` — both currently supported majors — so
Composer can settle on whichever version coexists with a project's other
dependencies.

## Read a feed

```php
use Google\Transit\Realtime\FeedMessage;

$feed = new FeedMessage();
$feed->mergeFromString(file_get_contents('https://example.com/vehiclepositions.pb'));

foreach ($feed->getEntity() as $entity) {
  if ($entity->hasVehicle()) {
    $vehicle = $entity->getVehicle();
    printf("%s at %.5f, %.5f\n",
      $vehicle->getVehicle()->getLabel(),
      $vehicle->getPosition()->getLatitude(),
      $vehicle->getPosition()->getLongitude());
  }
}
```

Every message type in the spec is here — `TripUpdate`, `VehiclePosition`,
`Alert`, and the newer additions like `TripModifications` — with getters,
setters, and `has*()` presence checks for each field. Field presence follows
the spec: a field a producer set to zero is set, and a field the producer
omitted is absent, even though a getter returns zero in both cases.

```php
use Google\Transit\Realtime\TripDescriptor\ScheduleRelationship;

foreach ($feed->getEntity() as $entity) {
  if (!$entity->hasTripUpdate()) {
    continue;
  }
  $update = $entity->getTripUpdate();
  if ($update->getTrip()->getScheduleRelationship() === ScheduleRelationship::CANCELED) {
    printf("trip %s is canceled\n", $update->getTrip()->getTripId());
  }
}
```

A whole feed converts to JSON in one call, which is how the binary feeds most
agencies publish become something a browser can use:

```php
$json = $feed->serializeToJsonString();
```

## Write a feed

Producers use the same classes in the other direction:

```php
use Google\Transit\Realtime\FeedHeader;
use Google\Transit\Realtime\FeedMessage;

$feed = new FeedMessage();
$feed->setHeader(
  (new FeedHeader())
    ->setGtfsRealtimeVersion('2.0')
    ->setTimestamp(time())
);
// ... add entities ...
file_put_contents('feed.pb', $feed->serializeToString());
```

## Performance

The pure-PHP protobuf runtime handles typical feeds without trouble. Very
large feeds — megabytes of trip updates for a whole network — cost real CPU
and memory there. Installing the PECL `protobuf` extension replaces the
runtime internals with the C implementation; no code changes, same classes.

## Migrating from lowa/gtfs-realtime-php

`lowa/gtfs-realtime-php` pins `google/protobuf` to exactly 3.25.0, which is
affected by [CVE-2026-6409](https://github.com/advisories/GHSA-p2gh-cfq4-4wjc),
a denial of service triggered by malicious protobuf messages — precisely the
untrusted input a GTFS Realtime consumer parses. The pin makes the fix
uninstallable, `composer audit` flags it, and Composer 2.8+ refuses to
install the package at all. This package is a drop-in replacement:

```
composer remove lowa/gtfs-realtime-php
composer require gtfs-media/gtfs-realtime-php
```

It also declares itself as a Composer `replace` for
`lowa/gtfs-realtime-php` 1.0.0, so a project that still requires the old
name through some other dependency resolves to this package once it is
required anywhere.

Class names match — everything lives in `Google\Transit\Realtime` — and
the legacy underscore names lowa shipped (`FeedHeader_Incrementality` and
friends) exist here as deprecated aliases of the nested classes, so code
written against either form runs unchanged. What to check:

- New code should use the nested form, `FeedHeader\Incrementality`; the
  underscore aliases exist for compatibility and carry a suppressed
  deprecation notice that tooling can surface.
- Field presence is tracked for every singular field. Values a producer
  explicitly sets to a default — `direction_id: 0`,
  `schedule_relationship: SCHEDULED`, `delay: 0` — survive parsing and
  appear in JSON output. lowa's conversion silently dropped them, so JSON
  from this package can contain keys the old package omitted. The values
  were always in the feed; nothing that was present before is missing now.
- The spec is current: `TripModifications`, `Stop`, `Shape`,
  `TranslatedImage`, and every field and enum value added upstream since
  early 2024.

Unchanged, for code relying on it: getters on **unset** fields return zero
values (`getCause()` gives `0`, not the spec default `UNKNOWN_CAUSE`),
because proto3 cannot express proto2's custom defaults. Check `has*()`
before trusting a getter, exactly as with lowa.

## How the package tracks the spec

The pristine official proto lives at `proto/upstream/gtfs-realtime.proto`,
pinned to the google/transit commit recorded next to it. A scripted,
assertion-guarded conversion produces the compiled proto — never edited by
hand — and `regenerate.sh` runs the whole chain with a pinned protoc
version. Committed snapshots of the API surface and of golden JSON output
turn every regeneration into a reviewable diff.

A weekly job watches upstream: a spec change arrives as a pull request with
the regeneration already done, a protoc release as a pull request bumping
the toolchain, and a security advisory as an issue. CI tests every release
against both runtime majors, at the dependency floor and ceiling, with and
without the C extension.

Version numbers follow from that: new spec surface is a minor release,
anything removed or any raised floor is a major, tooling and documentation
are patches. [RELEASING.md](https://github.com/FivePaths/gtfs-realtime-php/blob/main/RELEASING.md)
has the full procedure.

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

## Authorship

This package — code, tooling, tests, and documentation — was written by
Claude, Anthropic's AI model, supervised by a human maintainer who uses the
package professionally in production transit systems. Nothing merges or
ships without that person's review: the automation opens pull requests, a
human reads the diffs and decides.

## License

Apache-2.0, matching the GTFS Realtime protocol definition this package is
derived from.
