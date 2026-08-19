<?php

namespace GtfsMedia\GtfsRealtime\Tests;

use Google\Transit\Realtime\Alert;
use Google\Transit\Realtime\Alert\Cause;
use Google\Transit\Realtime\FeedMessage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FeedMessageTest extends TestCase {

  private const FIXTURES = __DIR__ . '/fixtures';

  public static function fixtures(): array {
    return [
      'alert feed' => [__DIR__ . '/../examples/ExampleAlert.pb', self::FIXTURES . '/ExampleAlert.json'],
      'mixed feed' => [self::FIXTURES . '/feed.pb', self::FIXTURES . '/feed.json'],
    ];
  }

  /**
   * The golden files pin the exact JSON every fixture serializes to. A diff
   * here after regenerating src/ means observable behavior changed; review
   * it before regenerating the goldens with tools/make-fixtures.php.
   *
   * JSON key order and binary field order are runtime implementation
   * details: the goldens record the pure-PHP runtime's output byte for
   * byte, and under the C extension the comparison is order-normalized.
   */
  #[DataProvider('fixtures')]
  public function testJsonMatchesGolden(string $pb, string $golden): void {
    $feed = new FeedMessage();
    $feed->mergeFromString(file_get_contents($pb));
    if (!extension_loaded('protobuf')) {
      $this->assertSame(file_get_contents($golden), $feed->serializeToJsonString() . "\n");
    }
    else {
      $this->assertSameJson(file_get_contents($golden), $feed->serializeToJsonString());
    }
  }

  /**
   * Parse -> serialize must lose nothing. The pure-PHP runtime's output is
   * additionally pinned byte for byte; the C extension orders fields
   * differently, so there the proof is equal length plus identical decoded
   * content.
   */
  #[DataProvider('fixtures')]
  public function testBinaryRoundTrip(string $pb, string $golden): void {
    $bytes = file_get_contents($pb);
    $feed = new FeedMessage();
    $feed->mergeFromString($bytes);
    $reserialized = $feed->serializeToString();
    if (!extension_loaded('protobuf')) {
      $this->assertSame($bytes, $reserialized);
    }
    $this->assertSame(strlen($bytes), strlen($reserialized));
    $reparsed = new FeedMessage();
    $reparsed->mergeFromString($reserialized);
    $this->assertSameJson($feed->serializeToJsonString(), $reparsed->serializeToJsonString());
  }

  /**
   * GTFS Realtime reserves extension ranges (1000-1999, 9000-9999) and real
   * agency feeds use them. The generated classes no longer declare the
   * ranges (proto3 cannot), so such data arrives as unknown fields — and
   * must survive a parse/serialize pass, or pass-through pipelines corrupt
   * feeds silently. Field 1000, varint 42 encodes as C0 3E 2A.
   */
  public function testUnknownExtensionFieldSurvivesRoundTrip(): void {
    $bytes = file_get_contents(self::FIXTURES . '/feed.pb') . "\xC0\x3E\x2A";
    $feed = new FeedMessage();
    $feed->mergeFromString($bytes);
    $reserialized = $feed->serializeToString();
    if (!extension_loaded('protobuf')) {
      $this->assertSame($bytes, $reserialized);
    }
    $this->assertSame(strlen($bytes), strlen($reserialized));
    $this->assertStringContainsString("\xC0\x3E\x2A", $reserialized);
  }

  /**
   * Strict equality after recursively sorting keys and rounding floats to
   * six significant digits, so semantically equal JSON passes regardless of
   * which runtime emitted it: the runtimes render the same float32 bits as
   * different decimal strings (37.7793 vs 37.779301).
   */
  private function assertSameJson(string $expected, string $actual): void {
    $e = json_decode($expected, TRUE);
    $a = json_decode($actual, TRUE);
    $normalize = function (array &$node) use (&$normalize): void {
      ksort($node);
      foreach ($node as &$child) {
        if (is_array($child)) {
          $normalize($child);
        }
        elseif (is_float($child)) {
          $child = (float) sprintf('%.6g', $child);
        }
      }
    };
    $normalize($e);
    $normalize($a);
    $this->assertSame($e, $a);
  }

  /**
   * Explicitly-set default values must survive to JSON — the data
   * lowa/gtfs-realtime-php silently dropped. The fixture sets direction_id
   * 0, schedule_relationship SCHEDULED, delay 0, occupancy_percentage 0.
   */
  public function testExplicitDefaultsSurviveToJson(): void {
    $feed = new FeedMessage();
    $feed->mergeFromString(file_get_contents(self::FIXTURES . '/feed.pb'));
    $decoded = json_decode($feed->serializeToJsonString(), TRUE);
    $trip = $decoded['entity'][0]['tripUpdate']['trip'];
    $this->assertSame(0, $trip['directionId']);
    $this->assertSame('SCHEDULED', $trip['scheduleRelationship']);
    $this->assertSame(0, $decoded['entity'][0]['tripUpdate']['stopTimeUpdate'][0]['arrival']['delay']);
    $this->assertSame(0, $decoded['entity'][1]['vehicle']['occupancyPercentage']);
  }

  /**
   * Documented proto3 trade-off, identical to lowa's behavior: without
   * proto2 custom defaults, getters on UNSET fields return the zero
   * sentinel, not the spec default (UNKNOWN_CAUSE). Consumers distinguish
   * unset via hasCause(). Revisit when the source moves to editions.
   */
  public function testUnsetFieldBehavior(): void {
    $alert = new Alert();
    $this->assertFalse($alert->hasCause());
    $this->assertSame(Cause::PROTO3_DEFAULT_CAUSE, $alert->getCause());
  }

  /**
   * The committed API surface is the BC contract: lines removed = major
   * version, lines added = minor. Regenerate with
   * php tools/api-surface.php > tests/api-surface.txt
   */
  public function testApiSurfaceMatchesSnapshot(): void {
    exec('php ' . escapeshellarg(__DIR__ . '/../tools/api-surface.php') . ' 2>/dev/null', $output, $status);
    $this->assertSame(0, $status);
    $this->assertSame(
      file_get_contents(__DIR__ . '/api-surface.txt'),
      implode("\n", $output) . "\n"
    );
  }

}
