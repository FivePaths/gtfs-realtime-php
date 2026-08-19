<?php
// Legacy class name from lowa/gtfs-realtime-php, kept so that package's
// consumers can switch without code changes.

namespace Google\Transit\Realtime;

if (false) {
    /**
     * @deprecated Use Google\Transit\Realtime\Alert\Cause instead.
     */
    class Alert_Cause {}
}
class_alias(Alert\Cause::class, Alert_Cause::class);
@trigger_error('Google\Transit\Realtime\Alert_Cause is deprecated. Use Google\Transit\Realtime\Alert\Cause instead.', E_USER_DEPRECATED);
