<?php
/*
Plugin Name: Layers
Plugin URI: https://cba.fro.at/uvisualize
Description: Visualize posts on layers
Author: Ralf Traunsteiner, Ingo Leindecker
Author URI:
*/


/**
 * Adds a description to a module
 * The array key is the directory name of the module
 */
function uvis_register_layers_module( $modules ) {

  $modules["layers"]["title"] = "Layers";
  $modules["layers"]["description"] = "Present your posts as overlaying layers on a three dimensional timeline or as a horizontal or vertical slideshow.";

  return $modules;

}
add_filter( "uvis_register_module", "uvis_register_layers_module" );



/**
 * Adds the neccessary configuration fields and their default values to the uvis datamodel
 */
function uvis_register_layers_datamodel( $datamodel ) {

  // Either 3D ("z"), horizontal ("x") or vertical ("y")
  $datamodel["uvis_layers_axis"] = "z";

  // Show Layer controls to select the next/previous playlist item.
  $datamodel["uvis_layers_show_layer_controls"] = true;

  // Initial position on the x-axis
  $datamodel["uvis_layers_originX"] = 100;

  // Initial position on the y-axis
  $datamodel["uvis_layers_originY"] = 50;

  // The screen background color
  $datamodel["uvis_layers_default_screen_background_color"] = "#222222";

  // The layer's box background color
  $datamodel["uvis_layers_default_box_background_color"] = "#ffffff";

  // The layer's box border color
  $datamodel["uvis_layers_default_box_border_color"] = "#000000";

  // The layer's box font color
  $datamodel["uvis_layers_default_box_font_color"] = "#000000";

  // The layer's box font face
  $datamodel["uvis_layers_default_box_font"] = "Arial";

  return $datamodel;

}
add_filter( "uvis_datamodel", "uvis_register_layers_datamodel" );


/**
 * Adds styles for the layers in backend and frontend
 */
function uvis_layers_enqueue_styles() {
  wp_register_style( 'uvis-layers', UVIS_MODULES_URL . 'layers/css/layers.css', array(), UVIS_VERSION );
  wp_enqueue_style( 'uvis-layers' );
}
add_action( "uvis_enqueue_styles", "uvis_layers_enqueue_styles" );

?>