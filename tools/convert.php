<?php

/**
 * Converts the pristine upstream proto2 file (proto/upstream/gtfs-realtime.proto)
 * into the proto3 file protoc actually compiles (proto/gtfs-realtime.proto).
 *
 * protoc cannot generate PHP from proto2 (required fields, closed enums), and
 * its editions support for PHP begins with protoc 34 — the protobuf-PHP major-5
 * era. Generating with a 4-era protoc (<= 33.x) keeps the package installable
 * on both google/protobuf ^4.33.6 and ^5.0, so until protobuf 4.x leaves
 * support this package targets proto3. See README.md for the full rationale
 * and the list of transformations.
 *
 * Every assumption about the upstream file is asserted. When upstream changes
 * in a way this script has not seen before, it must fail loudly, not guess.
 */

$upstream_path = __DIR__ . '/../proto/upstream/gtfs-realtime.proto';
$output_path = __DIR__ . '/../proto/gtfs-realtime.proto';

$fail = function (string $message): never {
  fwrite(STDERR, "convert.php: $message\n");
  fwrite(STDERR, "Upstream changed in a way this script does not understand. Update tools/convert.php deliberately.\n");
  exit(1);
};

$proto = file_get_contents($upstream_path);
$proto !== FALSE || $fail("cannot read $upstream_path");

// 1. proto2 -> proto3. The file must still be what we think it is.
str_contains($proto, 'syntax = "proto2";') || $fail('expected syntax = "proto2"');
str_contains($proto, 'package transit_realtime;') || $fail('expected package transit_realtime');
!preg_match('/^\s*(group\s|oneof\s)/m', $proto) || $fail('groups/oneofs appeared upstream; transform rules do not cover them');
$proto = str_replace('syntax = "proto2";', 'syntax = "proto3";', $proto);

// 2. Namespace the generated code where lowa/gtfs-realtime-php consumers
// expect it. The proto package itself stays transit_realtime, matching
// upstream on the wire and in descriptors.
$proto = str_replace(
  "package transit_realtime;\n",
  "package transit_realtime;\n"
  . "option php_namespace = \"Google\\\\Transit\\\\Realtime\";\n"
  . "option php_metadata_namespace = \"GPBMetadata\";\n",
  $proto
);

// 3. required -> optional. Identical wire format; proto2 required has no
// enforcement in the PHP runtime anyway.
$proto = preg_replace('/^(\s*)required /m', '$1optional ', $proto, -1, $required_count);
$required_count > 0 || $fail('no required fields found; expected a few');

// 4. Keep every singular field explicitly `optional`: in proto3 that label
// means tracked field presence, which preserves proto2 semantics — values
// explicitly set to a default survive serialization. (lowa stripped the
// labels, silently dropping such values.) proto2 syntax guarantees every
// singular field already carries a label, so there is nothing to add.

// 5. Custom defaults are proto2-only; proto3 rejects them. Their loss changes
// what getters return for UNSET fields (0 instead of the spec default) but
// not the wire format or JSON output. Restored when this package moves to an
// editions source. Options are single-item brackets in upstream; a combined
// list like [default = X, deprecated = true] needs a new rule.
!preg_match('/\[[^\]]*,[^\]]*\]/', $proto) || $fail('multi-option bracket found; strip rules assume single-option brackets');
$proto = preg_replace('/\s*\[default = [^\]]+\]/', '', $proto, -1, $default_count);
$default_count > 0 || $fail('no [default = ...] options found; expected several');

// 6. proto3 has no extension declarations. Data in these ranges still
// round-trips: the runtime preserves unknown fields, and the PHP runtime has
// no extension API that could have read them anyway.
$proto = preg_replace('/^\s*extensions \d+ to \d+;\n/m', '', $proto, -1, $ext_count);
$ext_count > 0 || $fail('no extension ranges found; expected many');

// 7. proto3 enums must start at zero. Three Alert enums start at 1 upstream;
// give each the zero sentinel lowa's conversion used, keeping the constant
// names consumers may already reference. Spec-conforming feeds never carry
// these values. If upstream ever defines its own zero value here, the
// sentinel must yield to it — that is what the assertions catch.
$sentinels = [
  'Cause' => ['PROTO3_DEFAULT_CAUSE', 'UNKNOWN_CAUSE = 1;'],
  'Effect' => ['PROTO3_DEFAULT_EFFECT', 'NO_SERVICE = 1;'],
  'SeverityLevel' => ['PROTO3_DEFAULT_SEVERITY', 'UNKNOWN_SEVERITY = 1;'],
];
foreach ($sentinels as $enum => [$sentinel, $expected_first]) {
  preg_match('/enum ' . $enum . ' \{\n(\s*)([A-Z_]+ = \d+;)/', $proto, $m) || $fail("enum $enum not found where expected");
  $m[2] === $expected_first || $fail("enum $enum no longer starts with '$expected_first' (found '$m[2]'); check whether upstream added a zero value");
  $proto = str_replace("enum $enum {\n", "enum $enum {\n$m[1]$sentinel = 0;\n", $proto);
}

// 8. Nothing proto2-only may remain (as declarations; comments may still
// discuss required fields and extensions).
!preg_match('/^\s*(required |extensions \d)/m', $proto) && !str_contains($proto, '[default')
  || $fail('proto2 constructs survived the transform');

file_put_contents($output_path, $proto) !== FALSE || $fail("cannot write $output_path");
printf("Wrote %s from upstream %s (%d required relabeled, %d defaults stripped, %d extension ranges removed).\n",
  $output_path,
  trim(file_get_contents(__DIR__ . '/../proto/upstream/UPSTREAM_COMMIT')),
  $required_count,
  $default_count,
  $ext_count
);
