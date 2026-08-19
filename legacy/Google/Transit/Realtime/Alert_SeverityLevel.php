<?php
// Legacy class name from lowa/gtfs-realtime-php, kept so that package's
// consumers can switch without code changes.

namespace Google\Transit\Realtime;

if (false) {
    /**
     * @deprecated Use Google\Transit\Realtime\Alert\SeverityLevel instead.
     */
    class Alert_SeverityLevel {}
}
class_alias(Alert\SeverityLevel::class, Alert_SeverityLevel::class);
@trigger_error('Google\Transit\Realtime\Alert_SeverityLevel is deprecated. Use Google\Transit\Realtime\Alert\SeverityLevel instead.', E_USER_DEPRECATED);
