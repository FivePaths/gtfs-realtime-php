<?php
// Legacy class name from lowa/gtfs-realtime-php, kept so that package's
// consumers can switch without code changes.

namespace Google\Transit\Realtime;

if (false) {
    /**
     * @deprecated Use Google\Transit\Realtime\TranslatedImage\LocalizedImage instead.
     */
    class TranslatedImage_LocalizedImage {}
}
class_alias(TranslatedImage\LocalizedImage::class, TranslatedImage_LocalizedImage::class);
@trigger_error('Google\Transit\Realtime\TranslatedImage_LocalizedImage is deprecated. Use Google\Transit\Realtime\TranslatedImage\LocalizedImage instead.', E_USER_DEPRECATED);
