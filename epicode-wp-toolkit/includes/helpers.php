<?php

// Exit if accessed directly
// --------------------------------
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * A simple helper for displaying templates
 * by specifying path and passing array of
 * variables
 *
 * @param string $view_path Path to view file
 * @param array $view_data Array of variables exposed to the included file
 *
 * @author Kim Maravilla <kim@epicode.nl>
 * @return string
 */

if ( ! function_exists( 'epictk_view' ) ) {
  function epictk_view($view_path, $view_data = []) {
    extract($view_data);
    $view_path = EPIC_TEMPLATES_PATH . '/' .$view_path . '.php';
    if(file_exists($view_path)){
      ob_start();
      include $view_path;
      $output = ob_get_contents();
      ob_end_clean();
      return $output;
    }
    wp_die("Template `$view_path` not found.");
  }
}

// Icons sprite
// --------------------------------
if ( ! function_exists( 'epictk_icons_sprite' ) ) {
  function epictk_icons_sprite() {
    $icons_sprite = plugin_dir_path(dirname(__FILE__)) . '/assets/images/icons-sprite.svg';
    
    // Stop if there is no icons sprite
    if (!file_exists($icons_sprite)) return;

    echo '<div style="display: none !important;">';
      include($icons_sprite);
    echo '</div>';
  }
}


// Print an icon
// --------------------------------
if ( ! function_exists( 'epictk_the_icon' ) ) {
  function epictk_the_icon($icon, $classes = []) {
    echo epictk_get_icon($icon, $classes);
  }
}

if ( ! function_exists( 'epictk_get_icon' ) ) {
  function epictk_get_icon($icon, $classes = []) {
    $classes_str = isset($classes[0]) ? ' ' . implode(' ', $classes) : '';

    ob_start(); ?>

    <svg class="icon<?= $classes_str; ?>">
      <use xlink:href="#<?= $icon; ?>"></use>
    </svg>

  <?php return ob_get_clean();
  }
}
