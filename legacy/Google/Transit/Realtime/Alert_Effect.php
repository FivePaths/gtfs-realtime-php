<?php
// Legacy class name from lowa/gtfs-realtime-php, kept so that package's
// consumers can switch without code changes.

namespace Google\Transit\Realtime;

if (false) {
    /**
     * @deprecated Use Google\Transit\Realtime\Alert\Effect instead.
     */
    class Alert_Effect {}
}
class_alias(Alert\Effect::class, Alert_Effect::class);
@trigger_error('Google\Transit\Realtime\Alert_Effect is deprecated. Use Google\Transit\Realtime\Alert\Effect instead.', E_USER_DEPRECATED);
