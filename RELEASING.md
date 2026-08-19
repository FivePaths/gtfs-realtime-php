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

## What the automation already does

CI (`.github/workflows/ci.yml`) runs on every push and PR: the test matrix
pins the constraint floor (PHP 8.1, protobuf 4.33.6) and ceiling (latest
5.x), one leg runs under the PECL C extension, and a reproducibility job
regenerates from the committed proto with the pinned protoc and fails on any
difference from src/ or the API-surface snapshot.

Maintenance (`.github/workflows/maintenance.yml`, Mondays + manual dispatch)
turns change into work items, so none of the watching below is done by hand:

- Upstream proto drift becomes a **pull request** with the sync complete —
  new pristine + SHA, regenerated code, rebuilt snapshots — and the diffs
  this document says to review, plus the in-job test result. Conversion
  failures (convert.php refusing an unknown construct) become an **issue**.
- A protoc patch release in the pinned minor becomes a **pull request**
  bumping PROTOC_VERSION, the runtime floor, and the regenerated code.
- A security advisory, a test failure against latest allowed dependencies,
  or a new protobuf major on Packagist becomes an **issue** stating the
  decision to make.

PRs opened by the workflow's own token do not trigger CI (GitHub blocks
recursive workflows); tests run inside the maintenance job and the result is
stated in the PR body. Push any commit to the branch, or close and reopen
the PR, to get a full CI run before merging.

## First publication

One-time setup. Everything after this is the tag-and-push loop below.

### 1. Create the GitHub repository

It must be **public** — Packagist cannot read a private repository without a
paid subscription, and the point of this package is that anyone can install it.

Create `fivepaths/gtfs-realtime-php` on GitHub with no README, no .gitignore
and no licence (the repository already has all three; letting GitHub add its
own creates an unrelated root commit you would have to merge past).

### 2. Push

```
git push -u origin main
git tag -a v1.0.0 -m "v1.0.0"
git push origin v1.0.0
```

The `v` prefix is conventional and Packagist strips it; the package version
will read `1.0.0`.

### 3. Submit to Packagist

Sign in at <https://packagist.org> — "Sign in with GitHub" is simplest, and it
is what lets Packagist install the update hook for you in step 4. Then go to
<https://packagist.org/packages/submit> and paste:

```
https://github.com/fivepaths/gtfs-realtime-php
```

Packagist clones the repository, reads `composer.json`, and names the package
from the `name` field — **`gtfs-media/gtfs-realtime-php`**, not the GitHub
org. That mismatch is fine and common; Packagist does not require the vendor
prefix to match the hosting account. The `gtfs-media` vendor namespace is
unclaimed, and submitting first claims it for this account.

### 4. Wire up automatic updates

Without this, Packagist only re-reads the repository on a slow crawl and new
tags can take hours to appear.

- **If you signed in with GitHub**, open the package page → *Settings*, and
  Packagist offers to install the hook itself. Accept it. Done.
- **Otherwise**, add it by hand: GitHub repo → *Settings* → *Webhooks* →
  *Add webhook*.
  - Payload URL: `https://packagist.org/api/github?username=<your-packagist-username>`
  - Content type: `application/json`
  - Secret: your Packagist API token, from <https://packagist.org/profile/>
  - Events: *Just the push event*

Confirm it works by checking the package page shows `1.0.0` within a minute or
two of the tag push, and that GitHub's webhook delivery log shows a 202.

### 5. Lock the vendor down

On <https://packagist.org/profile/>, enable two-factor authentication, then on
the package's *Settings* tab require 2FA for the vendor. A package that other
sites install is worth the two minutes.

### 6. Verify a real install

From a scratch directory, prove the thing the CVE blocked actually works now:

```
composer require gtfs-media/gtfs-realtime-php
composer audit
```

`composer audit` must report no advisories. That is the whole reason this
package exists — `lowa/gtfs-realtime-php` pins `google/protobuf` to exactly
3.25.0, which carries CVE-2026-6409, and Composer 2.8+ refuses to install it.

### 7. Switch the consumers

- `drupal/gtfs_rt`: replace `lowa/gtfs-realtime-php: ^v1` with
  `gtfs-media/gtfs-realtime-php: ^1.0` in `composer.json`, and update the
  Requirements section of its README and drupal.org project page.
- The SFMTA site: `composer update` to move off the vulnerable pin. This is a
  live exposure independent of the drupal.org release — `gtfs_rt` decodes
  protobuf fetched from a feed it does not control, which is exactly the
  attack surface CVE-2026-6409 describes.

## Publishing mechanics

Tag `vX.Y.Z`, push with tags. Packagist picks up new tags via the repository
webhook; verify the new version appears on the package page within minutes.
`composer.lock` is intentionally untracked (library), and `.gitattributes`
keeps tests, fixtures, and tooling out of the dist zip.
