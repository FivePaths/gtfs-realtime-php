<?php
// Legacy class name from lowa/gtfs-realtime-php, kept so that package's
// consumers can switch without code changes.

namespace Google\Transit\Realtime;

if (false) {
    /**
     * @deprecated Use Google\Transit\Realtime\TripUpdate\StopTimeUpdate\ScheduleRelationship instead.
     */
    class TripUpdate_StopTimeUpdate_ScheduleRelationship {}
}
class_alias(TripUpdate\StopTimeUpdate\ScheduleRelationship::class, TripUpdate_StopTimeUpdate_ScheduleRelationship::class);
@trigger_error('Google\Transit\Realtime\TripUpdate_StopTimeUpdate_ScheduleRelationship is deprecated. Use Google\Transit\Realtime\TripUpdate\StopTimeUpdate\ScheduleRelationship instead.', E_USER_DEPRECATED);
