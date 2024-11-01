<?php
/*
Plugin Name: UVisualize!
Plugin URI: https://cba.fro.at/uvisualize
Description: A powerful yet easy to use visualization tool for Wordpress. Visualize your content on maps, timelines, in 3D and more.
Version: 1.1
Author: Ralf Traunsteiner, Ingo Leindecker
Author URI:
Text Domain: Uvis

Copyright 2015 - Ralf Traunsteiner, Ingo Leindecker

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

Contributors: Ralf Traunsteiner, Ingo Leindecker

*/


/**
 *
 *** CONTENT ***
 *
 * Defines plugin constants
 * Creates the plugin's admin page and loads the settings
 * Creates modules management page
 * Loads modules
 * Handles (de)activation of modules
 * Loads necessary scripts and styles
 *
 */


// Don't load directly
if ( ! defined('ABSPATH') )
	die('-1');

// Do a PHP version check, require 5.2 or newer - mainly used for widget support
if ( version_compare( PHP_VERSION, '5.2.0', '<' ) ) {
	// Silently deactivate plugin, keeps admin usable
	if( function_exists( 'deactivate_plugins' ) ) {
		deactivate_plugins( plugin_basename(__FILE__), true );
	}

	// Spit out die messages
	wp_die( sprintf( __('Your PHP version is too old, please upgrade to a newer version. Your version is %s, UVisualize! requires %s. Remove the plugin from WordPress plugins directory with FTP client.', 'uvis' ), phpversion(), '5.2.0' ) );
}


define( 'UVIS_VERSION', '1.0' );
define( 'UVIS_NAME', 'UVisualize!' );
define( 'UVIS_URL', plugins_url( '', __FILE__) );
define( 'UVIS_DIR', rtrim( plugin_dir_path( __FILE__ ), '/' ) );
define( 'UVIS_MODULES_DIR', UVIS_DIR . '/modules/' );
define( 'UVIS_MODULES_URL', UVIS_URL . '/modules/' );
define( 'UVIS_INCLUDES_DIR', UVIS_DIR . '/includes/' );
define( 'UVIS_LANG', 'en' );

require( UVIS_INCLUDES_DIR . 'uvis-functions.php' ); // Internal functions


// Activation, deactivation
register_activation_hook( __FILE__, array('UVis_Plugin', 'activation') );
register_deactivation_hook( __FILE__, array('UVis_Plugin', 'deactivation') );


// Sets up the main Visualizer Administration Menu
function uvis_admin_menu () {
  add_menu_page( 'UVisualize', 'UVisualize!', 'manage_options', 'uvis-admin', 'uvis_admin_home', '' );
  add_submenu_page( 'uvis-admin', _('Modules'), _('Modules'), 'manage_options', 'uvis-admin-modules', 'uvis_admin_modules' );
}
add_action( 'admin_menu', 'uvis_admin_menu' );


/**
 * The UVisualize! settings' home page
 */
function uvis_admin_home () {

  global $wpdb;

?>

  <div id="wrap">
    <h1>UVisualize!</h1>

    <h3>First Steps</h3>

    <ol>
      <li>Go to <a href="edit.php">posts</a>. Create playlists with the dropdown and add items to it</li>
      <li>Go to <a href="edit.php?post_type=uvis_playlist">Playlists</a> and click on &quot;Create visualization&quot;</li>
      <li>Feature your visualization in one of your posts by using a shortcode like <code>[uvis id="12345"]</code></li>
    </ol>

    <h3>Theme functions</h3>
    <ol>

      <li>Add <code>&lt;?php uvis_playlist_dropdown(); ?&gt;</code> inside &quot;The Loop&quot; to your theme files to display the playlist-dropdown in order to add posts or attachments to playlists</li>
      <li>Use a widget to dynamically display most recent playlists or visualizations published</li>
      <li>Use the theme function <code>uvis_playlist_items( $playlist_id );</code> to display all items of a playlist</li>
      <li>Use the theme function <code>uvis_the_visualizations( $playlist_id );</code> to list all visualizations of a playlist</li>
      <li>Use the theme function <code>uvis_visualize( $args );</code> to auto-generate visualizations (e.g. for every post_tag or category)</li>
    </ol>

    <h3>You can additionally...</h3>
    <ol>
      <li>Manage available plugin modules (<a href="admin.php?page=uvis-admin-modules" target="_blank">Go to page</a>)</li>
      <li>Check the plugin settings (<a href="admin.php?page=uvis-options" target="_blank">Go to page</a>)</li>
      <li>If you want to display playlists and visualizations in your theme:</li>
         <ol>
            <li>Add the files <code>single-uvis_playlist.php</code>, <code>single-uvis_visualization.php</code> and <code>archive-uvis_playlist.php</code> located in <code>wp-content/plugins/uvis/theme_files</code> to your theme directory in order to display playlists and visualizations</li>
            <li>Update the permalinks (<a href="options-permalink.php" target="_blank">Go to page and press &quot;Save Changes&quot;</a>)</li>
         </ol>
    </ol>


    Get more infos about using the plugin functions in the <a href="https://cba.fro.at/uvisualize-tutorial">tutorials</a>.

	  <h3>Help &amp; documentation</h3>

	    <p>
	    <ul>
	      <li><a href="http://cba.fro.at/uvisualize">UVisualize! Plugin Website</a></li>
	      <li><a href="http://cba.fro.at/uvisualize-tutorial">Tutorials</a></li>
	    </ul>
	    </p>

	    <hr noshade />

	    <div class="uvisLogos clearfix">
	      Created by
	      <a href="http://www.livingarchives.eu" target="_blank"><img src="<?php echo UVIS_URL; ?>/images/logo_livingarchives.gif" title="Creative Approaches to Living Cultural Archives" /></a>
	      <a href="https://cba.fro.at" target="_blank"><img src="<?php echo UVIS_URL; ?>/images/logo_cba.gif" title="CBA - Cultural Broadcasting Archive" /></a>
	      <a href="https://www.fro.at"><img src="<?php echo UVIS_URL; ?>/images/logo_fro.gif" target="_blank" title="Radio FRO 105.0 MHz" /></a>
	      <br />
	      Funded by
	      <img src="<?php echo UVIS_URL; ?>/images/logo_euculture.gif" title="EU Culture" />
	      <img src="<?php echo UVIS_URL; ?>/images/logo_bka.gif" title="Bundeskanzleramt &Ouml;sterreich - Kultur" />
	      <img src="<?php echo UVIS_URL; ?>/images/logo_landooe.gif" title="Federal Government of Upper Austria" />
	      <img src="<?php echo UVIS_URL; ?>/images/logo_linzkultur.gif" title="City of Linz - Culture" />
	    </div>

       <p><?php echo UVIS_VERSION;  ?></p>

  </div>
<?php

}


/**
 * Module Management
 */
function uvis_load_modules () {
  $modules = get_option( 'uvis_active_modules', array() );

  foreach ( $modules as $module ) {
    if ( '.php' == substr( $module, -4 ) // $module must end with '.php'
      && file_exists( UVIS_MODULES_DIR . $module ) ) { // $module must exist
      $module = UVIS_MODULES_DIR . $module;
      include_once( $module );
    } else {
      uvis_deactivate_module( $module );
    }
  }

  if( isset( $_GET["action"] ) && $_GET["action"] == "activate" && isset( $_GET["plugin_status"] ) && $_GET["plugin_status"] == "uvis-modules" ) {
    do_action( 'uvis_activate_module' );
  }

}
add_action( 'plugins_loaded', 'uvis_load_modules' );


/**
 * Generates the modules page
 */
function uvis_admin_modules () {

  if ( ! current_user_can( 'activate_plugins' ) )
    wp_die( __( 'You do not have sufficient permissions to manage plugins for this site.' ) );

  $modules = get_plugins( '/' . basename( plugin_dir_path( __FILE__ ) ) . '/modules' );

  $module = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';

  if ( isset( $_POST['clear-recent-list'] ) )
    $action = 'clear-recent-list';
  elseif ( !empty( $_REQUEST['action'] ) )
    $action = $_REQUEST['action'];
  else
    $action = false;

  if ( ! empty( $action ) ) {
    switch ( $action ) {
      case 'activate':
	      uvis_activate_module( $module );
      break;

      case 'deactivate':
        uvis_deactivate_module( $module );
      break;
    }
  }

  echo '<div class="wrap">';

  screen_icon( 'plugins' );
  echo '<h2>UVisualize! Module Management</h2>';

  uvis_print_modules( $modules, "uvis-modules" );
  echo "</div> <!-- /wrap -->";

}


/**
 * Prints table listing the modules
 */
function uvis_print_modules( $modules, $context = '' ) {

  global $page;
?>
<table class="widefat" cellspacing="0" id="<?php echo $context; ?>-plugins-table">
  <thead>
  <tr>
    <th scope="col" class="manage-column check-column"></th>
    <th scope="col" class="manage-column"><?php _e( 'Module' ); ?></th>
    <th scope="col" class="manage-column"><?php _e( 'Description' ); ?></th>
  </tr>
  </thead>

  <tfoot>
  <tr>
    <th scope="col" class="manage-column check-column"></th>
    <th scope="col" class="manage-column"><?php _e( 'Module' ); ?></th>
    <th scope="col" class="manage-column"><?php _e( 'Description' ); ?></th>
  </tr>
  </tfoot>

  <tbody class="plugins">
<?php

  if ( empty( $modules ) ) {
    echo '<tr>
      <td colspan="3">' . __('No modules to show') . '</td>
    </tr>';
  }
  foreach ( (array) $modules as $module_file => $module_data ) {

    $actions = array();
    $is_active = uvis_is_module_active( $module_file );

    if ( $is_active ) {
      $actions['deactivate'] = '<a href="' . wp_nonce_url( 'admin.php?page=uvis-admin-modules&action=deactivate&amp;plugin=' . $module_file . '&amp;plugin_status=' . $context . '&amp;paged=' . $page, 'deactivate-plugin_' . $module_file ) . '" title="' . __('Deactivate this plugin') . '">' . __('Deactivate') . '</a>';
    } else {
      $actions['activate'] = '<a href="' . wp_nonce_url( 'admin.php?page=uvis-admin-modules&action=activate&amp;plugin=' . $module_file . '&amp;plugin_status=' . $context . '&amp;paged=' . $page, 'activate-plugin_' . $module_file ) . '" title="' . __('Activate this plugin') . '" class="edit">' . __('Activate') . '</a>';
    }

    $class = $is_active ? 'active' : 'inactive';
    $description = '<p>' . $module_data['Description'] . '</p>';
    $module_name = $module_data['Name'];

  echo '
  <tr class="' . $class . '">
    <th scope="row" class="check-column"></th>
    <td class="plugin-title"><strong>' . $module_name . '</strong></td>
    <td class="desc">' . $description . '</td>
  </tr>
  <tr class="' . $class . ' second">
    <td></td>
    <td class="plugin-title">
      <div class="row-actions-visible">';

    foreach ( $actions as $action => $link ) {
      $sep = end($actions) == $link ? '' : ' | ';
      echo '<span class="' . $action . '">' . $link . $sep. '</span>';
    }

    echo '</div></td>
    <td class="desc">';

    $module_meta = array();
    if ( !empty($module_data['Author']) ) {
      $author = $module_data['Author'];
      if ( !empty($module_data['AuthorURI']) )
        $author = '<a href="' . $module_data['AuthorURI'] . '" title="' . __( 'Visit author homepage' ) . '">' . $module_data['Author'] . '</a>';
      $module_meta[] = sprintf( __('By %s'), $author );
    }
    if ( ! empty($module_data['PluginURI']) )
      $module_meta[] = '<a href="' . $module_data['PluginURI'] . '" title="' . __( 'Visit plugin site' ) . '">' . __('Visit plugin site') . '</a>';

    echo implode(' | ', $module_meta);
    echo '</td>
  </tr>';
  }
?>
  </tbody>
</table>
<?php
}


/**
 * Checks if a module is active at the moment
 */
function uvis_is_module_active( $module ) {
  return in_array( $module, (array) get_option( 'uvis_active_modules', array() ) );
}


/**
 * Activates a module
 */
function uvis_activate_module( $module ) {
  $module  = plugin_basename( trim( $module ) );
  $current = get_option( 'uvis_active_modules', array() );
  if ( ! in_array( $module, $current ) ) {
    $current[] = $module;
    sort( $current );

    update_option( 'uvis_active_modules', $current );

    add_option( 'uvis_visualization_post_type', 'uvis_visualization' );
    add_option( 'uvis_visualization_post_type_name_singular', 'Visualization' );
    add_option( 'uvis_visualization_post_type_name_plural', 'Visualizations' );

  }
  return null;
}


/**
 * Deactivates a module
 */
function uvis_deactivate_module( $modules ) {
  $current = get_option( 'uvis_active_modules', array() );
  foreach ( (array) $modules as $module ) {
    $module = plugin_basename( $module );

    if ( ! uvis_is_module_active( $module ) )
      continue;

    $key = array_search( $module, (array) $current );
    if ( false !== $key ) {
      array_splice( $current, $key, 1 );
    }
  }
  update_option( 'uvis_active_modules', $current );

}


/**
 * Loads the language file in addition to the normal translation, so the WPLANG Constant can be de_DE and themes / plugins work accordingly
 */
function uvis_load_textdomain_mofile( $mofile, $domain ) {

  if ( $domain != 'uvis' ) {
    return $mofile;
  }

  $uvis_mofile = UVIS_DIR . '/i18n/po/' . UVIS_LANG . '.mo';
  $mofile = ( file_exists( $uvis_mofile ) ) ? $uvis_mofile : $mofile;

  return $mofile;

}
add_filter( 'load_textdomain_mofile', 'uvis_load_textdomain_mofile', 2, 10 );


/*
 * Initializes the UVisualize! plugin
 */
function init_uvis() {

  load_textdomain( 'uvis', UVIS_DIR . '/i18n/po/' . UVIS_LANG . '.mo' );

  require( UVIS_INCLUDES_DIR . 'uvis-options.php' );

  wp_enqueue_script( 'jquery' );
  wp_enqueue_script( 'jquery-ui-core' );
  wp_enqueue_script( 'jquery-ui-widget' );
  wp_enqueue_script( 'jquery-ui-mouse' );
  wp_enqueue_script( 'jquery-ui-position' );
  wp_enqueue_script( 'jquery-ui-sortable' );
  wp_enqueue_script( 'jquery-ui-draggable' );
  wp_enqueue_script( 'jquery-ui-tooltip' );
  wp_enqueue_script( 'jquery-ui-dialog' );

  function uvis_add_ajaxurl() {
	  ?>
  	<script>
	  var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
  	</script>
	  <?php
  }
  add_action( 'wp_head', 'uvis_add_ajaxurl' );

  new UVis_Options();

}
add_action( 'init', 'init_uvis' );

?>