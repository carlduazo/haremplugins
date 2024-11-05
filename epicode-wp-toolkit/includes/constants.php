<?php

// Exit if accessed directly
// --------------------------------
if ( ! defined( 'ABSPATH' ) ) exit;

// Constants
define('EPIC_PLUGIN_DIR_URL', trim(plugin_dir_url(dirname(__FILE__)), '/'));
define('EPIC_PLUGIN_DIR_PATH', plugin_dir_path(dirname(__FILE__)));
define('EPIC_ASSETS_URL', EPIC_PLUGIN_DIR_URL . '/assets');
define('EPIC_JS_URL', EPIC_ASSETS_URL . '/js');
define('EPIC_CSS_URL', EPIC_ASSETS_URL . '/css');
define('EPIC_IMAGES_URL', EPIC_ASSETS_URL . '/images');
define('EPIC_INC_PATH', EPIC_PLUGIN_DIR_PATH . 'includes');
define('EPIC_SYNC_PATH', WP_CONTENT_DIR . '/epicode-sync');
define('EGM_SYNC_PATH', WP_CONTENT_DIR . '/egm-media-sync');
define('EPIC_TEMPLATES_PATH', EPIC_INC_PATH . '/templates');
