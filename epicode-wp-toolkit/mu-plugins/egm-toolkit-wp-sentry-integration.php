<?php

if (!function_exists('is_plugin_active')){
	require_once (ABSPATH.'wp-admin/includes/plugin.php');
}

$wp_sentry = __DIR__ . '/../plugins/wp-sentry-integration/wp-sentry.php';

if ( ! file_exists( $wp_sentry ) || ! is_plugin_active( 'wp-sentry-integration/wp-sentry.php' ) ) {
	return;
}

if ( get_option( 'epic_sentry_allow', 'no' ) == 'no' ) {
	return;
}

if ( get_option( 'epic_sentry_php_error_tracking_enabled', 'no' ) == 'yes' ) {
	if ( ! defined( 'WP_SENTRY_DSN' ) ) {
		define( 'WP_SENTRY_DSN', get_option( 'epic_sentry_dsn', '' ) );
	}
}

if ( get_option( 'epic_sentry_js_error_tracking_enabled', 'no' ) == 'yes' ) {
	if ( ! defined( 'WP_SENTRY_PUBLIC_DSN' ) ) {
		define( 'WP_SENTRY_PUBLIC_DSN', get_option( 'epic_sentry_dsn', '' ) );
	}
}

if ( ! defined( 'WP_SENTRY_ENV' ) ) {
	define( 'WP_SENTRY_ENV', get_option( 'epic_sentry_environment', 'staging' ) );
}

require $wp_sentry;

define( 'WP_SENTRY_MU_LOADED', true );
