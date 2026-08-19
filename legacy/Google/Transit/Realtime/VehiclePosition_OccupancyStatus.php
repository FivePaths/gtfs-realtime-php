<?php
// Legacy class name from lowa/gtfs-realtime-php, kept so that package's
// consumers can switch without code changes.

namespace Google\Transit\Realtime;

if (false) {
    /**
     * @deprecated Use Google\Transit\Realtime\VehiclePosition\OccupancyStatus instead.
     */
    class VehiclePosition_OccupancyStatus {}
}
class_alias(VehiclePosition\OccupancyStatus::class, VehiclePosition_OccupancyStatus::class);
@trigger_error('Google\Transit\Realtime\VehiclePosition_OccupancyStatus is deprecated. Use Google\Transit\Realtime\VehiclePosition\OccupancyStatus instead.', E_USER_DEPRECATED);
