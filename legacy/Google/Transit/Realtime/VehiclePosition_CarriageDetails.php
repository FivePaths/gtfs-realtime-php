<?php
// Legacy class name from lowa/gtfs-realtime-php, kept so that package's
// consumers can switch without code changes.

namespace Google\Transit\Realtime;

if (false) {
    /**
     * @deprecated Use Google\Transit\Realtime\VehiclePosition\CarriageDetails instead.
     */
    class VehiclePosition_CarriageDetails {}
}
class_alias(VehiclePosition\CarriageDetails::class, VehiclePosition_CarriageDetails::class);
@trigger_error('Google\Transit\Realtime\VehiclePosition_CarriageDetails is deprecated. Use Google\Transit\Realtime\VehiclePosition\CarriageDetails instead.', E_USER_DEPRECATED);
