<?php
// Legacy class name from lowa/gtfs-realtime-php, kept so that package's
// consumers can switch without code changes.

namespace Google\Transit\Realtime;

if (false) {
    /**
     * @deprecated Use Google\Transit\Realtime\VehicleDescriptor\WheelchairAccessible instead.
     */
    class VehicleDescriptor_WheelchairAccessible {}
}
class_alias(VehicleDescriptor\WheelchairAccessible::class, VehicleDescriptor_WheelchairAccessible::class);
@trigger_error('Google\Transit\Realtime\VehicleDescriptor_WheelchairAccessible is deprecated. Use Google\Transit\Realtime\VehicleDescriptor\WheelchairAccessible instead.', E_USER_DEPRECATED);
