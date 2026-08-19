# Changelog

## 1.1.0 — unreleased

- composer.json now declares this package as a `replace` for
  `lowa/gtfs-realtime-php` 1.0.0: projects that still require the old
  name accept this package in its place, and the Packagist page states
  the relationship.
- The 17 legacy underscore class names lowa shipped
  (`FeedHeader_Incrementality`, `TripUpdate_StopTimeUpdate`, and friends)
  are available again as deprecated aliases of the nested classes, making
  the swap a drop-in even for code written against the old names. Each
  alias emits a suppressed `E_USER_DEPRECATED` that deprecation tooling
  can surface.

## 1.0.0 — 2026-08-18

First release. PHP classes for GTFS Realtime, generated with protoc 33.6
from the official definition at google/transit commit `474750a1`
(2026-08-17), converted to proto3 as documented in README.md.

Replaces `lowa/gtfs-realtime-php` 1.0.0, whose exact pin of
`google/protobuf` 3.25.0 is affected by CVE-2026-6409 (denial of service
through malicious messages). Differences from that package:

- Requires `google/protobuf` `^4.33.6 || ^5.0` — patched, and free to take
  future security updates.
- Explicit field presence: values producers explicitly set to a default
  (`direction_id: 0`, `schedule_relationship: SCHEDULED`) survive parsing
  and reappear in JSON output instead of being dropped.
- Covers the current spec, adding `TripModifications`, `Stop`,
  `StopSelector`, `Shape`, `ReplacementStop`, `TranslatedImage`, and all
  fields and enum values added upstream since early 2024.
- Legacy underscore class names (`FeedHeader_Incrementality`) are gone;
  use the nested form (`FeedHeader\Incrementality`).
