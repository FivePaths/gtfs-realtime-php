<?php
// Legacy class name from lowa/gtfs-realtime-php, kept so that package's
// consumers can switch without code changes.

namespace Google\Transit\Realtime;

if (false) {
    /**
     * @deprecated Use Google\Transit\Realtime\TripUpdate\TripProperties instead.
     */
    class TripUpdate_TripProperties {}
}
class_alias(TripUpdate\TripProperties::class, TripUpdate_TripProperties::class);
@trigger_error('Google\Transit\Realtime\TripUpdate_TripProperties is deprecated. Use Google\Transit\Realtime\TripUpdate\TripProperties instead.', E_USER_DEPRECATED);
