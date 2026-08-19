<?php

namespace GtfsMedia\GtfsRealtime\Tests;

use Google\Transit\Realtime\FeedHeader\Incrementality;
use Google\Transit\Realtime\TripUpdate\StopTimeUpdate;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The 17 underscore class names lowa/gtfs-realtime-php shipped resolve as
 * aliases of the nested classes, so code written against the old package
 * runs against this one — the contract behind the composer.json `replace`.
 */
class LegacyAliasTest extends TestCase {

  public static function aliases(): array {
    $map = [
      'Alert_Cause' => 'Alert\Cause',
      'Alert_Effect' => 'Alert\Effect',
      'Alert_SeverityLevel' => 'Alert\SeverityLevel',
      'FeedHeader_Incrementality' => 'FeedHeader\Incrementality',
      'TranslatedImage_LocalizedImage' => 'TranslatedImage\LocalizedImage',
      'TranslatedString_Translation' => 'TranslatedString\Translation',
      'TripDescriptor_ScheduleRelationship' => 'TripDescriptor\ScheduleRelationship',
      'TripUpdate_StopTimeEvent' => 'TripUpdate\StopTimeEvent',
      'TripUpdate_StopTimeUpdate' => 'TripUpdate\StopTimeUpdate',
      'TripUpdate_StopTimeUpdate_ScheduleRelationship' => 'TripUpdate\StopTimeUpdate\ScheduleRelationship',
      'TripUpdate_StopTimeUpdate_StopTimeProperties' => 'TripUpdate\StopTimeUpdate\StopTimeProperties',
      'TripUpdate_TripProperties' => 'TripUpdate\TripProperties',
      'VehicleDescriptor_WheelchairAccessible' => 'VehicleDescriptor\WheelchairAccessible',
      'VehiclePosition_CarriageDetails' => 'VehiclePosition\CarriageDetails',
      'VehiclePosition_CongestionLevel' => 'VehiclePosition\CongestionLevel',
      'VehiclePosition_OccupancyStatus' => 'VehiclePosition\OccupancyStatus',
      'VehiclePosition_VehicleStopStatus' => 'VehiclePosition\VehicleStopStatus',
    ];
    $cases = [];
    foreach ($map as $legacy => $nested) {
      $cases[$legacy] = [
        'Google\Transit\Realtime\\' . $legacy,
        'Google\Transit\Realtime\\' . $nested,
      ];
    }
    return $cases;
  }

  #[DataProvider('aliases')]
  public function testLegacyNameResolvesToNestedClass(string $legacy, string $nested): void {
    $this->assertTrue(class_exists($legacy), "$legacy is not loadable");
    $this->assertSame($nested, (new \ReflectionClass($legacy))->getName());
  }

  public function testLegacyEnumConstantsMatch(): void {
    $this->assertSame(
      Incrementality::FULL_DATASET,
      \Google\Transit\Realtime\FeedHeader_Incrementality::FULL_DATASET
    );
  }

  public function testLegacyMessageNameAcceptsNestedInstances(): void {
    $this->assertInstanceOf(
      \Google\Transit\Realtime\TripUpdate_StopTimeUpdate::class,
      new StopTimeUpdate()
    );
  }

}
