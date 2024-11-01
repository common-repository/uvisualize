<?php
/*
Plugin Name: Maps
Plugin URI: https://cba.fro.at/uvisualize
Description: Present your posts on maps.
Author: Ralf Traunsteiner, Ingo Leindecker
Author URI:
*/

/**
 * Adds a description to a module
 * The array key is the directory name of the module
 */
function uvis_register_map_module( $modules ) {

  $modules["map"]["title"] = "Map";
  $modules["map"]["description"] = 'Present your posts on a map. Add geo data to posts using a geo tagging plugin in order to display them on a map.<br />The <span class="dashicons dashicons-location"></span>-symbol indicates that a post has geo data.';

  return $modules;

}
add_filter( "uvis_register_module", "uvis_register_map_module" );


/**
 * Adds the neccessary configuration fields and their default values to the uvis datamodel
 */
function uvis_register_map_datamodel( $datamodel ) {

  // The basemap's handle
  $datamodel["uvis_map_basemap"] = "osm-basic";

  // Unique name of a svg marker icon created by a php file located in modules/map/images/
  $datamodel["uvis_map_default_marker_icon"] = "default"; // Base filename of the php file without path and extension. Must correspond to an iconID in $globals["map_marker_icons"] below

  // Hex code for the marker's color (the fill color if marker icon is transparent)
  $datamodel["uvis_map_default_marker_color"] = "#0070c0";

  // The marker's popup box' default background color in hex code
  $datamodel["uvis_map_default_box_background_color"] = "#ffffff";

  // The marker's popup box' default border color in hex code
  $datamodel["uvis_map_default_box_border_color"] = "#7f7f7f";

  // The marker's popup box' default font color in hex code
  $datamodel["uvis_map_default_font_color"] = "#262626";

  // The marker's popup box' default font face
  $datamodel["uvis_map_default_font"] = "Arial";

  // Whether to cluster markers automatically (1) or not (0)
  $datamodel["uvis_map_cluster_markers"] = 0;

  return $datamodel;

}
add_filter( "uvis_datamodel", "uvis_register_map_datamodel" );


/**
 * Registers the global vars neccessary for the map
 *
 * - Adds available basemaps
 * - Adds available marker icons
 *
 */
function uvis_register_map_global_vars( $globals ) {

  // Add basemaps to the globals

  // Basemap title
  $globals["basemaps"][0]["title"] = "OSM Basic Map";

  // A unique identifier which never changes and can be linked to a visualization -> see "uvis_map_basemap" in the datamodel above
  $globals["basemaps"][0]["handle"] = "osm-basic";

  // The basemap's description
  $globals["basemaps"][0]["description"] = "The Open Street Map Mapnik basic map. Usage policy: http://wiki.openstreetmap.org/wiki/Tile_usage_policy";

  // URL to the basemap's tiles
  $globals["basemaps"][0]["url"] = "http://a.tile.openstreetmap.org/{z}/{x}/{y}.png";

  $globals["basemaps"][3]["title"] = "OSM Grayscale";
  $globals["basemaps"][3]["handle"] = "osm-grayscale";
  $globals["basemaps"][3]["description"] = "Open Street Map grayscale map.";
  $globals["basemaps"][3]["url"] = "http://a.www.toolserver.org/tiles/bw-mapnik/{z}/{x}/{y}.png";

  $globals["basemaps"][4]["title"] = "OSM Toner (by Stamen Design)";
  $globals["basemaps"][4]["handle"] = "osm-toner";
  $globals["basemaps"][4]["description"] = 'Open Street Map in painted black and white. Map tiles by Stamen Design (http://stamen.com), licensed under CC BY 3.0 (http://creativecommons.org/licenses/by/3.0). Data by OpenStreetMap (http://openstreetmap.org), licensed under CC BY SA (http://creativecommons.org/licenses/by-sa/3.0).';
  $globals["basemaps"][4]["url"] = "http://a.tile.stamen.com/toner/{z}/{x}/{y}.png";

  $globals["basemaps"][5]["title"] = "basemap.at (Austria only)";
  $globals["basemaps"][5]["handle"] = "basemap.at";
  $globals["basemaps"][5]["description"] = 'basemap.at, licensed under CC-BY 3.0 (http://creativecommons.org/licenses/by/3.0/at/deed.de)';
  $globals["basemaps"][5]["subdomains"] = array("maps", "maps1", "maps2", "maps3");
  $globals["basemaps"][5]["url"] = "http://{s}.wien.gv.at/basemap/geolandbasemap/normal/google3857/{z}/{y}/{x}.png";
  $globals["basemaps"][5]["maxZoom"] = 18;

  $globals["basemaps"][6]["title"] = "basemap.at Ortho (Austria only)";
  $globals["basemaps"][6]["handle"] = "basemap.ortho.at";
  $globals["basemaps"][6]["description"] = 'basemap.at, licensed under CC-BY 3.0 (http://creativecommons.org/licenses/by/3.0/at/deed.de)';
  $globals["basemaps"][6]["subdomains"] = array("maps", "maps1", "maps2", "maps3");
  $globals["basemaps"][6]["url"] = "http://{s}.wien.gv.at/basemap/bmaporthofoto30cm/normal/google3857/{z}/{y}/{x}.jpeg";
  $globals["basemaps"][6]["maxZoom"] = 19;

  $globals["basemaps"][7]["title"] = "Watercolor (by Stamen Design)";
  $globals["basemaps"][7]["handle"] = "osm-watercolor";
  $globals["basemaps"][7]["description"] = 'Map tiles by Stamen Design (http://stamen.com), licensed under CC BY 3.0 (http://creativecommons.org/licenses/by/3.0). Data by OpenStreetMap (http://openstreetmap.org), licensed under CC BY SA (http://creativecommons.org/licenses/by-sa/3.0).';
  $globals["basemaps"][7]["url"] = "http://a.tile.stamen.com/watercolor/{z}/{x}/{y}.png";

  // Use default maps if custom option "uvis_basemaps" doesn't exist
  $globals["basemaps"] = ( is_array( get_option( "uvis_basemaps" ) ) ) ? get_option( "uvis_basemaps") : $globals["basemaps"];
  //$globals["basemaps"] = $globals["basemaps"];

  // Add available marker icons located in modules/map/images/. SVG Icons are made by php scripts which accept a $_GET["col"] variable to change the fill color
  $globals["map_marker_icons"] = array( array( "iconID" => "default", // Base filename of the php script without path and extension.
                                               "iconSize" => '[25, 41]', // Size of the icon
                                               "iconAnchor" => '[12, 41]', // Point of the icon which will correspond to marker's location
                                               "popupAnchor" => '[0, -41]' // Point from which the popup should open relative to the iconAnchor
                                             ),
                                        array( "iconID" => "microphone",
                                               "iconSize" => '[41, 41]',
                                               "iconAnchor" => '[20, 20]',
                                               "popupAnchor" => '[0, -25]'
                                             ),
                                        array( "iconID" => "play",
                                               "iconSize" => '[41, 41]',
                                               "iconAnchor" => '[20, 20]',
                                               "popupAnchor" => '[0, -18]'
                                             ),
                                        array( "iconID" => "skull",
                                               "iconSize" => '[41, 41]',
                                               "iconAnchor" => '[20, 20]',
                                               "popupAnchor" => '[0, -27]'
                                             ),
                                        array( "iconID" => "speaker",
                                               "iconSize" => '[41, 41]',
                                               "iconAnchor" => '[20, 20]',
                                               "popupAnchor" => '[0, -25]'
                                             ),
                                        array( "iconID" => "tent",
                                               "iconSize" => '[41, 41]',
                                               "iconAnchor" => '[20, 20]',
                                               "popupAnchor" => '[0, -22]'
                                             ),
                                        array( "iconID" => "tick",
                                               "iconSize" => '[41, 33]',
                                               "iconAnchor" => '[15, 33]',
                                               "popupAnchor" => '[0, -25]'
                                             ),
                                        array( "iconID" => "tower",
                                               "iconSize" => '[41, 41]',
                                               "iconAnchor" => '[20, 41]',
                                               "popupAnchor" => '[0, -42]'
                                             ),
                                        array( "iconID" => "wifi",
                                               "iconSize" => '[41, 29]',
                                               "iconAnchor" => '[20, 29]',
                                               "popupAnchor" => '[0, -31]'
                                             )
                                      );

  return $globals;

}
add_filter( "uvis_global_vars", "uvis_register_map_global_vars" );


/**
 * Makes geodata of every playlist item available to Javascript (using the post metas according to the Wordpress standard)
 * See http://codex.wordpress.org/Geodata
 */
function uvis_add_map_postmeta( $postmeta ) {

  // Make a post's geodata available to JSON
  $postmeta[] = "geo_address";
  $postmeta[] = "geo_latitude";
  $postmeta[] = "geo_longitude";
  $postmeta[] = "geo_public";

  // Include plugins like WP Geo which aren't compatible with the WP standard
  // Element names are corrected in js/render.js
  $postmeta[] = "_wp_geo_latitude";
  $postmeta[] = "_wp_geo_longitude";

	return $postmeta;

}
add_filter( "uvis_add_postmeta_to_playlist_item", "uvis_add_map_postmeta" );


/**
 * Adds styles for the map in backend and frontend
 */
function uvis_map_enqueue_styles() {

  wp_register_style( 'uvis-leaflet', UVIS_URL . '/vendor/leaflet-dev/leaflet.css', false, UVIS_VERSION, false );
  wp_enqueue_style( 'uvis-leaflet' );

  wp_register_style( 'uvis-map', UVIS_MODULES_URL . 'map/css/map.css', array(), UVIS_VERSION );
  wp_enqueue_style( 'uvis-map' );

}
add_action( "uvis_enqueue_styles", "uvis_map_enqueue_styles" );


/**
 * Adds default options when activating the plugin module for the first time
 */
function uvis_map_activate_module() {
  $globals = uvis_register_map_global_vars( array() );

  // Adds basemaps to options for restoring defaults
  add_option( "uvis_default_basemaps", $globals["basemaps"] );
}
add_action( "uvis_activate_module", "uvis_map_activate_module" );

?>