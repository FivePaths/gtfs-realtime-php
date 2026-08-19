<?php
// Legacy class name from lowa/gtfs-realtime-php, kept so that package's
// consumers can switch without code changes.

namespace Google\Transit\Realtime;

if (false) {
    /**
     * @deprecated Use Google\Transit\Realtime\TripUpdate\StopTimeUpdate\StopTimeProperties instead.
     */
    class TripUpdate_StopTimeUpdate_StopTimeProperties {}
}
class_alias(TripUpdate\StopTimeUpdate\StopTimeProperties::class, TripUpdate_StopTimeUpdate_StopTimeProperties::class);
@trigger_error('Google\Transit\Realtime\TripUpdate_StopTimeUpdate_StopTimeProperties is deprecated. Use Google\Transit\Realtime\TripUpdate\StopTimeUpdate\StopTimeProperties instead.', E_USER_DEPRECATED);
