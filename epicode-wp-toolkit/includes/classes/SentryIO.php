<?php

namespace Epic_Toolkit;

use WP_Sentry_Php_Tracker;
use WP_Sentry_Js_Tracker;
use InvalidArgumentException;

/**
*
*/

class SentryIO
{

  /**
  *
  */

  const DATA_FIELD_PREFIX = 'epic_sentry';

  /**
  *
  */
  public $data_settings = [];

  /**
  *
  */
  public $environment_types = ['staging', 'production'];

  /**
  *
  */
  public function __construct()
  {
    $this->initializeDataSettings();
    add_action( 'admin_notices', [$this, 'displayNotices'] );
  }

  /**
  *
  */
  public function initializeDataSettings()
  {
    $this->data_settings = [
      'allow'                       => get_option( self::DATA_FIELD_PREFIX . '_allow', 'no' ),
      'dsn'                         => get_option( self::DATA_FIELD_PREFIX . '_dsn', '' ),
      'php_error_tracking_enabled'  => get_option( self::DATA_FIELD_PREFIX . '_php_error_tracking_enabled', 'no' ),
      'js_error_tracking_enabled'   => get_option( self::DATA_FIELD_PREFIX . '_js_error_tracking_enabled', 'no' ),
      'environment'                 => get_option( self::DATA_FIELD_PREFIX . '_environment', 'staging' )
    ];
  }

  /**
  *
  */
  public function reinitializeDataSettings()
  {
    $this->initializeDataSettings();
  }

  /**
  *
  */
  public function handleFormSave()
  {
    $data_settings_fields = array_keys($this->data_settings);
    foreach ( $data_settings_fields as $data_setting_field ) {
      if ( isset( $_POST[$data_setting_field] ) ) {
        update_option(
          self::DATA_FIELD_PREFIX . '_' . $data_setting_field,
          $_POST[$data_setting_field]
        );
      }
    }
    $this->reinitializeDataSettings();
    // $this->disableErrorTrackers();
    $this->manageMUPluginFile();
  }

  /**
  *
  */
  public function displayNotices()
  {
    if ( $this->data_settings['allow'] == 'no' ) return;

    $plugin_exists = file_exists( WP_PLUGIN_DIR . '/wp-sentry-integration/wp-sentry.php' );

    if ( ! $plugin_exists ) {
      $installation_link = wp_nonce_url(
        add_query_arg(
          [
            'action' => 'install-plugin',
            'plugin' => 'wp-sentry-integration'
          ],
          admin_url( 'update.php' )
        ),
        'install-plugin_wp-sentry-integration'
      );

      printf(
        '<div class="notice notice-warning"><p>%s</p></div>',
        sprintf(
          '<a href="%s" target="_blank">Wordpress Sentry</a> is not installed. Please click <a href="%s">here</a> to install.',
          'https://wordpress.org/plugins/wp-sentry-integration/',
          $installation_link
        )
      );
    }

    if ( $plugin_exists && ! is_plugin_active( 'wp-sentry-integration/wp-sentry.php' ) ) {
      $plugin_path = 'wp-sentry-integration/wp-sentry.php';
      $activation_link = wp_nonce_url(
        add_query_arg(
          [
            'action' => 'activate',
            'plugin' => $plugin_path
          ],
          admin_url( 'plugins.php' )
        ),
        'activate-plugin_' . $plugin_path
      );

      printf(
        '<div class="notice notice-warning"><p>%s</p></div>',
        sprintf(
          '<a href="%s" target="_blank">Wordpress Sentry</a> is not activated. Please click <a href="%s">here</a> to activate.',
          'https://wordpress.org/plugins/wp-sentry-integration/',
          $activation_link
        )
      );
    }

    if ( class_exists( 'WP_Sentry_Tracker_Base' ) && empty( $this->data_settings['dsn'] ) ) {
      printf(
        '<div class="notice notice-warning"><p>%s</p></div>',
        sprintf(
          'DSN is empty, to make sentry error tracker work, please enter your Public DSN. Click <a href="%s" target="_blank">here</a> to learn more.',
          'https://docs.sentry.io/quickstart/#configure-the-dsn/'
        )
      );
    }
  }

  // public function disableErrorTrackers()
  // {
  //   if ( $this->data_settings['allow'] == 'no' ) {
  //     update_option( self::DATA_FIELD_PREFIX . '_php_error_tracking_enabled', 'no' );
  //     update_option( self::DATA_FIELD_PREFIX . '_js_error_tracking_enabled', 'no' );
  //   }
  // }

  /**
  *
  */
  public function manageMUPluginFile()
  {
    global $wp_filesystem;

    // Ensure wp file system is initialized
    if( empty( $wp_filesystem ) && get_filesystem_method() === 'direct' ){
      $creds = request_filesystem_credentials(site_url() . '/wp-admin/', '', false, false, array());

    	/* initialize the API */
    	if ( ! WP_Filesystem($creds) ) {
    		/* any problems and we exit */
    		return false;
    	}
    }

    $mu_plugin_dir_path = WPMU_PLUGIN_DIR;
    $toolkit_mu_plugin_path = dirname(dirname(__DIR__)) . '/mu-plugins/epic-toolkit-wp-sentry-integration.php';
    $mu_plugin_file_path = $mu_plugin_dir_path . '/epic-toolkit-wp-sentry-integration.php';

    // Note: no need?
    if ( $this->data_settings['allow'] == 'no' ) {
      return unlink( $mu_plugin_file_path );
    }

    if ( wp_mkdir_p( $mu_plugin_dir_path ) ) {
      $put_contents = $wp_filesystem->put_contents(
        $mu_plugin_file_path,
        file_get_contents( $toolkit_mu_plugin_path )
      );

      if ( false === $put_contents ) {
        add_action( 'admin_notices', function() {
          printf(
            '<div class="notice notice-error">%s</div>',
            __( "{$mu_plugin_file_path} can't be created. Please check file permissions." )
          );
        } );
      }
    }
  }
}
