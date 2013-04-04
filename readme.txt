=== Hotscot Events ===
Contributors: huntlyc, DaganLev
Tags: Events, Hotscot
Donate Link: http://hotscot.net
Requires at least: 3.1
Tested up to: 3.3
Stable tag: 1.0.6

Simple events module...

== Description ==

Simple events module...

The shortcode is [ht_list_events numevents=1] where num events is optional to limit the amount of events being shown.

For theme developers - these functions may also be of use to you:

`
/**
 * echo out the events table
 * @param  string  $start_date [optional] leave blnk for current date
 * @param  string  $end_date   [optional] leave blank for all events
 * @param  integer $limit      [optional] set to 0 for all events
 * @return void
 */
ht_eventsHTML($start_date = "", $end_date = "", $limit = 0)
`

`
/**
 * get the events table html string
 * @param  string  $start_date [optional] leave blnk for current date
 * @param  string  $end_date   [optional] leave blank for all events
 * @param  integer $limit      [optional] set to 0 for all events
 * @return string $html Events table html
 */
ht_getEventsHTML($start_date = "", $end_date = "", $limit = 0);
`

== Installation ==

1. Upload `hotscot-events.zip` to the `/wp-content/plugins/` directory
1. Unzip contents
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==
NA

== Changelog ==

= 1.0.6 =
* added: shortcode for showing events as well as helper methods for theme creators

= 1.0.5 =
* fixed: session warning

= 1.0.4 =
* fixed some warnings

= 1.0.3 =
* fixed: sorted some issues

= 1.0.2 =
* added: Custom Filter on admin page

= 1.0.1 =
Sortable start and end date columns on admin section.


== Upgrade Notice ==

= 1.0.6 =
* added: shortcode for showing events as well as helper methods for theme creators

= 1.0.5 =
* fixed: session warning

= 1.0.4 =
* fixed some warnings

= 1.0.3 =
* fixed: sorted some issues

= 1.0.2 =
* added: Custom Filter on admin page

= 1.0.1 =
Sortable start and end date columns on admin section.

== Screenshots ==
NA