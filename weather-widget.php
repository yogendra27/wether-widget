<?php
/**
 * Plugin Name:       YKS Weather Widget
 * Plugin URI:        https://github.com/yourusername/weather-widget
 * Description:       Adds a [weather_widget city="London"] shortcode that shows the current temperature and conditions for a city, using the OpenWeatherMap API.
 * Version:           1.0.0
 * Author:            Yogendrakumar Sahani
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       yks-weather-widget
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

class Weather_Widget_Plugin {

	// Transient lifetime, so we don't hit the API on every page load.
	const CACHE_DURATION = HOUR_IN_SECONDS;

	const API_URL = 'https://api.openweathermap.org/data/2.5/weather';

	public function __construct() {
		add_shortcode( 'weather_widget', array( $this, 'render_shortcode' ) );
		add_action( 'admin_menu', array( $this, 'register_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	// Settings > Weather Widget, just holds the API key.
	public function register_settings_page() {
		add_options_page(
			__( 'Weather Widget', 'weather-widget' ),
			__( 'Weather Widget', 'weather-widget' ),
			'manage_options',
			'weather-widget',
			array( $this, 'render_settings_page' )
		);
	}

	public function register_settings() {
		register_setting(
			'weather_widget_settings',
			'weather_widget_api_key',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		add_settings_section(
			'weather_widget_main',
			__( 'API Settings', 'weather-widget' ),
			'__return_false',
			'weather-widget'
		);

		add_settings_field(
			'weather_widget_api_key',
			__( 'OpenWeatherMap API Key', 'weather-widget' ),
			array( $this, 'render_api_key_field' ),
			'weather-widget',
			'weather_widget_main'
		);
	}

	public function render_api_key_field() {
		$api_key = get_option( 'weather_widget_api_key', '' );
		printf(
			'<input type="text" name="weather_widget_api_key" value="%s" class="regular-text" autocomplete="off" />',
			esc_attr( $api_key )
		);
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Weather Widget Settings', 'weather-widget' ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'weather_widget_settings' );
				do_settings_sections( 'weather-widget' );
				submit_button();
				?>
			</form>
			<p><?php esc_html_e( 'Usage: [weather_widget city="London"]', 'weather-widget' ); ?></p>
		</div>
		<?php
	}

	/**
	 * [weather_widget city="London"]
	 */
	public function render_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'city' => 'London',
			),
			$atts,
			'weather_widget'
		);

		$city = sanitize_text_field( $atts['city'] );

		if ( '' === $city ) {
			return '';
		}

		$weather = $this->get_weather_data( $city );

		if ( is_wp_error( $weather ) ) {
			return sprintf(
				'<div class="weather-widget weather-widget--error">%s</div>',
				esc_html__( 'Weather data is currently unavailable.', 'weather-widget' )
			);
		}

		return sprintf(
			'<div class="weather-widget"><span class="weather-widget__city">%1$s</span>: <span class="weather-widget__temp">%2$s&deg;C</span>, <span class="weather-widget__desc">%3$s</span></div>',
			esc_html( $weather['city'] ),
			esc_html( $weather['temp'] ),
			esc_html( $weather['description'] )
		);
	}

	// Returns cached data if we have it, otherwise hits the API and caches the result.
	private function get_weather_data( $city ) {
		$cache_key = 'weather_widget_' . md5( strtolower( $city ) );
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$data = $this->fetch_weather_from_api( $city );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		set_transient( $cache_key, $data, self::CACHE_DURATION );

		return $data;
	}

	private function fetch_weather_from_api( $city ) {
		$api_key = get_option( 'weather_widget_api_key', '' );

		if ( empty( $api_key ) ) {
			return new WP_Error( 'weather_widget_no_api_key', __( 'Missing API key.', 'weather-widget' ) );
		}

		$url = add_query_arg(
			array(
				'q'     => rawurlencode( $city ),
				'appid' => rawurlencode( $api_key ),
				'units' => 'metric',
			),
			self::API_URL
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 10,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== (int) $code || empty( $body['main']['temp'] ) ) {
			return new WP_Error( 'weather_widget_api_error', __( 'Unable to retrieve weather data.', 'weather-widget' ) );
		}

		return array(
			'city'        => isset( $body['name'] ) ? $body['name'] : $city,
			'temp'        => round( $body['main']['temp'] ),
			'description' => isset( $body['weather'][0]['description'] ) ? ucfirst( $body['weather'][0]['description'] ) : '',
		);
	}
}

new Weather_Widget_Plugin();
