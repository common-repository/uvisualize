<?php
/*
Plugin Name: Timeline
Plugin URI: https://cba.fro.at/uvisualize
Description: Visualize posts and attachments on timelines
Author: Ralf Traunsteiner, Ingo Leindecker
Author URI:
*/


/**
 * Adds a description to a module
 * The array key is the directory name of the module
 */
function uvis_register_timeline_module( $modules ) {

  $modules["timeline"]["title"] = "Timeline";
  $modules["timeline"]["description"] = "Present your posts on the popular knightlab timeline.";

  return $modules;

}
add_filter( "uvis_register_module", "uvis_register_timeline_module" );


/**
 * Adds the neccessary configuration fields and their default values to the uvis datamodel
 */
function uvis_register_timeline_datamodel( $datamodel ) {

  // The name of a date field or postmeta to sort the timeline's items
  $datamodel["uvis_timeline_orderby"] = "post_date"; // "post_date" || "post_modified"

  // Start ordering posts by begin or end
  $datamodel["uvis_timeline_direction"] = "begin"; // "begin" || "end"

  // Whether to show the actual timeline slider (otherwise it's more like a slideshow)
  $datamodel["uvis_timeline_display"] = 1; // 1 || 0

  return $datamodel;

}
add_filter( "uvis_datamodel", "uvis_register_timeline_datamodel" );


/**
 * Adds styles for the timeline in backend and frontend
 */
function uvis_timeline_enqueue_styles() {
  wp_register_style( 'uvis-timeline-vendor', UVIS_URL . '/vendor/timeline/css/timeline.css', false, UVIS_VERSION, false );
  wp_enqueue_style( 'uvis-timeline-vendor' );

  wp_register_style( 'uvis-timeline', UVIS_MODULES_URL . 'timeline/css/timeline.css', false, UVIS_VERSION, false );
  wp_enqueue_style( 'uvis-timeline' );
}
add_action( "uvis_enqueue_styles", "uvis_timeline_enqueue_styles" );

?>