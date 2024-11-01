<?php
/*
Plugin Name: Example module
Plugin URI: https://cba.fro.at/uvisualize
Description: Bootstrap for your own visualizations, replace "example" with your own module name
Author: Ralf Traunsteiner, Ingo Leindecker
Author URI:
*/


/**
 * Adds a description to a module
 * The array key is the directory name of the module
 */
function uvis_register_example_module( $modules ) {

  $modules["example"]["title"] = "Example Module";
  $modules["example"]["description"] = "Bootstrap your own visualization by using this as a template";

  return $modules;

}
add_filter( "uvis_register_module", "uvis_register_example_module" );



/**
 * Adds configuration fields and their default values to the uvis datamodel
 * and makes it available to the JS uvisData.config object used by scripts located in
 *   wp-content/plugins/uvis/modules/examples/js/
 * and to the control panel template located in
 *   wp-content/plugins/uvis/modules/example/templates/settings.html
 */
function uvis_register_example_datamodel( $datamodel ) {

  // Some Example settings
  $datamodel["uvis_example_setting"] = "FooBar";

  // Some color setting
  $datamodel["uvis_example_color"] = "#FFCC00";

  // Default Font
  $datamodel["uvis_example_default_font"] = "Arial";

  return $datamodel;

}
add_filter( "uvis_datamodel", "uvis_register_example_datamodel" );


/**
 * Adds styles for the example in backend and frontend
 */
function uvis_example_enqueue_styles() {

  wp_register_style( 'uvis-example', UVIS_MODULES_URL . 'example/css/example.css', array(), UVIS_VERSION );
  wp_enqueue_style( 'uvis-example' );

}
add_action( "uvis_enqueue_styles", "uvis_example_enqueue_styles" );

?>