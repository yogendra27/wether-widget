=== Weather Widget ===
Contributors: Yogendrakumar Sahani
Tags: weather, shortcode, openweathermap
Requires at least: 5.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A simple shortcode to show the current weather for any city.

== Description ==

This plugin adds one shortcode: `[weather_widget]`. Use it anywhere on your
site to show the current temperature and weather for a city, like
"15°C, Cloudy".

It gets the weather from OpenWeatherMap and saves the result for 1 hour, so
the site is not calling the API every single time someone visits the page.

== Installation ==

1. Get a free API key here: https://openweathermap.org/api
2. Copy the `weather-widget` folder into `/wp-content/plugins/`, or just
   upload the zip from Plugins > Add New > Upload Plugin.
3. Activate the plugin from the Plugins page.
4. Go to Settings > Weather Widget and paste in your API key.

== Usage ==

Just drop this shortcode into any post or page:

`[weather_widget city="London"]`

If you don't add a city, it will show London by default.

A couple more examples:

`[weather_widget city="New York"]`
`[weather_widget city="Tokyo"]`

== Frequently Asked Questions ==

= How often does the weather update? =

Every city is cached for 1 hour, then it fetches fresh data on the next visit.

= What if the API key is wrong or missing? =

It just shows "Weather data is currently unavailable." instead of an error,
so it won't break your page.

== Changelog ==

= 1.0.0 =
* First version.
