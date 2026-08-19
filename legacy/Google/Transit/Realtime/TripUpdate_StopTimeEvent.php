<?php
// Legacy class name from lowa/gtfs-realtime-php, kept so that package's
// consumers can switch without code changes.

namespace Google\Transit\Realtime;

if (false) {
    /**
     * @deprecated Use Google\Transit\Realtime\TripUpdate\StopTimeEvent instead.
     */
    class TripUpdate_StopTimeEvent {}
}
class_alias(TripUpdate\StopTimeEvent::class, TripUpdate_StopTimeEvent::class);
@trigger_error('Google\Transit\Realtime\TripUpdate_StopTimeEvent is deprecated. Use Google\Transit\Realtime\TripUpdate\StopTimeEvent instead.', E_USER_DEPRECATED);
