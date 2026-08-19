<?php
// Legacy class name from lowa/gtfs-realtime-php, kept so that package's
// consumers can switch without code changes.

namespace Google\Transit\Realtime;

if (false) {
    /**
     * @deprecated Use Google\Transit\Realtime\FeedHeader\Incrementality instead.
     */
    class FeedHeader_Incrementality {}
}
class_alias(FeedHeader\Incrementality::class, FeedHeader_Incrementality::class);
@trigger_error('Google\Transit\Realtime\FeedHeader_Incrementality is deprecated. Use Google\Transit\Realtime\FeedHeader\Incrementality instead.', E_USER_DEPRECATED);
