<?php

/**
 * Builds the binary test fixture and the golden JSON snapshots under
 * tests/fixtures/. Run after an intentional behavior change, then review the
 * git diff of the goldens — that diff is the reviewable record of what the
 * change did to observable output. Regenerating goldens to silence a failing
 * test without reading the diff defeats their purpose.
 */

require __DIR__ . '/../vendor/autoload.php';

use Google\Transit\Realtime\Alert;
use Google\Transit\Realtime\Alert\Cause;
use Google\Transit\Realtime\Alert\Effect;
use Google\Transit\Realtime\Alert\SeverityLevel;
use Google\Transit\Realtime\EntitySelector;
use Google\Transit\Realtime\FeedEntity;
use Google\Transit\Realtime\FeedHeader;
use Google\Transit\Realtime\FeedHeader\Incrementality;
use Google\Transit\Realtime\FeedMessage;
use Google\Transit\Realtime\Position;
use Google\Transit\Realtime\TimeRange;
use Google\Transit\Realtime\TranslatedString;
use Google\Transit\Realtime\TranslatedString\Translation;
use Google\Transit\Realtime\TripDescriptor;
use Google\Transit\Realtime\TripDescriptor\ScheduleRelationship;
use Google\Transit\Realtime\TripUpdate;
use Google\Transit\Realtime\TripUpdate\StopTimeEvent;
use Google\Transit\Realtime\TripUpdate\StopTimeUpdate;
use Google\Transit\Realtime\VehicleDescriptor;
use Google\Transit\Realtime\VehiclePosition;
use Google\Transit\Realtime\VehiclePosition\CarriageDetails;
use Google\Transit\Realtime\VehiclePosition\OccupancyStatus;
use Google\Transit\Realtime\VehiclePosition\VehicleStopStatus;

$fixtures = __DIR__ . '/../tests/fixtures';
is_dir($fixtures) || mkdir($fixtures, 0755, TRUE);

$trip = (new TripDescriptor())
  ->setTripId('T100')
  ->setRouteId('38R')
  // Explicitly-set default values: proto2 presence semantics must carry
  // these through to JSON. lowa's conversion dropped every one of them.
  ->setDirectionId(0)
  ->setScheduleRelationship(ScheduleRelationship::SCHEDULED)
  ->setStartDate('20260818');

$feed = (new FeedMessage())
  ->setHeader(
    (new FeedHeader())
      ->setGtfsRealtimeVersion('2.0')
      ->setIncrementality(Incrementality::FULL_DATASET)
      ->setTimestamp(1755550000)
  )
  ->setEntity([
    (new FeedEntity())->setId('trip-update-1')->setTripUpdate(
      (new TripUpdate())
        ->setTrip($trip)
        ->setVehicle((new VehicleDescriptor())->setId('V42')->setLabel('Geary'))
        ->setStopTimeUpdate([
          (new StopTimeUpdate())
            ->setStopSequence(0)
            ->setStopId('S1')
            ->setArrival((new StopTimeEvent())->setDelay(0)->setTime(1755550060)),
          (new StopTimeUpdate())
            ->setStopSequence(7)
            ->setStopId('S8')
            ->setDeparture((new StopTimeEvent())->setDelay(-30)->setUncertainty(15)),
        ])
        ->setTimestamp(1755549990)
        ->setDelay(0)
    ),
    (new FeedEntity())->setId('vehicle-1')->setVehicle(
      (new VehiclePosition())
        ->setTrip($trip)
        ->setVehicle((new VehicleDescriptor())->setId('V42'))
        ->setPosition(
          (new Position())
            ->setLatitude(37.7793)
            ->setLongitude(-122.4193)
            ->setBearing(0.0)
            ->setSpeed(0.0)
        )
        ->setCurrentStopSequence(0)
        ->setStopId('S1')
        ->setCurrentStatus(VehicleStopStatus::STOPPED_AT)
        ->setTimestamp(1755550000)
        ->setOccupancyStatus(OccupancyStatus::PBEMPTY)
        ->setOccupancyPercentage(0)
        ->setMultiCarriageDetails([
          (new CarriageDetails())->setId('C1')->setCarriageSequence(1)
            ->setOccupancyStatus(OccupancyStatus::MANY_SEATS_AVAILABLE)
            ->setOccupancyPercentage(0),
        ])
    ),
    (new FeedEntity())->setId('alert-1')->setAlert(
      (new Alert())
        ->setActivePeriod([(new TimeRange())->setStart(1755540000)->setEnd(1755600000)])
        ->setInformedEntity([
          (new EntitySelector())->setRouteId('38R'),
          (new EntitySelector())->setStopId('S1'),
        ])
        ->setCause(Cause::CONSTRUCTION)
        ->setEffect(Effect::DETOUR)
        ->setSeverityLevel(SeverityLevel::WARNING)
        ->setHeaderText(
          (new TranslatedString())->setTranslation([
            (new Translation())->setText('Geary construction detour')->setLanguage('en'),
          ])
        )
    ),
  ]);

file_put_contents("$fixtures/feed.pb", $feed->serializeToString());
file_put_contents("$fixtures/feed.json", $feed->serializeToJsonString() . "\n");

$alert = new FeedMessage();
$alert->mergeFromString(file_get_contents(__DIR__ . '/../examples/ExampleAlert.pb'));
file_put_contents("$fixtures/ExampleAlert.json", $alert->serializeToJsonString() . "\n");

echo "Wrote feed.pb (" . filesize("$fixtures/feed.pb") . " bytes), feed.json, ExampleAlert.json\n";
