<?php
// Legacy class name from lowa/gtfs-realtime-php, kept so that package's
// consumers can switch without code changes.

namespace Google\Transit\Realtime;

if (false) {
    /**
     * @deprecated Use Google\Transit\Realtime\TripDescriptor\ScheduleRelationship instead.
     */
    class TripDescriptor_ScheduleRelationship {}
}
class_alias(TripDescriptor\ScheduleRelationship::class, TripDescriptor_ScheduleRelationship::class);
@trigger_error('Google\Transit\Realtime\TripDescriptor_ScheduleRelationship is deprecated. Use Google\Transit\Realtime\TripDescriptor\ScheduleRelationship instead.', E_USER_DEPRECATED);
