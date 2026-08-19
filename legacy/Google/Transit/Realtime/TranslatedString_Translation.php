<?php
// Legacy class name from lowa/gtfs-realtime-php, kept so that package's
// consumers can switch without code changes.

namespace Google\Transit\Realtime;

if (false) {
    /**
     * @deprecated Use Google\Transit\Realtime\TranslatedString\Translation instead.
     */
    class TranslatedString_Translation {}
}
class_alias(TranslatedString\Translation::class, TranslatedString_Translation::class);
@trigger_error('Google\Transit\Realtime\TranslatedString_Translation is deprecated. Use Google\Transit\Realtime\TranslatedString\Translation instead.', E_USER_DEPRECATED);
