<?php
// Legacy class name from lowa/gtfs-realtime-php, kept so that package's
// consumers can switch without code changes.

namespace Google\Transit\Realtime;

if (false) {
    /**
     * @deprecated Use Google\Transit\Realtime\TripUpdate\StopTimeUpdate instead.
     */
    class TripUpdate_StopTimeUpdate {}
}
class_alias(TripUpdate\StopTimeUpdate::class, TripUpdate_StopTimeUpdate::class);
@trigger_error('Google\Transit\Realtime\TripUpdate_StopTimeUpdate is deprecated. Use Google\Transit\Realtime\TripUpdate\StopTimeUpdate instead.', E_USER_DEPRECATED);
