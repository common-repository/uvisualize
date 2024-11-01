<?php
/*
Plugin Name: UVisualize! Visualizer Core Framework
Plugin URI: https://cba.fro.at/uvisualize
Description: Visualize your playlists and add visualization modules
Author: Ralf Traunsteiner, Ingo Leindecker
Author URI:
*/

class UVis_Visualizer {

    // Names of Custom Post Type
    public $postType = 'uvis_visualization';
    public $postTypeNameSingular = 'Visualization';
    public $postTypeNamePlural = 'Visualizations';

    // Meta Box Stuff
    public $metaBoxID = 'uvis_visualizer_metabox';
    public $metaBoxTempl = 'templates/metabox.tpl.php';

    public $taxonomies = array();

    public function __construct() {

        $this->postType = get_option( "uvis_visualization_post_type", $this->postType );
        $this->postTypeNameSingular = get_option( "uvis_visualization_post_type_name_singular", $this->postTypeNameSingular );
        $this->postTypeNamePlural = get_option( "uvis_visualization_post_type_name_plural", $this->postTypeNamePlural );
        $this->uvis_playlist_post_meta = get_option( "uvis_playlist_post_meta" );
        $this->uvis_convert_shortcodes = get_option( "uvis_convert_shortcodes", "true");

        // Retrieve all existing taxonomy slugs and get rid of some we don't need
        $this->taxonomies = get_taxonomies();
        unset( $this->taxonomies["nav_menu"] );
        unset( $this->taxonomies["link_category"] );
        unset( $this->taxonomies["post_format"] );

        // Register the post type for the visualization
        $this->registerPostType( $this->postTypeNameSingular, $this->postTypeNamePlural );

        // Load the Javascript needed on this admin page
        add_action( 'admin_enqueue_scripts', array( $this, 'uvis_enqueue_admin_scripts' ) );

        // Add the meta Box
        add_action( 'add_meta_boxes', array( $this, 'addVisualizationMetaBox' ) );

        /* AJAX REQUESTS */

        // Save a visualization
        add_action( 'wp_ajax_uvis_save_visualization', array( $this, 'saveVisualization' ) );

        // Get a visualization's configuration, its playlist and all of its items (+ allow access for frontend)
        add_action( 'wp_ajax_uvis_get_visualization', array( $this, 'getVisualization' ) );
        add_action( 'wp_ajax_nopriv_uvis_get_visualization', array( $this, 'getVisualization' ) );
        add_action( 'wp_ajax_uvis_get_visualization_on_the_fly', array( $this, 'getVisualizationOnTheFly' ) );
        add_action( 'wp_ajax_nopriv_uvis_get_visualization_on_the_fly', array( $this, 'getVisualizationOnTheFly' ) );

        // Get all visualizations of a playlist
        add_action( 'wp_ajax_uvis_get_visualizations', array( $this, 'getVisualizations' ) );

        // Add a new visualization
        add_action( 'wp_ajax_uvis_add_visualization', array( $this, 'addVisualization' ) );

        // Delete a visualization
        add_action( 'wp_ajax_uvis_delete_visualization', array( $this, 'deleteVisualization' ) );

        // Get a playlist item
        add_action( 'wp_ajax_uvis_get_playlist_taxonomies', array( $this, 'getPlaylistTaxonomies' ) );
        add_action( 'wp_ajax_nopriv_uvis_get_playlist_taxonomies', array( $this, 'getPlaylistTaxonomies' ) );

        // Add/update a postmeta
        add_action( 'wp_ajax_uvis_update_post_meta', array( $this, 'updatePostMeta' ) );

        /* AJAX REQUESTS END */

    }


    /**
     * Returns an array of all registered visualization modules
     * Core modules like visualizer, playlist and debugger are excluded
     *
     * Use the filter hook "uvis_register_module" to add a title and a description (in this example a module named "test" in your own ..../plugins/uvis/modules/test/test.php file)
     *
     * The $modules array index named "test" in the example below (= the id of your module) is determined by the directory name of your module (located in e.g.: /wp-content/plugin/uvis/modules/test/
     *
     * Usage:
     *
     *   function uvis_register_test_module( $modules ) {
     *     $modules["test"]["title"] = 'Title of my module';
     *     $modules["test"]["description"] = 'What my module does.';
     *
     *     return $modules;
     *   }
     *   add_filter( "uvis_register_module", "uvis_register_test_module" );
     *
     *
     * @return array
     */
    public function getRegisteredModules() {
      $active_modules = get_option( "uvis_active_modules", array() );
      $registered_modules = array();

      foreach( $active_modules as $key => $module ) {
        $mod = explode( '/', $module ); // Use directory name only

        if( $mod[0] !== "visualizer" && $mod[0] !== "playlist" && $mod[0] !== "debug" ) {
          $registered_modules[$mod[0]]["id"] = $mod[0];
          $registered_modules[$mod[0]]["title"] = "Title missing. Use the filter &quot;uvis_register_module&quot; to add one.";
          $registered_modules[$mod[0]]["description"] = "Description missing. Use the filter &quot;uvis_register_module&quot; to add one.";
        }

      }
      return apply_filters( "uvis_register_module", $registered_modules );
    }


    /**
     * Enqueues scripts for the backend
     */
    public function uvis_enqueue_admin_scripts() {

      $screen = get_current_screen();

      // Restrict to visualizer, playlist and plugin start screen
      if( $screen->post_type != $this->postType && $screen->post_type != get_option( 'uvis_playlist_post_type' ) && $screen->base != "toplevel_page_uvis-admin" )
        return;

      do_action( "uvis_enqueue_styles" );

      wp_register_style( 'uvis-ngDialog', UVIS_URL . '/vendor/ngDialog/css/ngDialog.css', false, UVIS_VERSION );
      wp_enqueue_style( 'uvis-ngDialog' );

      wp_register_style( 'uvis-colorpicker', UVIS_URL . '/vendor/colorpicker/css/evol.colorpicker.css', false, UVIS_VERSION );
      wp_enqueue_style( 'uvis-colorpicker' );

      wp_register_style( 'uvis-visualizer', UVIS_MODULES_URL . 'visualizer/css/visualizer.css', false, UVIS_VERSION );
      wp_enqueue_style( 'uvis-visualizer' );

      wp_register_style( 'uvis-daterange', UVIS_URL . '/css/rangeslider.css', false, UVIS_VERSION, false );
      wp_enqueue_style( 'uvis-daterange' );


      // JS

      wp_enqueue_script( 'jquery-ui-tooltip' );

      wp_register_script( 'uvis-colorpicker', UVIS_URL . '/vendor/colorpicker/js/evol.colorpicker.min.js', false, UVIS_VERSION, true );
      wp_enqueue_script( 'uvis-colorpicker' );

      wp_register_script( 'uvis-require', UVIS_URL . '/vendor/require.js', false, UVIS_VERSION, true );
      wp_enqueue_script( 'uvis-require' );

      wp_register_script( 'uvis-visualizer-init', UVIS_URL . '/js/require.js', false, UVIS_VERSION, true );
      wp_enqueue_script( 'uvis-visualizer-init' );

    }


    /**
     * Defines the data model for the visualization's configuration array
     *
     * The data model can be manipulated by the filter "uvis_datamodel" in the following way:
     *
     * function uvis_add_to_model( $arr ) {
     *   $arr["uvis_my_variable"] = "myvalue"; // Adds an array element to the model
     *   return $arr;
     * }
     * add_filter("uvis_datamodel", "uvis_add_to_model" );
     *
     *
     * @param array : a post's array retrieved from get_post( $id, ARRAY_A )
     * @return array
     */
    public function getVisualizationDataModel( $visualization ) {

      /* Post fields are overwritten since they can change */
      $uvis["uvis_ID"] = $visualization["ID"];
      $uvis["uvis_post_title"] = $visualization["post_title"];
      $uvis["uvis_post_content"] = $visualization["post_content"];
      $uvis["uvis_post_author"] = $visualization["post_author"];
      $uvis["uvis_post_date"] = $visualization["post_date"];
      $uvis["uvis_post_modified"] = $visualization["post_modified"];
      $uvis["uvis_post_status"] = $visualization["post_status"];
      $uvis["uvis_post_permalink"] = get_permalink( $visualization["ID"] );
      $uvis["uvis_post_parent"] = $visualization["post_parent"];
      $uvis["uvis_playlist"] = $visualization["post_parent"];
      $uvis["uvis_post_type"] = $visualization["post_type"];

      /* End post fields */

      // Retrieves the module handler for this visualization
      $uvis["uvis_module"] = get_post_meta( $visualization["ID"], "uvis_module", true ) ;

      // Whether to display the playlist?
      $uvis["uvis_playlist_display"] = 1;

      // Where to display the playlist on screen?
      $uvis["uvis_playlist_position"] = "overlay";

      // Default playlist background color
      $uvis["uvis_playlist_background_color"] = "#ffffff";

      // Default playlist text color
      $uvis["uvis_playlist_text_color"] = "#000000";

      // Whether to display attached media files to a post at all
      $uvis["uvis_attachment_display"] = 1;

      // Default mediatypes which can be toggled in display
      $uvis["uvis_attachment_show_mediatypes"] = array( "audio", "video", "image", "document" );

      // This is currently unused and not considered nowhere. It is meant for having different layouts/templates in which the media files can be displayed
      $uvis["uvis_attachment_display_mode"] = "default";

      // Whether to autostart playing a video or audio file after selecting the playlist item without having to click the play button
      $uvis["uvis_attachment_autoplay"] = 0;

      // Database fields which can be toggled in display
      $uvis["uvis_filters_show_fields"] = array( "post_title", "post_date", "post_content", "post_permalink" );

      // Whether to display the taxonomy filter planel
      $uvis["uvis_filters_enable"] = 0;

      // Build the array structure for the filters
      $uvis["uvis_filters"] = array( 0 => array( "uvis_filter_by_taxonomy" => "",
                                                 "uvis_filter_by_taxonomy_term_ids" => array(),
                                               )
      );

      // Whether to display the timerange filter
      $uvis["uvis_filters_timerange_enable"] = 0;

      // Which date field the timerange uses to filter by default (database fields wp_posts.post_date or wp_posts.post_modified)
      $uvis["uvis_filter_by_timerange"] = "post_date";

      // Whether to auto animate the playlist and switch to one item to the next
      $uvis["uvis_animation_autoplay"] = 0;

      // Number of milliseconds to switch between playlist items
      $uvis["uvis_animation_delay"] = 1500;

      // Whether to show playback control buttons (forward, backward and play) (0 = disabled, 1 = enabled)
      $uvis["uvis_animation_playbuttons"] = 0;

      // Each playlist item may store additional metadata in its post meta config
      $uvis["uvis_item_config"] = array();

      return apply_filters( "uvis_datamodel", $uvis );

    }



    /**
     * @param array
     * @return array
     */
    public function fetchPlaylistItems( $playlist_items ) {

      global $wpdb;

      $playlist_items = ( is_array( $playlist_items ) && ! empty( $playlist_items ) ) ? $playlist_items : array();
      $postsarray = array();

      // Get all parent posts of the items - We assume that we only get parent posts, not attachments
      $results = $wpdb->get_results( "SELECT ID, post_author, post_date, post_content, post_title, post_status, post_modified, post_parent, guid, post_type, post_mime_type FROM " . $wpdb->posts . " WHERE post_type='post' AND ID IN( " . implode( ",", $playlist_items ) . ") ORDER BY FIND_IN_SET(ID, '" . implode( ",", $playlist_items ) . "')", ARRAY_A );

      $i = 0;

      // Get all the attachments and add them to the parent's array key "attachments"
      foreach( $results as $post ) {

        $postsarray[$i] = $post;

        $post["post_content"] = strip_tags( trim( $post["post_content"] ), '<a><br><br /><p><i><u><b><strong><sub><sup><strike><s><hr><ul><ol><li>'); // Get rid of most HTML tags
        $post["post_content"] = str_replace( array( "\n", "\r\n" ), array( "<br />", "<br />" ), $post["post_content"] ); // Remove double breaks

        // If uvis_convert_shortcodes is true remove shortcodes from content, execute them otherwise
        remove_shortcode( "uvis" ); // Prevent parsing and replacing our own shortcode
        $postsarray[$i]["post_content"] = ( $this->uvis_convert_shortcodes === "true" ) ? strip_shortcodes( $post["post_content"] ) : do_shortcode( $post["post_content"] );
        $postsarray = $this->processPlaylistItems( $postsarray );

        $children = $wpdb->get_results( "SELECT ID, post_date, post_content, post_title, post_modified, guid, post_type, post_mime_type FROM " . $wpdb->posts . " WHERE post_parent=" . esc_sql( $post["ID"] ) . " AND post_type='attachment' AND post_status='inherit' ", ARRAY_A );
        $children = $this->processPlaylistItems( $children );

        // Get additional attachments if they were included by a shortcode
        // An attachment will be added only if it has a representation in the posts table! (external content won't be considered)
        if( $this->uvis_convert_shortcodes === "true" ) {
          $additional_attachments = $this->getPostIDsFromShortcodes( $post["post_content"] );
          $additional_children = $wpdb->get_results( "SELECT ID, post_date, post_content, post_title, post_status, guid, post_mime_type FROM " . $wpdb->posts . " WHERE ( post_status='inherit' OR post_status='publish' ) AND ID IN('" . implode("','", $additional_attachments ) . "')", ARRAY_A );
          $additional_children = $this->processPlaylistItems( $additional_children );
          $children = array_merge( $children, $additional_children );
        }

        $postsarray[$i]["attachments"] = ! empty( $children ) ? $children : array();

        $i++;

      }

      return $postsarray;

    }


    /**
     * Adds the meta box in the backend
     */
    public function addVisualizationMetaBox() {

        // Add the meta box
        add_meta_box(
            $this->metaBoxID,
            UVIS_NAME,
            array( $this, 'getVisualizationMetaBox' ), // Get the markup needed
            get_option( "uvis_playlist_post_type" ), // Add meta box to the edit-playlist screen
            'side',
            'high'
        );

    }


    /**
     * Creates a new visualization (AJAX call)
     *
     * Expects
     * $GLOBALS["HTTP_RAW_DATA"]["playlist_id"]
     * $GLOBALS["HTTP_RAW_DATA"]["title"]
     *
     * Inserts a post for the visualization
     * Saves the default config in postmeta "uvis_config"
     *
     * Passes the the uvisData JSON object on success
     * Passes the same object containing error messages on failure
     */
    public function addVisualization() {

      $uvis_data = json_decode( file_get_contents("php://input"), true );

      if( ! isset( $uvis_data["playlist_id"] ) || ! is_numeric( $uvis_data["playlist_id"] ) )
        uvis_answer_ajax_request( array(), 400, __("No playlist ID given.", "uvis") );

      if( ! isset( $uvis_data["title"] ) || trim( $uvis_data["title"] ) == "" )
        uvis_answer_ajax_request( array(), 400, __("Please give a title!", "uvis") );

      $post = array();
      $post["post_title"] = $uvis_data["title"];
      $post["post_type"] = $this->postType;
      $post["post_parent"] = $uvis_data["playlist_id"];
      $post["post_status"] = "draft";
      $post["post_name"] = sanitize_title_with_dashes( $post->post_title, '', 'save' );

      // Insert the visualization's post
      if( $post_id = wp_insert_post( $post ) ) {
          // Save the configuration data model to postmeta "uvis_config"
          $post = get_post( $post_id, ARRAY_A );
          add_post_meta( $post_id, "uvis_config", $this->getVisualizationDataModel( $post ), true );

          // Get and print the uvis object
          $this->getVisualization( $post_id ); // Will die on success
      }

      uvis_answer_ajax_request( array(), 400, __($this->postTypeNameSingular . " could not be created.", "uvis") );

    }


    /**
     * Prints the main JSON object necessary for loading the visualizer and a certain visualization
     *
     * This is the main function which collects and returns the necessary data for the whole visualizer js application
     *
     * Expects EITHER
     *   $GLOBALS["HTTP_RAW_DATA"]["visualization_id"] alias php://input
     * OR
     *   $this->getVisualization( $visualization_id )
     *
     * The uvis_data object will contain:
     * - Object 'playlist': The playlist's post, mainly used for displaying its title and description
     * - Array 'items': All playlist items in a manually ordered array (attachments are stored in items[x].attachments)
     *   - May contain additional postmetas when the filter 'uvis_add_postmeta_to_playlist_item' is called
     * - Object 'config': The datamodel for describing and configuring the visualization
     *   - Stored in wp_posts (post_type 'uvis_visualization'): Title, description and relation to playlist via post_parent
     *   - Stored in wp_postmeta: All visualization configuration variables registered via getVisualizationDataModel();
     *     - Can be extended through a module by calling the filter 'uvis_datamodel'
     * - Array 'active_modules': An array containing the directory names of all active visualization modules (e.g. 'map', 'timeline', 'layers', etc..)
     *
     * It may contain other array keys storing variables which must be available to JS all the time
     * It can be extended by the filter 'uvis_global_vars'
     *
     */
    public function getVisualization( $visualization_id ) {

      global $wpdb;

      $post_data = json_decode( file_get_contents("php://input"), true );

      $visualization_id = ( isset( $post_data["visualization_id"] ) && ! empty( $post_data["visualization_id"] ) ) ? $post_data["visualization_id"] : $visualization_id;

      if( ! $visualization_id )
        uvis_answer_ajax_request( array(), 400, __("Not a valid ID.", "uvis") );

      // Retrieve the visualization
      if( ! $visualization = get_post( $visualization_id, ARRAY_A ) )
        uvis_answer_ajax_request( array(), 404, __("Visualization doesn't exist.", "uvis") );

      // Get the whole data model for this visualization
      $visualization = $this->getVisualizationDataModel( $visualization );

      // Is there a saved visualization config?
      $saved_config = get_post_meta( $visualization_id, "uvis_config", true );
      $saved_config = ( $saved_config != "" && is_array( $saved_config ) && ! empty( $saved_config ) ) ? $saved_config : array();

      // Add standard values to the saved config if elements don't exist yet (e.g. when a module was actived later, pass its default values anyway)
      foreach( $visualization as $key => $vis ) {
        if( ! array_key_exists( $key, $saved_config ) || ( $key === "uvis_post_modified" || $key === "uvis_post_modified_gmt" || $key === "uvis_post_status" || $key === "uvis_post_permalink" ) ) {
          $saved_config[$key] = $vis;
        }
      }

      $visualization = $saved_config;

      // The visualization's parent post is the playlist ID
      $playlist_id = $visualization["uvis_post_parent"];
      $playlist = get_post( $playlist_id, ARRAY_A );

      // Retrieve the playlist's featured image
      $playlist["featured_image"] = get_the_post_thumbnail( $playlist_id, "large" );

      // Retrieve the playlist's items from postmeta (= an array of post_ids in the right order)
      if( ! $playlist_items = get_post_meta( $playlist_id, $this->uvis_playlist_post_meta, true ) )
        uvis_answer_ajax_request( array(), 404, __("This playlist is empty.", "uvis" ) );

      $uvis = array();

      // Hook into this filter to add variables to the uvisData JS object
      $uvis = apply_filters( "uvis_global_vars", $uvis );

      $uvis["playlist"] = $playlist;
      $uvis["items"] = $this->fetchPlaylistItems( $playlist_items );
      $uvis["items_order"] = $playlist_items;
      $uvis["config"] = $visualization;
      $uvis["active_modules"] = $this->getRegisteredModules();

      uvis_answer_ajax_request( $uvis );

    }


    /**
     * Generates a playlist out of taxonomies and terms
     * Used by the theme function uvis_visualize()
     * See more there
     */
    public function getVisualizationOnTheFly() {

      $post_data = json_decode( file_get_contents("php://input"), true );
      $settings = $post_data["settings"];

      $taxonomy = $settings["taxonomy"];
      $term = $settings["term"];
      $module = $settings["module"];
      $limit = ( ! empty( $settings["limit"] ) && $settings["limit"] > -1 ) ? $settings["limit"] : -1;
      $posts_per_page = ( $settings["post_per_page"] > -1 ) ? $settings["posts_per_page"] : $limit;
      $offset = ( ! empty( $settings["offset"] ) ) ? $settings["offset"] : 0;

      if( ! in_array( $taxonomy, $this->taxonomies ) )
        uvis_answer_ajax_request( array(), 400, __( $taxonomy . " is not a valid taxonomy.", "uvis") );

      $args = array(
        'posts_per_page'   => $posts_per_page,
        'offset'           => $offset,
        'orderby'          => 'post_date',
        'order'            => 'ASC',
        'post_type'        => 'post',
        'post_status'      => 'publish',
        'tax_query' => array(
                     array(
                       'taxonomy' => $taxonomy,
                       'field' => 'slug',
                       'terms' => $term
                     )
                   )

      );
      $posts = get_posts( $args );
      $playlist_items = array();

      //$playlist_items = array_column( $posts, "ID" ); // Requires PHP 5.5.0+
      foreach( $posts as $post ) {
        $playlist_items[] = $post->ID;
      }

      // Default settings for Visualization
      $visualization = $this->getVisualizationDataModel( array() );
      $visualization["uvis_ID"] = (int) 0;
      $visualization["uvis_module"] = $module;
      $visualization["uvis_post_title"] = $settings["caption"];

      $visualization = array_merge( $visualization, $settings );

      $playlist["ID"] = 0;
      $playlist["post_title"] = ( isset( $args["caption"] ) && ! empty( $args["caption"] ) ) ? $args["caption"] : "All posts by " . $taxonomy . " '" . $term . "'";

      $uvis = array();

      // Hook into this filter to add variables to the uvisData JS object
      $uvis = apply_filters( "uvis_global_vars", $uvis );

      $uvis["playlist"] = $playlist;
      $uvis["items"] = $this->fetchPlaylistItems( $playlist_items );
      $uvis["items_order"] = $playlist_items;
      $uvis["config"] = $visualization;
      $uvis["active_modules"] = $this->getRegisteredModules();

      uvis_answer_ajax_request( $uvis );

    }


    /**
     * Get all visualizations of a playlist by AJAX
     *
     * Prints a JSON object
     *
     * Expects EITHER
     *   $GLOBALS["HTTP_RAW_POST_DATA"]["playlist_id"]
     * OR
     *   function call getVisualizations( $playlist_id )
     */
    public function getVisualizations( $playlist_id ) {
      global $wpdb;

      $uvis_data = json_decode( file_get_contents("php://input"), true );

      $playlist_id = ( ! isset( $uvis_data["playlist_id"] ) || ! is_numeric( $uvis_data["playlist_id"] ) ) ? $playlist_id : $uvis_data["playlist_id"];

      if( ! isset ( $playlist_id ) )
        uvis_answer_ajax_request( array(), 404, __("Not a valid playlist ID.", "uvis") );

      if( ! $this->canSaveData( $playlist_id ) )
        uvis_answer_ajax_request( array(), 550, __("Permission denied.", "uvis") );

      $visualizations = $wpdb->get_results( "SELECT * FROM " . $wpdb->posts . " WHERE post_type='". $this->postType . "' AND post_parent=" . $playlist_id . " ORDER BY post_date ASC", ARRAY_A );

      // Add permalinks to visualizations
      foreach( $visualizations as $key => $vis ) {
        $visualizations[$key]["post_permalink"] = get_permalink( $vis["ID"] );
      }

      uvis_answer_ajax_request( $visualizations );

    }


    /**
     * Displays the UVisualize! metabox in the backend
     *
     * @param object : the post object
     */
    public function getVisualizationMetaBox( $post ) {
      if( ! is_object( $post ) )
        return;

      global $wpdb;

      $visualizations = $wpdb->get_results( "SELECT * FROM " . $wpdb->posts ." WHERE post_type='". $this->postType . "' AND post_parent=" . $post->ID . " ORDER BY post_date", ARRAY_A );
      $active_modules = $this->getRegisteredModules();
      echo $this->getTemplatePart( $this->metaBoxTempl, array( "playlist_id" => $post->ID, "visualizations" => $visualizations, "active_modules" => $active_modules ));

    }


    /**
     * Handles saving a visualization by AJAX
     *
     * Expects $GLOBALS["HTTP_RAW_POST_DATA"]["uvis_data"]["config"]
     *
     */
    public function saveVisualization() {
        global $wpdb;

        $uvis_data = json_decode( file_get_contents("php://input"), true );

        if( ! isset( $uvis_data ) || ! is_array( $uvis_data ) || empty( $uvis_data ) )
          uvis_answer_ajax_request( array(), 404, __("uvis_data object does not exist or is empty.", "uvis") );

        $config = $uvis_data["uvis_data"]["config"];

        if( ! $this->canSaveData( $config["uvis_ID"] ) )
          uvis_answer_ajax_request( array(), 550, __("Permission denied.", "uvis") );

        // Is there already a saved configuration?
        $saved_config = get_post_meta( $config["uvis_ID"], "uvis_config", true );
        $saved_config = ( $saved_config != "" && ! empty( $saved_config ) ) ? $saved_config : array();

        // Overwrite existing configuration and keep everything else
        foreach( $config as $key => $val ) {
          $saved_config[$key] = $val; // Generates the visualization's config saved as postmeta
          $post_config[ str_replace( "uvis_", "", $key ) ] = $val; // Generate array to save the post
        }

        // Save visualization's config to postmeta
        update_post_meta( $config["uvis_ID"], "uvis_config", $saved_config );
        update_post_meta( $config["uvis_ID"], "uvis_module", $saved_config["uvis_module"] );

        // Save the visualization's post
        wp_update_post( $post_config );

        // Success
        uvis_answer_ajax_request( $saved_config );

    }


    /**
     * Deletes a visualization
     *
     * Calls getVisualizations() and prints an array of all visualizations to the playlist
     *
     * Expects:
     * $GLOBALS["HTTP_RAW_POST_DATA"]["visualization_id"]
     */
    public function deleteVisualization() {

      $uvis_data = json_decode( file_get_contents("php://input"), true );

      if( ! isset( $uvis_data["visualization_id"] ) || ! is_numeric( $uvis_data["visualization_id"] ) ) {
          uvis_answer_ajax_request( array(), 400, __("This isn't a valid " . $this->postTypeNameSingular . " ID.", "uvis") );
      }

      if( ! $this->canSaveData( $uvis_data["visualization_id"] ) ) {
          uvis_answer_ajax_request( array(), 550, __("You don't have permissions to delete this " . $this->postTypeNameSingular . ".", "uvis") );
      }

      // Get the playlist ID
      $visualization = get_post( $uvis_data["visualization_id"], ARRAY_A );
      $playlist_id = $visualization["post_parent"];

      wp_delete_post( $uvis_data["visualization_id"] );

      // Print a list of all visualizations to this playlist
      $this->getVisualizations( $playlist_id );

    }


    /**
     * Prints the taxonomies and their terms to each post
     * Since this query can be very performance intense the list of terms is loaded only when the filter is used
     */
     function getPlaylistTaxonomies() {
       $uvis_data = json_decode( file_get_contents("php://input"), true );
       $item_ids = get_post_meta( $uvis_data["playlist_id"], $this->uvis_playlist_post_meta, true );

       if( ! is_array( $item_ids ) || count( $item_ids ) < 1 )
         uvis_answer_ajax_request( array(), 404, __("This playlist is empty.", "uvis" ) );

       $taxonomies = array();

       foreach( $item_ids as $item_id ) {
         $taxonomies[ $item_id ] = wp_get_post_terms( $item_id, $this->taxonomies );
       }

       uvis_answer_ajax_request( $taxonomies );
     }


    /**
     * Adds or updates a meta key
     * Prints a message
     *
     * Expects:
     *   int $GLOBALS["HTTP_RAW_POST_DATA"]["post_id"]
     *   string $GLOBALS["HTTP_RAW_POST_DATA"]["meta_key"]
     *   string $GLOBALS["HTTP_RAW_POST_DATA"]["meta_value"]
     *
     * See:
     *   //codex.wordpress.org/Function_Reference/add_post_meta
     *   //codex.wordpress.org/Function_Reference/update_post_meta
     */
    function updatePostMeta() {

      $uvis_data = json_decode( file_get_contents("php://input"), true );

      if( ! isset( $uvis_data["post_id"] ) || ! is_numeric( $uvis_data["post_id"] ) || ! isset( $uvis_data["meta_key"] ) || trim( $uvis_data["meta_key"] ) == "" || ! isset( $uvis_data["meta_value"] ) || trim( $uvis_data["meta_key"] ) == "" )
        uvis_answer_ajax_request( array(), 400, __("Some variables are missing.", "uvis") );

      if( $this->canSaveData( $uvis_data["post_id"] ) ) {
        add_post_meta( $uvis_data["post_id"], $uvis_data["meta_key"], $uvis_data["meta_value"], true );
        update_post_meta( $uvis_data["post_id"], $uvis_data["meta_key"], $uvis_data["meta_value"] );
        uvis_answer_ajax_request( 1 );
      }

      uvis_answer_ajax_request( array(), 403, __("Permission denied.", "uvis") );

    }


    /**
    * Determines if the current user has the relevant permissions for accessing a visualization
    *
    * @param int : the post_id
    * @return boolean
    */
    private function canSaveData( $post_id ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
            return false;
        if ( 'page' == $_POST['post_type'] ) {
            if ( ! current_user_can( 'edit_page', $post_id ) )
                return false;
        } else {
            if ( ! current_user_can( 'edit_post', $post_id ) )
                return false;
        }
        return true;
    }


    /**
     * Registers a new post type for visualization and adds capabilities
     */
    public function registerPostType( $single, $plural ) {

      $labels = array(
          'name' => _x( $single, 'post type general name', 'uvis' ),
          'singular_name' => _x( $single, 'post type singular name', 'uvis' ),
          'add_new' => _x( 'Add New '.$single, $single, 'uvis' ),
          'add_new_item' => __( 'Add New '.$single, 'uvis' ),
          'edit_item' => __( 'Edit '.$single, 'uvis' ),
          'new_item' => __( 'New '.$single, 'uvis' ),
          'all_items' => __( 'All '.$plural, 'uvis' ),
          'view_item' => __( 'View '.$single, 'uvis' ),
          'search_items' => __( 'Search '.$plural, 'uvis' ),
          'not_found' =>  __( 'No '.$plural.' found', 'uvis' ),
          'not_found_in_trash' => __( 'No '.$plural.' found in Trash', 'uvis' ),
          'parent_item_colon' => '',
          'menu_name' => $plural
      );
      $args = array(
          'labels' => $labels,
          'public' => true,
          'publicly_queryable' => true,
          'show_ui' => false,
          'show_in_menu' => false,
          'query_var' => true,
          'rewrite' => array('slug' => str_replace( 'uvis_', '', $this->postType ) ),
          'capability_type' => $this->postType,
          'has_archive' => false,
          'hierarchical' => false,
          'menu_position' => null,
          'supports' => array( 'title', 'editor', $this->metaBoxID, 'author', 'comments', 'sticky', 'thumbnail' )
      );

      register_post_type( $this->postType, $args );

      global $wp_roles;

      // Add capabilities for the admin to access this post type
      if( class_exists( 'WP_Roles' ) ) {

        if ( ! isset( $wp_roles ) )
          $wp_roles = new WP_Roles();

        if( is_object( $wp_roles ) ) {

                    $wp_roles->add_cap( 'administrator', 'edit_'.$this->postType );
                    $wp_roles->add_cap( 'administrator', 'edit_'.$this->postType.'s' );
                    $wp_roles->add_cap( 'administrator', 'edit_others_'.$this->postType.'s' );
                    $wp_roles->add_cap( 'administrator', 'publish_'.$this->postType.'s' );
                    $wp_roles->add_cap( 'administrator', 'read_'.$this->postType.'s' );
                    $wp_roles->add_cap( 'administrator', 'read_private_'.$this->postType.'s' );
                    $wp_roles->add_cap( 'administrator', 'delete_'.$this->postType.'s' );
                    $wp_roles->add_cap( 'administrator', 'delete_'.$this->postType );

                    $wp_roles->add_cap( 'author', 'edit_'.$this->postType );
                    $wp_roles->add_cap( 'author', 'edit_'.$this->postType.'s' );
                    $wp_roles->add_cap( 'author', 'edit_others_'.$this->postType.'s' );
                    $wp_roles->add_cap( 'author', 'publish_'.$this->postType.'s' );
                    $wp_roles->add_cap( 'author', 'read_'.$this->postType.'s' );
                    $wp_roles->add_cap( 'author', 'read_private_'.$this->postType.'s' );
                    $wp_roles->add_cap( 'author', 'delete_'.$this->postType.'s' );
                    $wp_roles->add_cap( 'author', 'delete_'.$this->postType );

        }
      }
    }


    /**
     * Includes certain postmetas to a post array
     * Includes taxonomy term ids
     *
     * @param array : a post's array
     * @return array
     */
    public function processPlaylistItems( $array ) {

      if( ! is_array( $array ) || empty( $array ) )
        return array();

      // Include custom postmetas
      // Use add_filter("uvis_add_postmeta_to_playlist_item", "my_filter_function"); to manipulate this array
      $postmetas = array_unique( apply_filters( "uvis_add_postmeta_to_playlist_item", array() ) );

      foreach( $array as $key => $post ) {

        // Add the permalink
        $array[$key]["post_permalink"] = get_permalink( $post["ID"] );

        // Add the url of the attached media file
        if( $post["post_type"] == "attachment" )
          $array[$key]["post_attachment_url"] = wp_get_attachment_url( $post["ID"] );

        $post_custom = get_post_custom( $post["ID"] ); // Performs better than foreach get_post_meta()

        // Check if the postmeta to be added is there
          foreach( $postmetas as $postmeta ) {
            if( in_array( $postmeta, array_keys( $post_custom ) ) ) {
              $array[$key][$postmeta] = (float) $post_custom[$postmeta][0]; // Only singles are expected
            }
          }

        // The parent post's taxonomy terms will be loaded only when using the filter options since this query can be very performance intense
        // By using the AJAX action "uvis_get_playlist_taxonomies" and only when using the filter options
        if( $post["post_type"] == "post" )
          $array[$key]["taxonomies"] = array(); // wp_get_post_terms( $post["ID"], $taxonomies );

      }

      return $array;

    }


    /**
     * This function parses the post's content for shortcodes
     * It searches their attributes for urls and ids and will try to determine the corresponding post in the database
     * It returns an array containing all post ids found
     *
     * Mind: Externally linked content won't be considered if there isn't a corresponding post row in the database
     *
     * @param string : the post_content
     * @return array : all post ids found
     */
    public function getPostIDsFromShortcodes( $content ) {
      global $wpdb;

      $post_ids = array();

      preg_match_all( '/'. get_shortcode_regex() . '/s', $content, $shortcodes_verbose );
      $shortcodes = $shortcodes_verbose[3]; // Whole shortcodes like [shortcode attr="value"]

      $search_for_attributes = array( "src", "url", "guid", "id", "post_id", "postid" );

      foreach( $shortcodes as $attr ) {

        // Retrieve all shortcode attributes
        $attr_arr = shortcode_parse_atts( $attr );

        // Collect every found value
        foreach( $attr_arr as $attr_name => $attr_val ) {
        if( in_array( $attr_name, $search_for_attributes ) && ! empty( $attr_val ) ) {

            // If numeric search for the ID, if string we assume it's an url
            // TODO: This could be more differentiated
            $where = ( is_numeric( $attr_val ) ) ? "ID=" . esc_sql( $attr_val ) : "guid='" . esc_sql( $attr_val ). "'";

            // Search for a corresponding post
            $post_id = $wpdb->get_var( "SELECT ID FROM ". $wpdb->posts . " WHERE " . $where );

            // Only add it if we found one in the database
            if( ! empty( $post_id ) )
              $post_ids[] = $post_id;

          }
        }

      }

      return array_unique( $post_ids );
    }


    /**
    * Render a template file
    *
    * @param string
    * @param array|null
    * @return string
    */
    public function getTemplatePart( $filePath, $viewData = null ) {

        ( $viewData ) ? extract( $viewData ) : null;

        ob_start();
        include ( $filePath );
        $template = ob_get_contents();
        ob_end_clean();

        return $template;
    }

}


/**
 * Outputs a standardized JSON response object understood by the angular app including errors (if any) and dies.
 *
 * To pass data with a success answer call uvis_answer_ajax_request( $data ) in which $data is a non-empty object or array
 *
 * To pass a plain success answer without any data call uvis_answer_ajax_request( 1 )
 *
 * To give an error without any data returned call e.g. uvis_answer_ajax_request( array(), 404, "Not found" )
 *
 * To give separate errors call e.g. uvis_answer_ajax_request( array(), array( 404, 550 ), array( "Not found", "Permission denied" ) )
 *
 * @param array|object : the data array/object to pass (e.g. the uvisData object generated through getVisualizationDataModel())
 * @param string|array : one or more error codes
 * @param string|array : one or more error messages
 *
 * The output will look like:
 *
 * {
 *   result: 1 || { key: value }, // Result is 1 on generic success OR json data sructure
 *   error: [ // Only exists when something bad happened
 *     {
 *       id: 666, // ID starting at 1, individual codes per call
 *       error_msg: "Error: Does not compute!" // Error message
 *     },
 *     {...}
 *   ]
 * }
 */
function uvis_answer_ajax_request( $data, $codes = '', $messages = '') {

  $codes = ( ! is_array( $codes ) ) ? array( $codes ) : $codes;
  $messages = ( ! is_array( $messages ) ) ? array( $messages ) : $messages;

  $output = new StdClass();
  $output->result = $data;

  if( ! empty( $codes[0] ) ) {
    $i = 0;
    foreach( $codes as $code ) {
      $output->error[$i]["id"] = $codes[$i];
      $output->error[$i]["error_msg"] = $messages[$i];
      $i++;
    }
  }

  die( json_encode( $output ) );

}


/**
 * Call initialization functions
 */
function uvis_init_visualizer() {

      $uvisVisualizer = new UVis_Visualizer();

      /**
       * Enqueue styles and scripts for the frontend
       */
      function uvis_visualizer_enqueue_theme_scripts() {

        wp_enqueue_media(); // Make mediaelements player available

        do_action( "uvis_enqueue_styles" );

        wp_register_style( 'uvis-visualizer', UVIS_MODULES_URL . 'visualizer/css/visualizer.css', false, UVIS_VERSION );
        wp_enqueue_style( 'uvis-visualizer' );

        wp_register_style( 'uvis-ngDialog', UVIS_URL . '/vendor/ngDialog/css/ngDialog.css', false, UVIS_VERSION );
        wp_enqueue_style( 'uvis-ngDialog' );

        wp_register_style( 'uvis-daterange', UVIS_URL . '/css/rangeslider.css', false, UVIS_VERSION, false );
        wp_enqueue_style( 'uvis-daterange' );

        wp_register_script( 'uvis-require', UVIS_URL . '/vendor/require.js', false, UVIS_VERSION, true );
        wp_enqueue_script( 'uvis-require' );

        wp_register_script( 'uvis-visualizer-init', UVIS_URL . '/js/require.js', false, UVIS_VERSION, true );
        wp_enqueue_script( 'uvis-visualizer-init' );

      }
      add_action( "wp_enqueue_scripts", "uvis_visualizer_enqueue_theme_scripts" );

      function uvis_visualizer_print_footer_scripts() {

        global $pagenow;

        if( $pagenow != "index.php" )
          return;

		?>
		<script>
		jQuery(document).ready( function() {
		  jQuery(".ngdialog-close").live( "click", function() {
		    jQuery(".uvisWrap").hide();
		  });
		});
		</script>
		<?php
      }
      add_action( 'wp_print_footer_scripts', 'uvis_visualizer_print_footer_scripts', 200 );

      function uvis_add_visualizer_js_vars() {
        $uvisVisualizer = new UVis_Visualizer();
        $uvis_active_modules = $uvisVisualizer->getRegisteredModules();
        ?>

        <script>
        window.uvis_active_modules = <?php echo json_encode( $uvis_active_modules ); ?>;
        var uvis_visualization_post_type_name_singular = '<?php echo esc_html( get_option( 'uvis_visualization_post_type_name_singular' ) ); ?>';
        var uvis_visualization_post_type_name_plural = '<?php echo esc_html( get_option( 'uvis_visualization_post_type_name_plural' ) ); ?>';
        </script>
        <?php
      }
      add_action( 'wp_head', 'uvis_add_visualizer_js_vars' );
      add_action( 'admin_head', 'uvis_add_visualizer_js_vars' );

}
add_action( 'init', 'uvis_init_visualizer' );


/**
 * Theme function: Determines the icon for a visualization module
 */
function uvis_get_module_icon( $module ) {
  $c = "";

  switch( $module ) {
    case "map" :
      $c = "admin-site";
    break;
    case "timeline" :
      $c = "backup";
    break;
    case "layers" :
      $c = "images-alt2";
    break;
    default:
      $c = ""; // unknown or no module specified
    break;
  }

  return '<span class="dashicons dashicons-' . $c. ' uvis-vis-icon"></span>';

}


/**
 * Theme function: Displays a link to a visualization
 *
 * @param int : visualization_id
 * @param boolean: Whether to open the visualization inline in the same browser window (true) or link to its permalink url (false)
 */
function uvis_link_to_visualization( $visualization_id = 0, $inline = false ) {

  if( ! is_numeric( $visualization_id ) || $visualization_id < 1 )
    echo __('No visualization given.', 'uvis');

  $visualization = get_post( $visualization_id );
  $config = get_post_meta( $visualization_id, "uvis_config", true );

  ?>

  <div class="uvis-vis-row" ng-controller="uvisAppController">
    <!-- <span><?php echo $visualization->ID; ?></span> -->
    <?php echo uvis_get_module_icon( $config["uvis_module"] ); ?>
    <?php if( $inline ) : ?>
      <span class="uvis-vis-title"><a ng-click="view(<?php echo $visualization->ID; ?>)"><?php echo get_the_title( $visualization->ID ); ?></a></span>
    <?php else : ?>
      <span class="uvis-vis-title"><a href="<?php echo get_permalink( $visualization->ID ); ?>"><?php echo get_the_title( $visualization->ID ); ?></a></span>
    <?php endif; ?>
  </div>

  <?php
}


/**
 * Theme function: Displays a list of all visualizations to a playlist
 *
 * @param int : playlist_id
 * @param boolean|string : If the parameter is given the headline can be changed
 */
function uvis_the_visualizations( $playlist_id, $headline = false ) {

  $args = array(
    'posts_per_page'   => -1,
    'orderby'          => 'post_date',
    'order'            => 'ASC',
    'post_type'        => get_option( 'uvis_visualization_post_type' ),
    'post_parent'      => $playlist_id,
    'post_status'      => 'publish',
  );
  $visualizations = get_posts( $args );

  $headline = ( $headline ) ? $headline : get_option( 'uvis_visualization_post_type_name_plural' );
  echo '<h2 class="uvis-headline">' . $headline . '</h2>';

  foreach( $visualizations as $vis ) {
     uvis_link_to_visualization( $vis->ID );
  }

}


/**
 * Theme function: Displays a button for an auto generated visualization
 *
 * Add the following code to a theme file to load an auto generated visualization with custom query criteria
 *
 *   uvis_visualize( array( "taxonomy" => "post_tag", "term" => "my-tag-slug", "module" => "map" );
 *
 *
 * Expected array elements:
 *
 *   $args["taxonomy"] string : The taxonomy name like "post_tag", "category", etc..
 *   $args["term"]     string : A term slug within the taxonomy like "news"
 *   $args["module"]   string : A module name (named after the module directory) like "map", "timeline", "layers", etc.
 *   $args["limit"]    int    : Number of posts per page
 *   $args["offset"]   int    : Start returning posts beginning by this offset
 *   $args["caption"]  string : Title of the visualization/playlist and the caption of the button
 *
 *	 ...and every other configuration setting, defined in getVisualizationDataModel() and in additional plugin filter "uvis_datamodel"
 *
 */
function uvis_visualize( $args ) {

  $args["taxonomy"] = ( isset( $args["taxonomy"] ) && ! empty( $args["taxonomy"] ) ) ? $args["taxonomy"] : "";
  $args["term"] = ( isset( $args["term"] ) && ! empty( $args["term"] ) ) ? $args["term"] : "";
  $args["module"] = ( isset( $args["module"] ) && ! empty( $args["module"] ) ) ? $args["module"] : "map";
  $args["offset"] = ( isset( $args["offset"] ) && ! empty( $args["offset"] ) ) ? $args["offset"] : 0;
  $args["limit"] = ( isset( $args["limit"] ) && ! empty( $args["limit"] ) ) ? $args["limit"] : -1;
  $args["caption"] = ( isset( $args["caption"] ) && ! empty( $args["caption"] ) ) ? $args["caption"] : "All posts by ". $args["taxonomy"] . " '" . $args["term"] . "' on a " . $args["module"];

  ?>

  <div class="uvis-onthefly-button" ng-controller="uvisAppController">
    <?php echo uvis_get_module_icon( $args["module"] ); ?>
    <span class="uvis-vis-title"><a ng-click='viewonthefly(<?php echo json_encode( $args, JSON_HEX_APOS ); ?>)'><?php echo esc_html( $args["caption"] ); ?></a></span>
  </div>

  <?php
}


/**
 * Adds the ability to link to visualization via shortcode
 *
 * Putting the shortcode [uvis id="12345"] in your post content will add a button to open the visualization
 *
 * @param array
 */
function uvis_shortcode( $atts ) {

  $a = shortcode_atts( array( 'id' => 0 ), $atts );

  if( ! is_admin() )
    uvis_link_to_visualization( strip_tags( $atts["id"] ), true );

}
add_shortcode( 'uvis', 'uvis_shortcode' );


function uvis_sharebuttons( $post_id ) {

  $permalink = get_permalink( $post_id );
  $title = urlencode( get_the_title( $post_id ) );

?>


<div id="uvis-share-button"><span class="dashicons dashicons-share"></span>

<!-- I got these buttons from simplesharebuttons.com -->
<div id="uvis-share-buttons">

    <!-- Buffer -->
    <!--
    <a href="https://bufferapp.com/add?url=<?php echo $permalink; ?>&amp;text=<?php echo $title; ?>" target="_blank">
        <img src="https://simplesharebuttons.com/images/somacro/buffer.png" alt="Buffer" />
    </a>-->

    <!-- Digg -->
    <a href="http://www.digg.com/submit?url=<?php echo $permalink; ?>" target="_blank">
        <img src="https://simplesharebuttons.com/images/somacro/diggit.png" alt="Digg" />
    </a>

    <!-- Facebook -->
    <a href="http://www.facebook.com/sharer.php?u=<?php echo $permalink; ?>" target="_blank">
        <img src="https://simplesharebuttons.com/images/somacro/facebook.png" alt="Facebook" />
    </a>

    <!-- Google+ -->
    <a href="https://plus.google.com/share?url=<?php echo $permalink; ?>" target="_blank">
        <img src="https://simplesharebuttons.com/images/somacro/google.png" alt="Google" />
    </a>

    <!-- LinkedIn -->
    <a href="http://www.linkedin.com/shareArticle?mini=true&amp;url=<?php echo $permalink; ?>" target="_blank">
        <img src="https://simplesharebuttons.com/images/somacro/linkedin.png" alt="LinkedIn" />
    </a>

    <!-- Pinterest -->
    <a href="javascript:void((function()%7Bvar%20e=document.createElement('script');e.setAttribute('type','text/javascript');e.setAttribute('charset','UTF-8');e.setAttribute('src','http://assets.pinterest.com/js/pinmarklet.js?r='+Math.random()*99999999);document.body.appendChild(e)%7D)());">
        <img src="https://simplesharebuttons.com/images/somacro/pinterest.png" alt="Pinterest" />
    </a>

    <!-- Reddit -->
    <a href="http://reddit.com/submit?url=<?php echo $permalink; ?>&amp;title=<?php echo $title; ?>" target="_blank">
        <img src="https://simplesharebuttons.com/images/somacro/reddit.png" alt="Reddit" />
    </a>

    <!-- StumbleUpon-->
    <a href="http://www.stumbleupon.com/submit?url=<?php echo $permalink; ?>&amp;title=<?php echo $title; ?>" target="_blank">
        <img src="https://simplesharebuttons.com/images/somacro/stumbleupon.png" alt="StumbleUpon" />
    </a>

    <!-- Tumblr-->
    <a href="http://www.tumblr.com/share/link?url=<?php echo $permalink; ?>&amp;title=<?php echo $title; ?>" target="_blank">
        <img src="https://simplesharebuttons.com/images/somacro/tumblr.png" alt="Tumblr" />
    </a>

    <!-- Twitter -->
    <a href="https://twitter.com/share?url=<?php echo $permalink; ?>&amp;name=<?php echo $title; ?>" target="_blank">
        <img src="https://simplesharebuttons.com/images/somacro/twitter.png" alt="Twitter" />
    </a>

    <!-- VK -->
    <!--
    <a href="http://vkontakte.ru/share.php?url=<?php echo $permalink; ?>" target="_blank">
        <img src="https://simplesharebuttons.com/images/somacro/vk.png" alt="VK" />
    </a>
    -->

    <!-- Yummly -->
    <!--
    <a href="http://www.yummly.com/urb/verify?url=<?php echo $permalink; ?>&amp;title=<?php echo $title; ?>" target="_blank">
        <img src="https://simplesharebuttons.com/images/somacro/yummly.png" alt="Yummly" />
    </a>
    -->

</div>
</div>

<script>
jQuery("#uvis-share-button").live("mouseover", function() {
  jQuery("#uvis-share-buttons").show();
});
jQuery("#uvis-share-button").mouseout( function() {
  jQuery("#uvis-share-buttons").hide();
});
</script>


<?php
}


/**
 * Adds a widget for displaying the most recent visualizations published
 */
class Uvis_Recent_Visualizations_Widget extends WP_Widget {

  /**
   * Register widget with WordPress.
   */
  function __construct() {
    parent::__construct(
      'uvis_recent_visualizations_widget', // Base ID
      __( 'UVisualize! Recent ' . get_option( 'uvis_visualization_post_type_name_plural' ), 'uvis' ), // Name
      array( 'description' => __( 'Most recent ' . get_option( 'uvis_visualization_post_type_name_plural' ), 'uvis' ), ) // Args
    );
  }

  /**
   * Front-end display of widget.
   *
   * @see WP_Widget::widget()
   *
   * @param array $args     Widget arguments.
   * @param array $instance Saved values from database.
   */
  public function widget( $args, $instance ) {
    echo $args['before_widget'];
    if ( ! empty( $instance['title'] ) ) {
      echo $args['before_title'] . apply_filters( 'uvis_visualization_widget_title', $instance['title'] ). $args['after_title'];
    }

    $posts_per_page = ( isset( $instance['number'] ) && is_numeric( $instance['number'] ) && $instance['number'] > 0 ) ? $instance['number'] : 10;

    // Get recent published visualizations
    $visualizations = get_posts( "post_type=" . get_option( "uvis_visualization_post_type", "uvis_visualization" ) . "&post_status=publish&posts_per_page=" . $posts_per_page . "&orderby=date&order=desc", ARRAY_A );

    echo '<div class="uvisWidget">';

    foreach( $visualizations as $vis ) {
      echo '<div class="uvisRecentVisualizations clearfix">';

      if ( $instance["display_post_thumbnail"] == 'Y' ) {
        echo '<span class="uvisThumbnail"><a href="' . get_permalink( $vis->ID ) .'">' . get_the_post_thumbnail( $vis->post_parent, "thumbnail" ). '</a></span>';
      }

      echo '<span class="uvisMetadata">';
      echo '  <span class="uvisVisualizationTitle"><a href="' . get_permalink( $vis->ID ) .'">' . get_the_title( $vis->ID ) . '</a></span>';

      if ( $instance["display_post_date"] == 'Y' ) {
        echo '  <span class="uvisVisualizationDate">' . get_the_date( "d.m.Y", $vis->ID ) . '</span>';
      }

      if ( $instance["display_post_content"] == 'Y' ) {
        echo '  <span class="uvisVisualizationContent"><a href="' . get_permalink( $vis->ID ) .'">' . uvis_truncate( strip_tags( strip_shortcodes( $vis->post_content ) ), $instance['truncate'] ) . '</a></span>';
      }

      echo '</span>
      </div>';

    }

    echo '</div> <!-- /.uvisWidget -->';

    echo $args['after_widget'];
  }

  /**
   * Back-end widget form.
   *
   * @see WP_Widget::form()
   *
   * @param array $instance Previously saved values from database.
   */
  public function form( $instance ) {
    $title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Most recent ' . get_option( 'uvis_visualization_post_type_name_plural' ), 'uvis' );
    $number = ! empty( $instance['number'] ) ? $instance['number'] : 10;
    $display_post_date = ( $instance['display_post_date'] != 'Y' ) ? '' : 'checked="checked"';
    $display_post_content = ( $instance['display_post_content'] != 'Y' ) ? '' : 'checked="checked"';
    $display_post_thumbnail = ( $instance['display_post_thumbnail'] != 'Y' ) ? '' : 'checked="checked"';
    $truncate = ! empty( $instance['truncate'] ) ? $instance['truncate'] : 80;
    ?>
    <p>
    <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
    <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
    </p>

    <p>
    <label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of ' . get_option( 'uvis_visualization_post_type_name_plural' ) . ' to show:' ); ?></label>
    <input class="widefat" id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="number" value="<?php echo esc_attr( $number ); ?>">
    </p>

    <p>
    <label for="<?php echo $this->get_field_id( 'display_post_thumbnail' ); ?>"><?php _e( 'Display thumbnail:' ); ?></label>
    <input class="widefat" id="<?php echo $this->get_field_id( 'display_post_thumbnail' ); ?>" name="<?php echo $this->get_field_name( 'display_post_thumbnail' ); ?>" type="checkbox" value="Y" <?php echo $display_post_thumbnail; ?>>
    </p>

    <p>
    <label for="<?php echo $this->get_field_id( 'display_post_date' ); ?>"><?php _e( 'Display date of publication:' ); ?></label>
    <input class="widefat" id="<?php echo $this->get_field_id( 'display_post_date' ); ?>" name="<?php echo $this->get_field_name( 'display_post_date' ); ?>" type="checkbox" value="Y" <?php echo $display_post_date; ?>>
    </p>

    <p>
    <label for="<?php echo $this->get_field_id( 'display_post_content' ); ?>"><?php _e( 'Display post content:' ); ?></label>
    <input class="widefat" id="<?php echo $this->get_field_id( 'display_post_content' ); ?>" name="<?php echo $this->get_field_name( 'display_post_content' ); ?>" type="checkbox" value="Y" <?php echo $display_post_content; ?>>
    </p>

    <p>
    <label for="<?php echo $this->get_field_id( 'truncate' ); ?>"><?php _e( 'Truncate content to about this number of letters:' ); ?></label>
    <input class="widefat" id="<?php echo $this->get_field_id( 'truncate' ); ?>" name="<?php echo $this->get_field_name( 'truncate' ); ?>" type="number" value="<?php echo esc_attr( $truncate ); ?>">
    </p>

    <?php
  }

  /**
   * Sanitize widget form values as they are saved.
   *
   * @see WP_Widget::update()
   *
   * @param array $new_instance Values just sent to be saved.
   * @param array $old_instance Previously saved values from database.
   *
   * @return array Updated safe values to be saved.
   */
  public function update( $new_instance, $old_instance ) {
    $instance = array();
    $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
    $instance['number'] = ( ! empty( $new_instance['number'] ) ) ? preg_replace( '%([^0-9])%siU', '', $new_instance['number'] ) : '';
    $instance['display_post_date'] = ( $new_instance['display_post_date'] != 'Y' ) ? 'N' : 'Y';
    $instance['display_post_content'] = ( $new_instance['display_post_content'] != 'Y' ) ? 'N' : 'Y';
    $instance['display_post_thumbnail'] = ( $new_instance['display_post_thumbnail'] != 'Y' ) ? 'N' : 'Y';
    $instance['truncate'] = ( ! empty( $new_instance['truncate'] ) ) ? preg_replace( '%([^0-9])%siU', '', $new_instance['truncate'] ) : '';

    return $instance;
  }

}
add_action('widgets_init',
     create_function('', 'return register_widget("Uvis_Recent_Visualizations_Widget");')
);


/**
 * Adds the default options when activating the plugin module for the first time
 */
function uvis_visualizer_activate_module() {
  add_option( 'uvis_convert_shortcodes', 'true' );
  add_option( 'uvis_active_modules', array( "visualizer", "playlist", "layers", "map", "timeline" ) );
  add_option( 'uvis_playlist_enable_dropdown', 'true' );
}
add_action( 'uvis_activate_module', 'uvis_visualizer_activate_module' );

?>