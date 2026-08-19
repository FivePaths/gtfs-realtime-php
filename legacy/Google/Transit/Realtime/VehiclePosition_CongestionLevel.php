<?php
// Legacy class name from lowa/gtfs-realtime-php, kept so that package's
// consumers can switch without code changes.

namespace Google\Transit\Realtime;

if (false) {
    /**
     * @deprecated Use Google\Transit\Realtime\VehiclePosition\CongestionLevel instead.
     */
    class VehiclePosition_CongestionLevel {}
}
class_alias(VehiclePosition\CongestionLevel::class, VehiclePosition_CongestionLevel::class);
@trigger_error('Google\Transit\Realtime\VehiclePosition_CongestionLevel is deprecated. Use Google\Transit\Realtime\VehiclePosition\CongestionLevel instead.', E_USER_DEPRECATED);
