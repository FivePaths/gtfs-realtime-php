<?php
// Legacy class name from lowa/gtfs-realtime-php, kept so that package's
// consumers can switch without code changes.

namespace Google\Transit\Realtime;

if (false) {
    /**
     * @deprecated Use Google\Transit\Realtime\VehiclePosition\VehicleStopStatus instead.
     */
    class VehiclePosition_VehicleStopStatus {}
}
class_alias(VehiclePosition\VehicleStopStatus::class, VehiclePosition_VehicleStopStatus::class);
@trigger_error('Google\Transit\Realtime\VehiclePosition_VehicleStopStatus is deprecated. Use Google\Transit\Realtime\VehiclePosition\VehicleStopStatus instead.', E_USER_DEPRECATED);
