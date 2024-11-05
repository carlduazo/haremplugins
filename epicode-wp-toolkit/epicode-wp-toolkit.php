<?php
/*
  Plugin Name: Epicode | Toolkit
  Description: Disables the theme, plugin and core updates and the related cronjobs and notification system.
  Plugin URI:  https://code.epicode.nl/tools/epicode-wp-toolkit/
  Version:     1.4.8
  Author:      Epicode B.V.
  Author URI:  https://epicode.nl

  Copyright 2018 Epicode (https://epicode.nl)
*/

// Exit if accessed directly
// --------------------------------
if ( ! defined( 'ABSPATH' ) ) exit;

// The Epic_WordpressOptions class
// --------------------------------
class Epic_WordpressOptions {

  public  $admin_menu;
  public  $admin_sub_menu;
  private $version = 1.48;

  public $sentryIO;

  // The Epic_WordpressOptions class constructor
  // initializing required stuff for the plugin
  // --------------------------------
  function __construct() {
    if (!function_exists('get_plugins')){
      require_once (ABSPATH.'wp-admin/includes/plugin.php');
    }

    // Add sentryIO instance
    // -----------------------
    $this->sentryIO = new \Epic_Toolkit\SentryIO;

    // Add some CSS
    // -----------------------
    add_action('admin_head',  array(__class__, 'epic_custom_styles'));

    // Handles the posted data
    // -----------------------
    add_action('admin_post_epic_handle_postdata', array($this, 'epic_handle_postdata'));

    // Adds the backend admin page
    // --------------------------------
    add_action('admin_menu', array($this, 'epic_adminpage'));
    add_action('admin_init', array($this, 'register_epicsettings'));
    add_filter( 'plugin_action_links_' . plugin_basename(__FILE__),  array($this, 'epic_plugin_action_links'));

    // Prevents the updates
    // --------------------------------
    add_action('after_setup_theme', array($this, 'epic_prevent_core_check'));
    add_action('after_setup_theme', array($this, 'epic_prevent_theme_check'));
    add_filter('site_transient_update_plugins', array($this, 'epic_disable_plugin_updates'), 99);

    // Hides the menu pages based on the set values in the Epicode plugin
    add_action( 'admin_menu', array($this, 'epic_get_menu_pages'), 998);
    add_action( 'admin_menu', array($this, 'epic_remove_menu_pages'), 999);

    // Saves the ACF groups as JSON so it can be easily pushed to staging/production
    add_filter('acf/settings/save_json', array($this, 'epic_acf_json_save_point'));
    add_filter('acf/settings/load_json', array($this, 'epic_acf_json_load_point'));
    add_action( 'admin_notices', array(__class__, 'epic_notify_about_acf_availble_sync'));

    /**
      * Moved sync folder to wp-content
      * @since 1.2.2
      */
    add_action( 'plugins_loaded', array( $this, 'move_sync_folder' ) );

    add_action( 'init', array(__class__, 'epic_handle_cptui_post_types'));
    add_action( 'admin_notices', array(__class__, 'epic_notify_about_cptui'));
    add_action( 'wp_footer', array( __class__, 'epic_project_huddle_api'  ));
    add_action( 'admin_footer', [__CLASS__, 'epic_admin_js'] );

    // Handle rewrite of options
    add_action( 'init', array( $this, 'handle_rewrite'), 5);
    add_action( 'init', array( $this, 'handle_current_url_change'), 6);
    register_activation_hook( __FILE__, array( $this, 'handle_rewrite') );
    register_activation_hook( __FILE__, array( $this, 'handle_current_url_change') );
    register_activation_hook( __FILE__, array( $this, 'add_expire_headers') );

    register_deactivation_hook( __FILE__, array( $this, 'deactivate_toolkit') );

    add_action('init', [__CLASS__, 'imagesExternalSource']);

    // Disables WP REST API
    add_action( 'init', [$this, 'disableWPRESTAPI'] );

    // Reactive maintenance/staging
    add_action( 'init', [$this, 'reactiveMaintenance'] );

    // Prevents plugin installations
    add_action( 'admin_init', [$this, 'disablePluginInstallation'] );

    // Rewrite CPT sync file to set some defaults
    add_filter( 'cptui_get_post_type_data', function( $post_types_array, $id ){

      if (!get_option('epic_disable_cpt_sync')) {
        foreach( $post_types_array as $post_type => $post_type_data ) {
          $post_types_array[$post_type]['show_in_rest'] = true;

          if( !in_array( 'revisions', $post_types_array[$post_type]['supports'] ) ){
            $post_types_array[$post_type]['supports'][] = 'revisions';
          }
        }
      }

      return $post_types_array;
    }, 99, 3 );

    // Make sure revision support and show in rest is enabled when CPTUI is active
    add_filter( 'cptui_pre_register_post_type', function( $args, $post_type_name, $post_type ){
      // Default to true if not set
      if ( empty( $post_type['show_in_rest'] ) ) {
        $args['show_in_rest'] = true;
      }

      if( !in_array( 'revisions', $post_type['supports'] ) ){
        $args['supports'][] = 'revisions';
      }

      return $args;
    }, 99, 3 );

      // For douchezaak - Check if backend.douchezaak and if so prevent frontend visit
    add_filter( 'option_epic_active_maintenance', function( $value ){
      if( function_exists('is_backend_site') && is_backend_site() ){
        $value = true;
      }
      return $value;
    }, 99);

    // For douchezaak - Check if backend.douchezaak and if so prevent frontend visit
    add_filter( 'option_epic_maintenance_type', function( $value ){
      if( function_exists('is_backend_site') && is_backend_site() ){
        $value = 'is-staging';
      }
      return $value;
    }, 99);

    // Adds the maintenance mode
    // --------------------------------
    $this->epic_maintenance_mode();
  }

  // Rewrite menu
  public function handle_rewrite() {
    $savedVersion = get_option('toolkit_version');

    if( empty( $savedVersion ) )
      $savedVersion = 0.01;

    // Rewrite default hidden pages
    if( $savedVersion < 1.31 ){
      $this->set_default_hidden_pages();
    }

    if( $savedVersion < 1.40 || $this->version >= 1.40 ){
      // Ensure the old toolkit is deactivated
      deactivate_plugins( '/egm-media-wp-toolkit/egm-media-wp-toolkit.php' );
    }

    if ( $savedVersion < 1.41 ) {
      $this->sync_old_options_to_new();
    }

    if( $savedVersion < 1.47  ){
      $this->add_expire_headers();
    }

    // If saved version is lower then current version execute functions and save the new version
    if( $savedVersion < $this->version ){
      update_option( 'toolkit_version', $this->version );
    }
  }

  // Deactivate Toolkit
  // -----------------------------------
  public function deactivate_toolkit() {
    $htaccess = get_home_path().".htaccess";

    // Remove expire headers from htaccess
    insert_with_markers( $htaccess, "EpicToolkit", array() );
  }

  // Add expire headers to .htaccess
  // -----------------------------------
  public function add_expire_headers() {
    if ( ! function_exists( 'get_home_path' ) ) {
      include_once ABSPATH . '/wp-admin/includes/file.php';
    }

    if ( ! function_exists( 'insert_with_markers' ) ) {
      include_once ABSPATH . '/wp-admin/includes/misc.php';
    }

    $htaccess = get_home_path().".htaccess";

    $lines = array();

    $lines[] = '<filesMatch ".(css|jpg|jpeg|png|gif|js|ico)$">';
    $lines[] = 'Header set Cache-Control "max-age=2592000, public"';
    $lines[] = '</filesMatch>';

    $lines[] = '<IfModule mod_expires.c>';
    $lines[] = 'ExpiresActive On';
    $lines[] = 'ExpiresByType image/jpg "access 1 year"';
    $lines[] = 'ExpiresByType image/jpeg "access 1 year"';
    $lines[] = 'ExpiresByType image/gif "access 1 year"';
    $lines[] = 'ExpiresByType image/png "access 1 year"';
    $lines[] = 'ExpiresByType text/css "access 1 month"';
    $lines[] = 'ExpiresByType text/html "modification 4 hours"';
    $lines[] = 'ExpiresByType application/pdf "access 1 month"';
    $lines[] = 'ExpiresByType text/x-javascript "access 1 month"';
    $lines[] = 'ExpiresByType application/x-shockwave-flash "access 1 month"';
    $lines[] = 'ExpiresByType image/x-icon "access 1 year"';
    $lines[] = 'ExpiresDefault "access 1 month"';
    $lines[] = '</IfModule>';

    insert_with_markers( $htaccess, "EpicToolkit", $lines );
  }

  public function sync_old_options_to_new() {
    $options_keys = [
      'egm_block_core_update,epic_block_core_update',
      'egm_block_theme_update,epic_block_theme_update',
      'egm_update_blocked_plugins,epic_update_blocked_plugins',
      'egm_maintenance_type,epic_maintenance_type',
      'egm_active_maintenance,epic_active_maintenance',
      'egm_maintenance_logo,epic_maintenance_logo',
      'egm_maintenance_text,epic_maintenance_text',
      'egm_hide_menu,epic_hide_menu',
      'egm_hide_sub_menu,epic_hide_sub_menu',
      'egm_disable_acf_sync,epic_disable_acf_sync',
      'egm_disable_cpt_sync,epic_disable_cpt_sync',
      'project_huddle_api_enable, epic_project_huddle_api_enable',
      'project_huddle_api,epic_project_huddle_api',
      'egm_maintenance_contact_us_link,epic_maintenance_contact_us_link',
      'egm_maintenance_actual_website_link,epic_maintenance_actual_website_link',
      'egm_block_seach_engines,epic_block_seach_engines',
      'egm_block_wp_mail,epic_block_wp_mail',
      'images_enable_external_source,epic_images_enable_external_source',
      'images_external_site_url,epic_images_external_site_url',
    ];

    foreach ($options_keys as $option_keys) {
      list($old_key, $new_key) = explode(',', $option_keys);
      if (
        get_option($new_key, '_missing_') == '_missing_' &&
        get_option($old_key, '_missing_') != '_missing_'
      ) {
        update_option($new_key, get_option($old_key));
      }
    }
  }

  public function handle_current_url_change() {
    $current_url = site_url();
    $old_url = get_option('epic_old_url', null);
    $settings_url = get_admin_url(null, 'options-general.php?page=epicode_options&tab=other');

    $project_huddle_api_enabled = get_option('epic_project_huddle_api_enable', false);
    $discourage_search_engines = get_option('epic_block_seach_engines', 'no') == 'yes';

    if (
      !empty($old_url) &&
      $old_url !== $current_url
    ) {
      if ($project_huddle_api_enabled || $discourage_search_engines) {
        // Send email notification
        $to = 'developer@epicode.nl';
        $subject = '%s active for: '.$current_url;
        $body = 'Website: '.$current_url.' got %s activated. <br/>Click <a href="'.$settings_url.'">here</a> or visit: '.$settings_url.' to deactivate.';

        if ($project_huddle_api_enabled) {
          $feature = 'Feedback Tool';
        } else if($discourage_search_engines) {
          $feature = 'Search Engine Visibility';
        }

        $subject = sprintf($subject, $feature);
        $body = sprintf($body, $feature);

        add_filter( 'wp_mail_content_type', array( __class__ , 'wpdocs_set_html_mail_content_type'));
        wp_mail( $to, $subject, $body );
        remove_filter( 'wp_mail_content_type', array( __class__ , 'wpdocs_set_html_mail_content_type'));

        // Saves current site url
        update_option('epic_old_url', $current_url);
      }
    }
    // var_dump($current_url, $old_url);
    // wp_die();
  }

  // Disables the CORE updates if the checkbox in backend is set
  // --------------------------------
  function epic_prevent_core_check() {
    if (get_option('epic_block_core_update')){
      # 2.3 to 2.7:
      add_action( 'init', function(){
        remove_action( 'init', 'wp_version_check' );
      }, 2 );
      add_filter( 'pre_option_update_core', '__return_null' );

      # 2.8+:
      remove_action( 'wp_version_check', 'wp_version_check' );
      remove_action( 'admin_init', '_maybe_update_core' );
      add_filter( 'pre_transient_update_core', '__return_null' );

      # 3.0+:
      add_filter( 'pre_site_transient_update_core', '__return_null' );
    }
  }

  // Disables the THEME updates if the checkbox in backend is set
  // --------------------------------
  function epic_prevent_theme_check() {
    if (get_option('epic_block_theme_update')){
      # 2.8 to 3.0:
      remove_action( 'load-themes.php', 'wp_update_themes' );
      remove_action( 'load-update.php', 'wp_update_themes' );
      remove_action( 'admin_init', '_maybe_update_themes' );
      remove_action( 'wp_update_themes', 'wp_update_themes' );
      add_filter( 'pre_transient_update_themes', '__return_null' );

      # 3.0:
      remove_action( 'load-update-core.php', 'wp_update_themes' );
      add_filter( 'pre_site_transient_update_themes', '__return_null' );
    }
  }

  // Disables selected plugin updates
  // --------------------------------
  function epic_disable_plugin_updates( $transient ) {
    $epic_update_blocked_plugins      = get_option('epic_update_blocked_plugins');
    $epic_update_blocked_plugins_array  = explode('###',$epic_update_blocked_plugins);

    if (!empty($epic_update_blocked_plugins_array)){
      foreach ($epic_update_blocked_plugins_array as $my_plugin){
        if(isset($transient->response) && !empty($transient->response)){
          unset($transient->response[$my_plugin]);
        }
      }
    }

    return $transient;
  }

  public function epic_maintenance_mode(){
    $maintenance_type = get_option('epic_maintenance_type');
    if (get_option('epic_active_maintenance')){
      // If buddypress is active use prio 9
      if(function_exists('bp_is_active')){
        add_action( 'template_redirect', array($this,'epic_get_maintenance_page'),9);
      }else{
        add_action( 'template_redirect', array($this,'epic_get_maintenance_page'));
      }
      add_action( 'admin_bar_menu', array( $this, 'epic_maintenance_admin_bar' ), 1000 );
      add_action( 'admin_head', array( $this, 'epic_maintenance_bar_styling' ));

      // Disable the rest api in maintenance mode
      $current_WP_version = get_bloginfo('version');
      if ( version_compare( $current_WP_version, '4.7', '>=' ) ) {
        add_filter( 'rest_authentication_errors', array( $this, 'epic_prevent_rest_access') );
      }
      if(strpos(get_site_url(), 'staging') !== false){
        wp_clear_scheduled_hook('epic_daily_check_staging_maintenance');
      }
    } else {
      if(strpos(get_site_url(), 'staging') !== false){
        if (! wp_next_scheduled ( 'epic_daily_check_staging_maintenance' )) {
          wp_schedule_event(time(), 'daily', 'epic_daily_check_staging_maintenance');
        }
      } else {
        wp_clear_scheduled_hook('epic_daily_check_staging_maintenance');
      }
      add_action('epic_daily_check_staging_maintenance', array( __class__, 'epic_notify_staging_maintenance'));
    }
  }

  public static function epic_notify_staging_maintenance() {
    add_filter( 'wp_mail_content_type', array( __class__ , 'wpdocs_set_html_mail_content_type'));

    $to = 'developer@epicode.nl';
    $subject = 'Maintenance mode not active for: '.get_site_url();
    $body = 'Staging website: '.get_site_url().' hasn\'t got the maintenance mode activated. <br/>Click <a href="'.get_admin_url(null, 'options-general.php?page=epicode_options').'">here</a> or visit: '.get_admin_url(null, 'options-general.php?page=epicode_options').' to turn it on.';

    wp_mail( $to, $subject, $body );

    // Reset content-type to avoid conflicts -- https://core.trac.wordpress.org/ticket/23578
    remove_filter( 'wp_mail_content_type', array( __class__ , 'wpdocs_set_html_mail_content_type'));

  }

  public static function wpdocs_set_html_mail_content_type() {
    return 'text/html';
  }

  public function epic_get_maintenance_page(){

    // If the current user is logged in don't show maintenance mode
    if(is_user_logged_in()){
      return false;
    }

    // Prevent Plugins from caching
    $this->epic_prevent_plugin_caching();

    $maintenance_type = get_option('epic_maintenance_type', 'is-staging');

    $maintenance_logo_arr = get_option('epic_maintenance_logo');
    $maintenance_logo = $maintenance_logo_arr[$maintenance_type] ?? $maintenance_logo_arr;
    if (empty($maintenance_logo)) {
      $maintenance_logo = EPIC_IMAGES_URL . '/epicode-logo.png';
    }

    $maintenance_text_arr = get_option('epic_maintenance_text');
    $maintenance_text = $maintenance_text_arr[$maintenance_type] ?? $maintenance_text_arr;

    $maintenance_contact_us_link_arr = $this->get_maintenance_contact_us_link_arr();
    $maintenance_contact_us_link = $maintenance_contact_us_link_arr[$maintenance_type];

    $maintenance_actual_website_link_arr = $this->get_maintenance_actual_website_link_arr();
    $maintenance_actual_website_link = $maintenance_actual_website_link_arr[$maintenance_type];

    // Render the template
    $page = epictk_view('maintenance/main', [
      'css'                   => EPIC_CSS_URL . '/maintenance-template.css',
      'html_title'            => esc_html(get_bloginfo( 'name' )),
      'logo'                  => esc_url($maintenance_logo, ['http', 'https']),
      'text'                  => esc_html($maintenance_text),
      'actual_website_link'   => $maintenance_actual_website_link,
      'contact_us_link'       => $maintenance_contact_us_link,
      'maintenance_type'      => $maintenance_type,
    ]);

    $this->epic_maintenance_pages_http_header($maintenance_type);

    exit($page);
  }

  // Output HTTP headers depending on the maintenance type
  // ----------------------------------
  public function epic_maintenance_pages_http_header($maintenance_type = 'is-staging') {

    switch($maintenance_type) {
      case 'is-staging':
        $status_code = 403;
        break;
      case 'is-maintenance':
        $status_code = 503;
        break;
      default:
        $status_code = null;
        break;
    }

    if(!is_null($status_code)) {
      status_header($status_code);
    }
  }

  // Prevent Plugins from caching
  // ----------------------------------
  public function epic_prevent_plugin_caching(){
    // Prevent Plugins from caching
    //
    // Disables the following caching plugins.
    //   - W3 Total Cache
    //   - WP Super Cache
    //   - ZenCache (Previously QuickCache)
    // --------------------------------------
    if(!defined('DONOTCACHEPAGE')) {
      define('DONOTCACHEPAGE', true);
    }
    if(!defined('DONOTCDN')) {
      define('DONOTCDN', true);
    }
    if(!defined('DONOTCACHEDB')) {
      define('DONOTCACHEDB', true);
    }
    if(!defined('DONOTMINIFY')) {
      define('DONOTMINIFY', true);
    }
    if(!defined('DONOTCACHEOBJECT')) {
      define('DONOTCACHEOBJECT', true);
    }
    header('Cache-Control: max-age=0; private');
  }

  // Display admin bar when active
  // ----------------------------------
  public function epic_maintenance_admin_bar($str){
    global $wp_admin_bar;

    $maintenance_type = get_option( 'epic_maintenance_type', 'is-staging' );

    if ( $maintenance_type == 'is-staging' ) {
      $message ='Staging Mode Active';
    } else if( $maintenance_type == 'is-maintenance' ) {
      $message = 'Maintenance Mode Active';
    }

    //Add the main siteadmin menu item
    $wp_admin_bar->add_menu( array(
        'id'     => 'epic-maintenance-notice',
        'href' => admin_url().'options-general.php?page=epicode_options&tab=maintenance',
        'parent' => 'top-secondary',
        'title'  => __( $message ),
        'meta'   => array( 'class' => 'maintenance-mode-is-active' ),
    ) );
  }

  public function epic_maintenance_bar_styling() {
    echo '<style> #wp-admin-bar-epic-maintenance-notice a{ background-color: #ff8447;}  #wp-admin-bar-epic-maintenance-notice:hover a{ background-color: #ff8447 !important; color: #fff !important; text-decoration: underline;}</style>';
  }

  public function epic_prevent_rest_access( $access ) {
    if( ! is_user_logged_in() ) {
      return new WP_Error( 'rest_cannot_access', __( 'Only authenticated users can access the REST API.', 'coming-soon' ), array( 'status' => rest_authorization_required_code() ) );
    }
    return $access;
  }

  public function epic_get_menu_pages() {
    global $submenu, $menu;

    $this->admin_menu = $menu;
    $this->admin_sub_menu = $submenu;
  }

  // Check the actual checkboxes on activation
  public function set_default_hidden_pages(){
    $epic_hide_menus = get_option('epic_hide_menu');

    if( !is_array( $epic_hide_menus ) )
      $epic_hide_menus = array();

    // Default hidden menu's
    $epic_default_hidden_menus = array(
      'plugins.php',
      'w3tc_dashboard'
    );

    $epic_hide_menus = array_merge( $epic_hide_menus, $epic_default_hidden_menus );

    update_option( 'epic_hide_menu', $epic_hide_menus );
  }

  public function epic_remove_menu_pages() {
    $current_user = wp_get_current_user();
    $epic_hide_menus = get_option('epic_hide_menu');
    $epic_hide_sub_menus = get_option('epic_hide_sub_menu');

    // Prevent warning if "epic_hide_menu" isn't set
    if(empty($epic_hide_menus)) $epic_hide_menus = array();

    if( !$this->currentUserIsFromEpic() ){

      if(isset($epic_hide_menus) && !empty($epic_hide_menus)){
        foreach ($epic_hide_menus as $menu) {
          remove_menu_page( $menu );
        }
      }

      if(isset($epic_hide_sub_menus) && !empty($epic_hide_sub_menus)){
        foreach ($epic_hide_sub_menus as $parent_menu => $sub_menu) {
          foreach ($sub_menu as $sub) {
            remove_submenu_page( $parent_menu, $sub );
          }
        }
      }
    }
  }

  public function epic_return_menu_pages() {
    $menu = $this->admin_menu;
    $submenu = $this->admin_sub_menu;

    $active_menus = array();

    foreach ($menu as $prio => $menu_array) {
      $menu_name = $menu_array['0'];
      $menu_slug = $menu_array['2'];

      if(isset($submenu[$menu_slug]) && !empty($submenu[$menu_slug])){
        $existing_sub = $submenu[$menu_slug];
        $active_sub_menus = array();

        if(!empty($existing_sub)){
          foreach ($existing_sub as $sub_prio => $sub_menu_array) {
            $sub_menu_name = $sub_menu_array['0'];
            $sub_menu_slug = $sub_menu_array['2'];

            if($sub_menu_name == 'Header' ||
            $sub_menu_slug == 'custom-header' ||
            $sub_menu_name == __('Background') ||
            $sub_menu_slug == 'epicode_options'){
              continue;
            }

            $active_sub_menus[] = [
              'name'  => $sub_menu_name,
              'slug'  => $sub_menu_slug,
            ];
          }
        }
      }

      $active_menus[] = [
        'name'     => $menu_name,
        'slug'     => $menu_slug,
        'sub_menu' => $active_sub_menus
      ];
    }

    return $active_menus;
  }

  // Sync ACF changes to the "acf-json" folder
  // ------------------------------------------
  public function epic_acf_json_save_point( $path ) {
    if(!get_option('epic_disable_acf_sync')){
      if( wp_mkdir_p( EPIC_SYNC_PATH . '/acf-json' ) ){
        $path = EPIC_SYNC_PATH . '/acf-json';
      }
    }
    return $path;
  }

  // Load ACF local json from the "acf-json" folder
  // ------------------------------------------
  public function epic_acf_json_load_point( $paths ) {
    if(!get_option('epic_disable_acf_sync')){
      if( wp_mkdir_p( EPIC_SYNC_PATH . '/acf-json' ) ){
        $paths[] = EPIC_SYNC_PATH . '/acf-json';
      }
    }
    return $paths;
  }

  // Notify about the new ACF json files ready for sync
  // --------------------------------------------------
  public static function epic_notify_about_acf_availble_sync() {
    if(!get_option('epic_disable_acf_sync')){
      if(function_exists('acf_maybe_get') && function_exists('acf_maybe_get')){
        $groups = acf_get_field_groups();
        $sync   = array();

        // bail early if no field groups
        if( empty( $groups ) )
          return;

        // find JSON field groups which have not yet been imported
        foreach( $groups as $group ) {

          $local    = acf_maybe_get( $group, 'local', false );
          $modified = acf_maybe_get( $group, 'modified', 0 );
          $private  = acf_maybe_get( $group, 'private', false );

          if( $local !== 'json' || $private ) {
            // Private field groups don't get notified about sync
          } elseif( ! $group[ 'ID' ] ) {

            $sync[ $group[ 'key' ] ] = $group;

          } elseif( $modified && $modified > get_post_modified_time( 'U', true, $group[ 'ID' ], true ) ) {

            $sync[ $group[ 'key' ] ]  = $group;
          }
        }

        // bail if no sync needed
        if( empty( $sync ) )
          return;

        if( ! empty( $sync ) && static::currentUserIsFromEpic()) {

          $class = 'notice notice-epic';
          $message = sprintf( __( 'There are ACF field groups ready to be synced, go <a href="%s">here</a> to sync them now' ), admin_url( 'edit.php?post_type=acf-field-group&post_status=sync' ));

          printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );

          // Auto Sync function, off for now might created a on/off button in backend
          // -------------------------------------------------------------------------
            // $new_ids = array();
            //
            // foreach( $sync as $key => $v ) { //foreach( $keys as $key ) {
            //
            //  // append fields
            //  if( acf_have_local_fields( $key ) ) {
            //
            //    $sync[ $key ][ 'fields' ] = acf_get_local_fields( $key );
            //
            //  }
            //  // import
            //  $field_group = acf_import_field_group( $sync[ $key ] );
            // }
        }
      }
    }
  }

  // Will retrieve the CPTUI post types and writes them to a file
  // -----------------------------------------------------------
  public function move_sync_folder() {

    // Does the deprecated folder still exist ?
    if( is_dir( EPIC_INC_PATH . '/cpt' ) ){

      // Create the new CPT sync folder if it doesn't already exist
      if( wp_mkdir_p( EPIC_SYNC_PATH . '/cpt' ) ){

        $oldLocation = EPIC_INC_PATH . '/cpt';
        $newLocation = EPIC_SYNC_PATH . '/cpt';

        // if cpt is only present in deprecated folder move it to new folder
        if( !file_exists( $newLocation . '/post-types.php' ) && file_exists( $oldLocation . '/post-types.php' ) ) {
          $this->move_files( $oldLocation, $newLocation );
          @rmdir( $oldLocation );
        } elseif( file_exists( $newLocation . '/post-types.php' ) ) {
          $this->delete_content( $oldLocation );
          @rmdir( $oldLocation );
        }
      }
    }

    // Does the deprecated folder still exist ?
    if( is_dir( EPIC_INC_PATH . '/acf-json' ) ){

      // Create the new ACF sync folder if it doesn't already exist
      if( wp_mkdir_p( EPIC_SYNC_PATH . '/acf-json' ) ){

        $oldLocation = EPIC_INC_PATH . '/acf-json';
        $newLocation = EPIC_SYNC_PATH . '/acf-json';

        @unlink( $oldLocation . '/info.txt' );
        $this->move_files( $oldLocation, $newLocation );
        @rmdir( $oldLocation );
      }
    }

    // After renaming to epicode

    // Does the deprecated folder still exist ( egm-sync ) ?
    if( is_dir( EGM_SYNC_PATH . '/cpt' ) ){

      // Create the new CPT sync folder if it doesn't already exist
      if( wp_mkdir_p( EPIC_SYNC_PATH . '/cpt' ) ){

        $oldLocation = EGM_SYNC_PATH . '/cpt';
        $newLocation = EPIC_SYNC_PATH . '/cpt';

        // if cpt is only present in deprecated folder move it to new folder
        if( !file_exists( $newLocation . '/post-types.php' ) && file_exists( $oldLocation . '/post-types.php' ) ) {
          $this->move_files( $oldLocation, $newLocation );
          @rmdir( $oldLocation );
        } elseif( file_exists( $newLocation . '/post-types.php' ) ) {
          $this->delete_content( $oldLocation );
          @rmdir( $oldLocation );
        }
      }
    }

    // Does the deprecated folder still exist ?
    if( is_dir( EGM_SYNC_PATH . '/acf-json' ) ){

      // Create the new ACF sync folder if it doesn't already exist
      if( wp_mkdir_p( EPIC_SYNC_PATH . '/acf-json' ) ){

        $oldLocation = EGM_SYNC_PATH . '/acf-json';
        $newLocation = EPIC_SYNC_PATH . '/acf-json';

        @unlink( $oldLocation . '/info.txt' );
        $this->move_files( $oldLocation, $newLocation );
        @rmdir( $oldLocation );
      }
    }

    if ( is_dir( EGM_SYNC_PATH ) ) {
      @rmdir( EGM_SYNC_PATH );
    }
  }

  // Will move files to diffrent folder
  // ----------------------------------------
  private function move_files( $src, $dest ){
    $src  = rtrim( $src, '/' );
    $dest = rtrim( $dest, '/' );

    // Get array of all source files
    $files = scandir( $src );

    // to delete items
    $delete = [];

    // Cycle through all source files
    foreach ($files as $file) {
      // if filename = "." or ".." continue
      if ( in_array( $file, array('.', '..') ) ) continue;

      // If we copied this successfully, mark it for deletion
      if (copy($src . '/' . $file, $dest . '/' . $file)) {
        $delete[] = $src . '/' . $file;
      }
    }

    // Delete all successfully-copied files
    foreach ($delete as $file) {
      unlink($file);
    }
  }

  // Will remove entire content of folder except for the folder itself
  // -----------------------------------------------------------
  private function delete_content($path){
    try{
      $iterator = new DirectoryIterator($path);
      foreach ( $iterator as $fileinfo ) {
        if($fileinfo->isDot())continue;
        if($fileinfo->isDir()){
          if(deleteContent($fileinfo->getPathname()))
            @rmdir($fileinfo->getPathname());
        }
        if($fileinfo->isFile()){
          @unlink($fileinfo->getPathname());
        }
      }
    } catch ( Exception $e ){
       // Output ?
       return false;
    }
    return true;
  }

  // Will retrieve the CPTUI post types and writes them to a file
  // -----------------------------------------------------------
  public static function epic_handle_cptui_post_types() {

    if (!get_option('epic_disable_cpt_sync')){
      $cptui_data_types = [
        'post-types' => [
          'code_function' => 'cptui_get_post_type_code',
          'code_data_function' => 'cptui_get_post_type_data',
          'filename' => 'post-types',
          'empty_message' => __( 'No post types to display at this time', 'custom-post-type-ui' ),
        ],
        'taxonomies' => [
          'code_function' => 'cptui_get_taxonomy_code',
          'code_data_function' => 'cptui_get_taxonomy_data',
          'filename' => 'taxonomies',
          'empty_message' => __( 'No taxonomies to display at this time', 'custom-post-type-ui' )
        ]
      ];

      foreach($cptui_data_types as $cptui_data_type) {
        if (!function_exists($cptui_data_type['code_function']) || !function_exists($cptui_data_type['code_data_function'])) {
          continue;
        }

        ob_start();
          @$cptui_data_type['code_function']($cptui_data_type['code_data_function']());
        $cpt_php = ob_get_clean();

        if(!empty($cpt_php)){
          $file = EPIC_SYNC_PATH . "/cpt/{$cptui_data_type['filename']}.php";
          $file_content = '';
          if($cpt_php != __( $cptui_data_type['empty_message'], 'custom-post-type-ui' )){
            $file_content = '<?php '.$cpt_php.'?>';
          }

          if(file_exists($file)) {
            file_put_contents($file, $file_content);
          } elseif( wp_mkdir_p( EPIC_SYNC_PATH . '/cpt' ) ) {
            // Create the file
            $newfile = fopen( $file, 'w' );
            $newfile = fwrite( $newfile, $file_content );
          }
        }
      }
    }
  }

  // Checks if there are any post types added to the post-types.php file
  // If so notify the Developer that he/she can turn off CPT
  // ------------------------------------------
  public static function epic_notify_about_cptui() {

    if (!get_option('epic_disable_cpt_sync')){
      $cptui_data_types = [
        'post-types' => [
          'label' => 'post types',
          'filename' => 'post-types'
        ],
        'taxonomies' => [
          'label' => 'taxonomies',
          'filename' => 'taxonomies'
        ],
      ];

      $cptui_synced_data_arr = [];

      foreach($cptui_data_types as $cptui_data_type) {
        $file = EPIC_SYNC_PATH . "/cpt/{$cptui_data_type['filename']}.php";

        if(!file_exists($file))return;

        $linecount = 0;
        $handle = fopen($file, "r");

        // Read the CPTUI synced file if there are more than 5 lines
        while(!feof($handle)){
          $line = fgets($handle);
          $linecount++;
          if($linecount >= 5){
            break;
          }
        }

        fclose($handle);

        if($linecount >= 5) {
          $cptui_synced_data_arr[] = $cptui_data_type['label'];
        }
      }

      // Notify the Developer about the CPTUI Sync status
      if(is_plugin_active('custom-post-type-ui/custom-post-type-ui.php') && count($cptui_synced_data_arr) && static::currentUserIsFromEpic() ) {
        $class = 'notice notice-epic';

        // We only know 2 types of data to be synced, post types and taxonomies
        $cptui_synced_data_string = join(" and ", $cptui_synced_data_arr);
        // $cptui_synced_data_check_count = 2;
        // if(count($cptui_synced_data_arr) > $cptui_synced_data_check_count) {
        //  $cptui_synced_data_string = join(" and ", array_slice($cptui_synced_data_arr, 0, $cptui_synced_data_check_count));
        //  $cptui_synced_data_string .= ', ' . join(", ", array_slice($cptui_synced_data_arr, $cptui_synced_data_check_count));
        // }
        $message = __( 'The CPTUI '.$cptui_synced_data_string.' were succesfully synced to the Epicode plugin, you can turn off CPTUI if you want. <strong style="color: #ff8447;">Don\'t forget to add CPTUI to your .gitignore it\'s not needed on Staging/Production</strong>.' );

        printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
      }
    }
  }

  // Checks if there are any post types added to the post-types.php file
  // If so notify the Developer that he/she can turn off CPT
  // ------------------------------------------
  public static function epic_project_huddle_api() {
    if(get_option('epic_project_huddle_api_enable')){
      wp_register_script('project-huddle-api-js', EPIC_JS_URL . '/project-huddle-api.js', [], '1.1', true);
      wp_localize_script('project-huddle-api-js', 'epic_project_huddle_api', [
        'src' => esc_url(get_option('epic_project_huddle_api'))
      ]);
      wp_enqueue_script('project-huddle-api-js');
    }
  }

  public static function epic_admin_js() {
    wp_register_script('admin-maintenance-type-selector-js', EPIC_JS_URL . '/admin/maintenance-type-selector.js', [], '1.0', true);
    wp_enqueue_script('admin-maintenance-type-selector-js');

    wp_register_script('admin-error-reporting-sentry-io', EPIC_JS_URL . '/admin/error-reporting-sentry-io.js', [], '1.0', true);
    wp_enqueue_script('admin-error-reporting-sentry-io');
  }

  // Backend settings
  //
  // Settings like Menu placement, backend page, registerd settings
  // --------------------------------

    //Add some global CSS to the backend
    // --------------------------------
    public static function epic_custom_styles() {
      echo '<style>.notice-epic{border-left-color: #ff8447;}</style>';
    }

    //Add settings button in plugin overview page
    // --------------------------------
    function epic_plugin_action_links( $links ) {
      $links[] = '<a href="'. esc_url( get_admin_url(null, 'options-general.php?page=epicode_options') ) .'">Settings</a>';
      return $links;
    }

    //Register our setting
    // --------------------------------
    function register_epicsettings() {
      // Settings for blocking updates
      register_setting('epic-settings-group', 'epic_disable_cpt_sync');
      register_setting('epic-settings-group', 'epic_disable_acf_sync');
      register_setting('epic-settings-group', 'epic_block_core_update');
      register_setting('epic-settings-group', 'epic_update_blocked_plugins');
      register_setting('epic-settings-group', 'epic_block_theme_update');

      // Settings for the maintenance mode
      register_setting('epic-settings-group', 'epic_active_maintenance');
      register_setting('epic-settings-group', 'epic_maintenance_logo');
      register_setting('epic-settings-group', 'epic_maintenance_text');

      // Settings for the other functions
      register_setting('epic-settings-group', 'epic_hide_menu');
      register_setting('epic-settings-group', 'epic_hide_sub_menu');
      register_setting('epic-settings-group', 'epic_block_wp_mail');
      register_setting('epic-settings-group', 'epic_project_huddle_api_enable');
      register_setting('epic-settings-group', 'epic_project_huddle_api');

    }

    // Register menu item
    // --------------------------------
    public function epic_adminpage(){
      if ($this->currentUserIsFromEpic()) {
        add_options_page( 'Epicode', 'Epicode', 'manage_options', 'epicode_options', array($this, 'epicode_options'));
      }
    }

    public function epic_handle_postdata(){

      $page = $_POST['page'];

      // Posted value's for Welcome page
      if($page == 'welcome'){
        $disable_cpt = (isset($_POST['disable_cpt_sync']) && !empty($_POST['disable_cpt_sync']) ? $_POST['disable_cpt_sync'] : '');
        if($disable_cpt){
          update_option('epic_disable_cpt_sync', $disable_cpt);
        } else {
          update_option('epic_disable_cpt_sync', '');
        }

        $disable_acf = (isset($_POST['disable_acf_sync']) && !empty($_POST['disable_acf_sync']) ? $_POST['disable_acf_sync'] : '');
        if($disable_acf){
          update_option('epic_disable_acf_sync', $disable_acf);
        } else {
          update_option('epic_disable_acf_sync', '');
        }
      }

      // Posted value's for Block Updates page
      if($page == 'block-updates'){

        $disable_plugin_installation = (isset($_POST['disable_plugin_installation']) && !empty($_POST['disable_plugin_installation']) ? $_POST['disable_plugin_installation'] : '');
        if($disable_plugin_installation){
          update_option('epic_disable_plugin_installation', $disable_plugin_installation);
        } else {
          update_option('epic_disable_plugin_installation', '');
        }


        $core_blocked = (isset($_POST['block_core']) && !empty($_POST['block_core']) ? $_POST['block_core'] : '');
        if($core_blocked){
          update_option('epic_block_core_update', $core_blocked);
        } else {
          update_option('epic_block_core_update', '');
        }

        $theme_blocked = (isset($_POST['block_theme']) && !empty($_POST['block_theme']) ? $_POST['block_theme'] : '');
        if($theme_blocked){
          update_option('epic_block_theme_update', $theme_blocked);
        } else {
          update_option('epic_block_theme_update', '');
        }

        if(isset($_POST['block_plugin_updates'])){
          $blocked_plugins = join('###',$_POST['block_plugin_updates']);
          update_option('epic_update_blocked_plugins', $blocked_plugins);
          delete_option('_site_transient_update_plugins');
        } else {
          update_option('epic_update_blocked_plugins', '');
          delete_option('_site_transient_update_plugins');
        }
      }

      // Posted value's for Maintenance page
      if($page == 'maintenance'){
        $maintenance_type = $_POST['maintenance_type'];
        $is_reactive_type = in_array( $maintenance_type, ['is-reactive-maintenance', 'is-reactive-staging'] );
        // Disable or enable maintenance
        $epic_active_maintenance =  $maintenance_type == 'none' || $is_reactive_type ? 0 : 1 ;
        update_option('epic_active_maintenance', $epic_active_maintenance);

        update_option('epic_maintenance_type', $_POST['maintenance_type']);

        $maintenance_types = ['is-staging', 'is-maintenance'];

        $maintenance_fields = [
          'maintenance_text'            => 'epic_maintenance_text',
          'maintenance_logo'            => 'epic_maintenance_logo',
          'maintenance_contact_us_link' => 'epic_maintenance_contact_us_link',
          'maintenance_actual_website_link' => 'epic_maintenance_actual_website_link'
        ];

        foreach ( $maintenance_types as $maintenance_type ) {
          //@NOTE $maintenance_type overrides the first declared $maintenance_type
          foreach ( $maintenance_fields as $maintenance_form_field => $maintenance_db_field ) {
            $maintenance_field_value = $_POST[$maintenance_form_field][$maintenance_type] ?? '';
            $old_epic_field_arr = get_option($maintenance_db_field);
            if ( is_array( $old_epic_field_arr ) ) {
              unset($old_epic_field_arr[$maintenance_type]);
            }
            $new_epic_field_arr = [$maintenance_type => $maintenance_field_value];
            if(is_array($old_epic_field_arr)) {
              $new_epic_field_arr = array_merge($new_epic_field_arr, $old_epic_field_arr);
            }
            update_option($maintenance_db_field, $new_epic_field_arr);
          }
        }

        // Reactive maintenance duration
        update_option( 'epic_reactive_maintenance_duration', $_POST['reactive_maintenance_duration'] );
        if ( $is_reactive_type ) {
          update_option( 'epic_reactive_maintenance_start_time', time() );
        }
      }

      if($page == 'error-reporting') {
        $this->sentryIO->handleFormSave();
      }

      // Posted value's for Other page
      if($page == 'other'){
        if(isset($_POST['hide_menu_items']) && !empty($_POST['hide_menu_items'])){
          update_option('epic_hide_menu', $_POST['hide_menu_items']);
        } else {
          update_option('epic_hide_menu', array());
        }

        if(isset($_POST['hide_submenu_items']) && !empty($_POST['hide_submenu_items'])){
          update_option('epic_hide_sub_menu', $_POST['hide_submenu_items']);
        } else {
          update_option('epic_hide_sub_menu', array());
        }

        update_option('epic_block_seach_engines', $_POST['block_seach_engines']);
        $this->allow_search_engines_indexing( $_POST['block_seach_engines'] == 'no' );

        if(isset($_POST['block_wp_mail']) && !empty($_POST['block_wp_mail'])){
          update_option('epic_block_wp_mail', $_POST['block_wp_mail']);
        } else {
          update_option('epic_block_wp_mail', '');
        }

        update_option( 'epic_images_enable_external_source', $_POST['images_enable_external_source'] );
        update_option( 'epic_images_external_site_url', $_POST['images_external_site_url'] );

        $project_huddle_api = (isset($_POST['project_huddle_api']) && !empty($_POST['project_huddle_api']) ? $_POST['project_huddle_api'] : '');
        update_option('epic_project_huddle_api', $project_huddle_api);

        if(isset($_POST['project_huddle_api_enable']) && !empty($_POST['project_huddle_api_enable'])){
          update_option('epic_project_huddle_api_enable', $_POST['project_huddle_api_enable']);
        } else {
          update_option('epic_project_huddle_api_enable', '');
        }

        register_setting('epic-settings-group', 'epic_project_huddle_api_enable');
        register_setting('epic-settings-group', 'epic_project_huddle_api');

        // Disable WP REST API
        update_option( 'epic_wp_rest_api_disabled', $_POST['epic_wp_rest_api_disabled'] );
      }

      $redirect_url = admin_url( "options-general.php?page=epicode_options&tab=$page&success=true" );

      wp_redirect( $redirect_url ); exit;
    }

    protected function allow_search_engines_indexing( $allow = true ) {
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

      $robots_txt_path = ABSPATH . 'robots.txt';

      if ( $allow ) {
        update_option('blog_public', '1');
        $robots_txt_rules = "User-agent: *\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php";
      } else {
        update_option('blog_public', '0');
        $robots_txt_rules = "User-agent: *\nDisallow: /";
      }

      if ( false === $wp_filesystem->put_contents( $robots_txt_path, $robots_txt_rules ) ) {
        wp_die( 'Unable to create/update robots.txt. Please check directory or file permissions.' );
      }
    }

    protected function get_maintenance_contact_us_link_arr() {
      $default_contact_link = 'mailto:support@epicode.nl';
      return get_option( 'epic_maintenance_contact_us_link', [
        'is-maintenance' => $default_contact_link,
        'is-staging' => $default_contact_link
      ] );
    }

    protected function get_maintenance_actual_website_link_arr() {
      return get_option( 'epic_maintenance_actual_website_link', [
        'is-maintenance' => '',
        'is-staging' => ''
      ] );
    }

    // Retrieves the backend settings page
    public function epicode_options() {

      $epic_save_MSG = '';
      if ( isset( $_GET['success'] ) && $_GET['success'] == 'true' ){
        $epic_save_MSG = 'Settings have been successfully updated.';
      }

      $current_tab = $_GET['tab'] ?? 'welcome';

      $view_data = [
        'tab_content_view'  => "dashboard/tab-contents/$current_tab",
        'tab_content_data'  => [],
        'current_user'      => wp_get_current_user(),
        'tabs' => [
          'welcome'         => 'Welcome',
          'maintenance'     => 'Maintenance',
          'block-updates'   => 'Block Updates',
          'error-reporting' => 'Error Reporting',
          'other'           => 'Other',
        ],
        'current_tab'   => $current_tab,
        'epic_save_MSG'   => $epic_save_MSG
      ];

      switch( $current_tab ) {
        case 'welcome':
          $epic_disable_cpt_sync              = get_option( 'epic_disable_cpt_sync' );
          $epic_disable_acf_sync              = get_option( 'epic_disable_acf_sync' );

          $view_data['tab_content_data'] = array_merge(
            $view_data['tab_content_data'],
            compact( 'epic_disable_cpt_sync', 'epic_disable_acf_sync' )
          );
          break;

        case 'maintenance':
          $epic_maintenance_active                = get_option( 'epic_active_maintenance' );
          $epic_maintenance_type                  = get_option( 'epic_maintenance_type', ( $epic_maintenance_active ? 'is-staging' : 'none' ) );
          $epic_maintenance_text_arr              = get_option( 'epic_maintenance_text' );
          $epic_maintenance_logo_arr              = get_option( 'epic_maintenance_logo' );
          $epic_reactive_maintenance_duration_arr = get_option( 'epic_reactive_maintenance_duration', 0 );

          $epic_maintenance_contact_us_link_arr  = $this->get_maintenance_contact_us_link_arr();
          $epic_maintenance_actual_website_link_arr  = $this->get_maintenance_actual_website_link_arr();

          $view_data['tab_content_data'] = array_merge(
            $view_data['tab_content_data'],
            compact(
              'epic_maintenance_active',
              'epic_maintenance_type',
              'epic_maintenance_text_arr',
              'epic_maintenance_logo_arr',
              'epic_maintenance_contact_us_link_arr',
              'epic_maintenance_actual_website_link_arr',
              'epic_reactive_maintenance_duration_arr'
            )
          );
          break;

        case 'block-updates':
          $plugins                          = get_plugins();
          $epic_disable_plugin_installation = get_option( 'epic_disable_plugin_installation' );
          $epic_core_blocked                = get_option( 'epic_block_core_update' );
          $epic_theme_blocked               = get_option( 'epic_block_theme_update' );
          $epic_update_blocked_plugins      = get_option( 'epic_update_blocked_plugins' );
          $epic_update_blocked_plugins_array  = explode( '###', $epic_update_blocked_plugins );

          $view_data['tab_content_data'] = array_merge(
            $view_data['tab_content_data'],
            compact(
              'plugins',
              'epic_disable_plugin_installation',
              'epic_core_blocked',
              'epic_theme_blocked',
              'epic_update_blocked_plugins_array'
            )
          );
          break;

        case 'error-reporting':
          $sentryIO           = new \Epic_Toolkit\SentryIO;
          $data_settings      = $sentryIO->data_settings;
          $environment_types  = $sentryIO->environment_types;

          $view_data['tab_content_data'] = array_merge(
            $view_data['tab_content_data'],
            compact(
              'data_settings',
              'environment_types'
            )
          );
          break;

        case 'other':
          $epic_hide_menu_array           = get_option( 'epic_hide_menu', [] );
          $epic_hide_sub_menu_array       = get_option( 'epic_hide_sub_menu', [] );
          $menu_pages                     = $this->epic_return_menu_pages();
          $epic_block_seach_engines       = get_option( 'epic_block_seach_engines', 'no' );
          $epic_block_wp_mail             = get_option( 'epic_block_wp_mail', '' );
          $images_enable_external_source  = get_option( 'epic_images_enable_external_source', 'no' );
          $images_external_site_url       = get_option( 'epic_images_external_site_url', '' );
          $epic_project_huddle_api        = get_option( 'epic_project_huddle_api', '' );
          $epic_project_huddle_api_enable = get_option( 'epic_project_huddle_api_enable', '' );
          $epic_wp_rest_api_disabled      = get_option( 'epic_wp_rest_api_disabled', 'no' );

          $view_data['tab_content_data'] = array_merge(
            $view_data['tab_content_data'],
            compact(
              'epic_hide_menu_array',
              'epic_hide_sub_menu_array',
              'menu_pages',
              'epic_block_seach_engines',
              'epic_block_wp_mail',
              'images_enable_external_source',
              'images_external_site_url',
              'epic_project_huddle_api',
              'epic_project_huddle_api_enable',
              'epic_wp_rest_api_disabled'
            )
          );
          break;

        default:
          break;
      }

      $view = epictk_view( 'dashboard/main', $view_data );

      if ( ! $this->currentUserIsFromEpic() ) {
        $view = __('Restricted access, only accessible for Epicode!');
      }

      echo $view;
    }

    // Notify the User that the Email function is disabled
    // ------------------------------------------
    public static function epic_notify_about_blocked_mail() {
      echo epictk_view('notify-about-blocked-mail', [
        'class' => 'notice notice-epic',
        'url' => admin_url( 'options-general.php?page=epicode_options&tab=other' )
      ]);
    }

    // Check if user is from Epicode via email address
    // checking.
    // ------------------------------------------
    public static function currentUserIsFromEpic() {
      $current_user = wp_get_current_user();
      // Adds egm-media.com, egmmedia.com,egmmedia.nl email domains for legacy
      return preg_match("/@egmmedia\.nl|@egmmedia\.nl|@egmmedia\.com|@egmmedia\.com|@egm-media\.com|@egm-media\.com|@epicode\.com|@epicode\.nl/", $current_user->user_email);
    }

    public static function imagesExternalSource() {
      $images_enable_external_source  = get_option( 'epic_images_enable_external_source', 'no' );
      $images_external_site_url       = get_option( 'epic_images_external_site_url', '' );

      if ( $images_enable_external_source == 'yes' && !empty($images_external_site_url) ) {
        // @NOTE: A bit hacky, affects the upload process, but much faster.
        if ( ! isset( $_REQUEST['async-upload'] ) ) {
          add_filter('upload_dir', function( $dir_info ) use( $images_external_site_url ) {
            $dir_info['baseurl'] = trim($images_external_site_url, '/') . '/wp-content/uploads';
            return $dir_info;
          });

          add_action( 'admin_notices',  function() use ( $images_external_site_url ){
            $class = 'notice notice-epic';
            $message = "External image sources is turned on, images are loaded from $images_external_site_url.";

            printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
          });
        }
      }
    }

    // Disables WP REST API
    // ------------------------------------------
    public function disableWPRESTAPI() {
      add_filter( 'rest_authentication_errors', function( $result ) {
        if ( !empty( $result ) ) {
          return $result;
        }

        if( get_option( 'epic_wp_rest_api_disabled', 'no' ) == 'yes' && !is_user_logged_in() ) {
          return new WP_Error( 'epic_wp_rest_api_disabled', 'WP REST API is disabled.', ['status' => 401] );
        }

        return $result;
      });
    }

    // Reactive maintenance/staging
    // ------------------------------------------
    public function reactiveMaintenance() {
      $maintenance_type = get_option( 'epic_maintenance_type' );
      $start_time = get_option( 'epic_reactive_maintenance_start_time' );

      if ( ! in_array( $maintenance_type, ['is-reactive-maintenance', 'is-reactive-staging'] ) ) return;

      if ( ! wp_next_scheduled( 'epic_reactive_maintenance' ) ) {
        wp_schedule_event( $start_time, 'hourly', 'epic_reactive_maintenance' );
      }

      add_action( 'epic_reactive_maintenance', function() use( $maintenance_type, $start_time ){
        $duration = get_option( 'epic_reactive_maintenance_duration' )[$maintenance_type];
        $past_duration = (time() - $start_time)/3600;

        if ( $past_duration > $duration ) {
          // Enable maintenace/staging modes
          update_option( 'epic_active_maintenance', 1 );

          // Enable maintenace/staging modes based in the current reactive type
          switch ( $maintenance_type ) {
            case 'is-reactive-maintenance':
              update_option( 'epic_maintenance_type', 'is-maintenance' );
              break;
            case 'is-reactive-staging':
              update_option( 'epic_maintenance_type', 'is-staging' );
              break;
            default:
              break;
          }
          // Unshedule epic_reactive_maintenance event
          wp_clear_scheduled_hook( 'epic_reactive_maintenance' );

          // Email notification
          $to = 'developer@epicode.nl';
          $subject = 'Reactive maintenance/staging mode for ' . get_bloginfo( 'name' );
          $body = sprintf(
            '%s is over. %s mode has been enabled for %s (%s).',
            $maintenance_type == 'is-reactive-maintenance' ? 'Reactive maintenance' : 'Reactive staging',
            $maintenance_type == 'is-reactive-maintenance' ? 'Maintenance' : 'Staging',
            get_bloginfo( 'name' ),
            get_site_url()
          );
          wp_mail( $to, $subject, $body );
        }
      } );
    }

    public function disablePluginInstallation() {
      if ( get_option('epic_disable_plugin_installation') && !static::currentUserIsFromEpic() ) {
        add_filter('user_has_cap', function($allcaps, $caps, $args, $instance) {
          unset($allcaps['install_plugins']);
          return $allcaps;
        }, 10, 4);
      }
    }
}

// Constants
include dirname(__FILE__) . '/includes/constants.php';
// Helpers
include dirname(__FILE__) . '/includes/helpers.php';
// Classes
include dirname(__FILE__) . '/includes/classes/SentryIO.php';

if ( class_exists('Epic_WordpressOptions') ) {
  $Epic_WordpressOptions = new Epic_WordpressOptions();

  if(get_option('epic_block_wp_mail')) {
    if (!function_exists('wp_mail')) {
      function wp_mail(){}
      add_action( 'admin_notices', array('Epic_WordpressOptions', 'epic_notify_about_blocked_mail') );
    }
  }

  if (!get_option('epic_disable_cpt_sync')) {
    // Includes the CPT generated by CPT
    $cptui_synced_files_paths = [
      EPIC_SYNC_PATH . '/cpt/post-types.php',
      EPIC_SYNC_PATH . '/cpt/taxonomies.php'
    ];

    foreach ( $cptui_synced_files_paths as $cptui_synced_file_path ) {
      if(file_exists($cptui_synced_file_path)) {
        include($cptui_synced_file_path);
      }
    }
  }
}
