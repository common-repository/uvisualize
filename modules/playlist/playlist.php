<?php
/*
Plugin Name: UVisualize! Playlists. Make collections of your posts.
Plugin URI: https://cba.fro.at/uvisualize
Description: Manage Playlists in order to visualize them.
Author: Ingo Leindecker
Author URI: https://www.fro.at/ingol
*/

class UVis_Playlist {

    // Names of the custom playlist post type
    public $postType = 'uvis_playlist';

    // Meta key to store the playlist items
    public $postMeta = 'uvis_playlist_items';

    // How to call a playlist by default? (e.g. 'Collection', 'Library', 'Item list', etc.)
    public $postTypeNameSingular = 'Playlist';
    public $postTypeNamePlural = 'Playlists';

    // Id of the metabox in the backend
    public $metaBoxID = 'uvis_playlist_metabox';

    // The template of the metabox
    public $metaBoxTempl = 'templates/playlist.tpl.php';

    // Playlist items
    public $playlistItems = array();


		public function __construct() {

        $this->postTypeNameSingular = get_option( "uvis_playlist_post_type_name_singular", $this->postTypeNameSingular );
        $this->postTypeNamePlural = get_option( "uvis_playlist_post_type_name_plural", $this->postTypeNamePlural );
        $this->postType = get_option( "uvis_playlist_post_type", $this->postType );
        $this->postMeta = get_option( "uvis_playlist_post_meta", $this->postMeta );

        // Registers the playlist post type
        $this->registerPostType( $this->postTypeNameSingular, $this->postTypeNamePlural );

        // Loads the Javascript needed on this admin page
        $this->enqueueScripts();
        add_action( 'admin_print_footer_scripts', 'uvis_playlist_print_footer_scripts' );

        // Removes media buttons on playlist screen
        add_action( 'admin_head', array( $this, 'remove_media_buttons' ) );

        // Adds the meta box to playlist screen
        add_action( 'add_meta_boxes', array( $this, 'addMetaBox' ) );

        // Watches for post being saved
        add_action( 'save_post', array( $this, 'savePost' ) );

        // Deletes all visualizations to a playlist after it was deleted
        add_action( 'deleted_post', array( $this, 'deleteVisualizationsOfPlaylist' ) );


        /**
         * AJAX Requests for the playlist dropdown menu
         */

        // Saves a playlist
        add_action( 'wp_ajax_uvis_save_playlist', array( $this, 'savePlaylist' ) );

        // Gets the playlist content
        add_action( 'wp_ajax_uvis_get_playlist', array( $this, 'getPlaylist' ) );

        // Gets recently modified playlists of current user
        add_action( 'wp_ajax_uvis_get_playlists', array( $this, 'getPlaylists' ) );

        // Adds a playlist
        add_action( 'wp_ajax_uvis_add_playlist', array( $this, 'addPlaylist' ) );

        // Adds an item to a playlist
        add_action( 'wp_ajax_uvis_add_to_playlist', array( $this, 'addToPlaylist' ) );

        // Deletes a playlist
        add_action( 'wp_ajax_uvis_delete_playlist', array( $this, 'deletePlaylist' ) );

		}


    /**
     * Removes the Add Media Button on the Playlist page
     */
    public function remove_media_buttons() {
      $screen = get_current_screen();
      if( $screen->post_type == $this->postType ) {
          remove_action( 'media_buttons', 'media_buttons' );
      }
    }


    /**
     * Adds the meta box in the backend
     */
		public function addMetaBox() {

		    add_meta_box(
		        $this->metaBoxID,
		        $this->postTypeNameSingular,
		        array( $this, 'getPlaylist' ), // Get the markup needed
		        $this->postType,
            'normal',
		        'high'
		    );

		}


    /**
     * Creates a new playlist
     */
    public function addPlaylist() {

      if( ! isset( $_POST["title"] ) || trim( $_POST["title"] ) == "" ) {
          echo "Please give a title!";
          die();
      }

      $post = array();
      $post["post_title"] = $_POST["title"];
      $post["post_type"] = $this->postType;

      if( $post_id = wp_insert_post( $post ) ) {
          update_post_meta( $post_id, $this->postMeta, array() );
      }

      echo $this->postTypeNameSingular . " created.";

      die();

    }


    /**
     * Adds an item to a playlist
     * Playlist items and their order are stored in metakey $this->postMeta ('uvis_playlist_items' by default)
     */
    public function addToPlaylist() {

      global $wpdb;

      if( ! isset( $_POST["playlist_id"] ) || ! is_numeric( $_POST["playlist_id"] ) ) {
          return;
      }

      if( ! isset( $_POST["item_id"] ) || ! is_numeric( $_POST["item_id"] ) ) {
          return;
      }

      if ( ! $this->canSaveData( $_POST["playlist_id"] ) ) {
          return;
      }

      // Check the post_type of the item: allow 'post' and 'attachment' only!
      $post_type = $wpdb->get_var( "SELECT post_type FROM " . $wpdb->posts . " WHERE ID=" . esc_sql( $_POST["item_id"] ) );
      if( $post_type != 'post' && $post_type != 'attachment' )
          die( "This post type can't be added." );

      $playlist_items = get_post_meta( $_POST["playlist_id"], $this->postMeta, true );
	    $playlist_items[] = $_POST["item_id"];

      // Get rid of empty elements
      $playlist_items = array_filter( $playlist_items );

      // Copy the array in order to compare it
      $playlist_items_compare = $playlist_items;

      // Get rid of duplicates
      $playlist_items = array_unique( $playlist_items );

      // Is this item already a part of the playlist?
      if( count( $playlist_items_compare ) != count( $playlist_items ) ) {
          exit( "Already a part of the " . $this->postTypeNameSingular . "." );
      }

      update_post_meta( $_POST["playlist_id"], $this->postMeta, $playlist_items );

      echo "Added to " . $this->postTypeNameSingular . ".";

      die();

    }


    /**
     * Get a list of the user's playlists sorted by modification date
     */
    public function getPlaylists() {

      $current_user = wp_get_current_user();
      $ppp = get_option( "uvis_playlist_dropdown_number" ); // Posts per page

      if( ! is_numeric( $current_user->ID ) || ! isset( $_POST["item_id"] ) || ! is_numeric( $_POST["item_id"] ) ) {
          return;
      }

      // Retrieve the last modified playlists
      $query = new WP_Query( 'author=' . $current_user->ID . '&post_type=' . $this->postType . '&orderby=modified&order=desc&showposts=' . $ppp . '&posts_per_page=' . $ppp );

			while ( $query->have_posts() ) {
			  $query->the_post();
			  global $post;

			  $count = count( get_post_meta( $post->ID, $this->postMeta, true ) );

			  // The element attribute "post_id" in this case is the current item
			  echo '<div role="presentation"><a role="menuitem" class="uvis-add-to-playlist btn btn-sm" post_id="' . $_POST["item_id"] . '" playlist_id="' . $post->ID . '" tabindex="-1" title="' . __("Add this item to", "uvis" ) . " " . esc_html( get_the_title() ) . '">+ ' . uvis_truncate( get_the_title(), 42, "...", true ) . ' <small>(' . ($count) . ')</small></a><div class="uvis-btn-manage-playlist pull-right" playlist_id="'. $post->ID . '"><img src="' . UVIS_MODULES_URL . 'playlist/images/btn-edit-playlist.png" alt="Edit ' . $this->postTypeNameSingular . ' title="'. __("Edit", "uvis") . ' ' . esc_html( $post->post_title ) . '" border="0" width="14" height="14" /></div></div>';
			}

      die();

    }


    /**
     * Get all items of a playlist and display them
     * Used for populating the metabox in the backend as well as for managing playlists in the frontend dialog
     *
     * @param object : the post object
     */
		public function getPlaylist( $post ) {

        $die = false;

        // Handle the Ajax request
        if( isset( $_POST["playlist_id"] ) && is_numeric( $_POST["playlist_id"] ) ) {
            $post = get_post( $_POST["playlist_id"] );
            $die = true; // This is an Ajax request - so die die die!
        }

        if( ! is_object( $post ) )
            return;

        $this->playlistItems = get_post_meta( $post->ID, $this->postMeta, true );
        $this->playlistItems = ( is_array( $this->playlistItems ) ) ? $this->playlistItems : array();

		    $json = array();
		    foreach ( $this->playlistItems as $id ) {
		        $json[] = $this->getPlaylistItem( $id );
		    }

        if( empty( $json[0] ) ) {
          echo __( 'No playlist items yet. Go to <a href="edit.php">Posts</a> and add them via the playlist dropdown.', 'uvis' );
        }

		    // Set data needed in the template
		    $viewData = array(
		        'post' => $post,
		        'playlistItems' => json_encode( $json )
		    );

		    echo $this->getTemplatePart( $this->metaBoxTempl, $viewData );

        if( $die )
          die();

		}


    /**
     * Retrieves the data for one playlist item
     *
     * @param object : the post object
     * @return array
     *
     * Comment: Is a call for get_post() actually necessary for each post?
     *          Can't we express a better performing SQL query with the returning array being procesed?
     *
     * 					This function is only called by getPlaylist()
     */
		public function getPlaylistItem( $post_id ) {
        $item = get_post( $post_id );

        return array(
            'playlistItemID' => $post_id,
            'playlistItemTitle' => $item->post_title,
            'playlistItemPostDate' => mysql2date( "d.m.Y", $item->post_date, true )
        );
		}


    /**
     * Handles the backend playlist saving
     *
     * @param int : post_id
     */
		public function savePost( $post_id ) {

		    // Check that we are saving our custom post type
		    if( $_POST['post_type'] !== $this->postType ) {
		        return;
		    }

		    // Check that the user has correct permissions
		    if( ! $this->canSaveData( $post_id ) ) {
		        return;
		    }

		    // Access the data from the $_POST "playlist_items"
		    $playlist_items = ( isset( $_POST["playlist_items"] ) && is_array( $_POST["playlist_items"] ) ) ? $_POST["playlist_items"] : array();

		    update_post_meta( $post_id, $this->postMeta, $playlist_items );

		}


    /**
     * Handles the frontend playlist saving
     */
		public function savePlaylist() {

        global $wpdb;

        if( ! isset( $_POST["playlist_id"] ) || ! is_numeric( $_POST["playlist_id"] ) ) {
            return 0;
        }

		    // Ensure that this user has the correct permissions
		    if( ! $this->canSaveData( $_POST["playlist_id"] ) ) {
		        return 0;
		    }

        $_POST["playlist_order"] = ( is_array( $_POST["playlist_order"] ) ) ? $_POST["playlist_order"] : array();
        $playlist_order = array();

        foreach( $_POST["playlist_order"] as $key => $pl ) {
            $playlist_order[$key] = (int) $pl;
        }

        // Save the new order into the database
        $update = update_post_meta( (int) $_POST["playlist_id"], $this->postMeta, $playlist_order );

        // Update last modification date of playlist
        $wpdb->query( "UPDATE " . $wpdb->posts . " SET post_modified=NOW(), post_modified_gmt=NOW() WHERE ID=" . esc_sql( $_POST["playlist_id"] ) );

        echo $this->postTypeNameSingular . " saved.";

        die();
		}


    /**
     * Deletes a playlist
     */
    public function deletePlaylist() {

      global $wpdb;

      if( ! isset( $_POST["playlist_id"] ) || ! is_numeric( $_POST["playlist_id"] ) ) {
          echo "This isn't a valid " . $this->postTypeNameSingular . " ID.";
          die();
      }

      if( ! $this->canSaveData( $_POST["playlist_id"] ) ) {
          echo "You don't have permissions to delete this " . $this->postTypeNameSingular . ".";
          die();
      }

      // Delete the playlist
      wp_delete_post( $_POST["playlist_id"] );

      $this->deleteVisualizationsOfPlaylist( $_POST["playlist_id"] );

      echo $this->postTypeNameSingular . " deleted.";

      die();

    }


    public function deleteVisualizationsOfPlaylist( $playlist_id ) {

      global $wpdb;

      // Delete its visualizations
      $visualizations = $wpdb->get_results("SELECT ID FROM ". $wpdb->posts ." WHERE post_type='". get_option( "uvis_visualization_post_type", "uvis_visualization" ). "' AND post_parent=". esc_sql( $playlist_id ) , ARRAY_A );
      foreach( $visualizations as $vis ) {
        wp_delete_post( $vis["ID"] );
      }

    }


		/**
		* Determines if the current user has the relevant permissions for accessing a playlist
		*
		* @param int : the playlist_id
		* @return boolean
		*/

		private function canSaveData( $post_id ) {
		    if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		        return false;
		    if( ! empty( $_POST['post_type'] ) && $this->postType == $_POST['post_type'] ) {
		        if( ! current_user_can( 'edit_post', $post_id ) )
		            return false;
		    } else {
		        if( ! current_user_can( 'edit_post', $post_id ) )
		            return false;
		    }
		    return true;
		}


    /**
     * Enqueues scripts for backend and frontend
     */
    private function enqueueScripts() {

      function uvis_playlist_admin_vars(){
      ?>
        <script>
        var uvis_url = '<?php echo UVIS_URL; ?>';
        var uvis_playlist_post_type_name_singular = '<?php echo esc_html( get_option( "uvis_playlist_post_type_name_singular" ) ); ?>';
        var uvis_playlist_post_type_name_plural = '<?php echo esc_html( get_option( "uvis_playlist_post_type_name_plural" ) ); ?>';
        </script>
      <?php
      }
      add_action( 'admin_head', 'uvis_playlist_admin_vars' );
      add_action( 'wp_head', 'uvis_playlist_admin_vars' );

		  wp_enqueue_script( 'jquery' );
		  wp_enqueue_script( 'jquery-ui-core' );
		  wp_enqueue_script( 'jquery-ui-widget' );
		  wp_enqueue_script( 'jquery-ui-mouse' );
		  wp_enqueue_script( 'jquery-ui-position' );
		  wp_enqueue_script( 'jquery-ui-sortable' );
		  wp_enqueue_script( 'jquery-ui-draggable' );
		  wp_enqueue_script( 'jquery-ui-tooltip' );
		  wp_enqueue_script( 'jquery-ui-dialog' );

      wp_enqueue_script( 'underscore' );
      wp_enqueue_script( 'backbone' );

      wp_register_script( 'bootstrap-dropdown', UVIS_URL . '/vendor/bootstrap/js/bootstrap.min.js', false, UVIS_VERSION, false );
      wp_enqueue_script( 'bootstrap-dropdown' );

      wp_register_style( 'bootstrap-dropdown', UVIS_URL . '/vendor/bootstrap/bootstrap.min.css', false, UVIS_VERSION, false );
      wp_enqueue_style( 'bootstrap-dropdown' );

      // Include the Playlist Script
      wp_register_script( 'uvis-playlist', UVIS_MODULES_URL . 'playlist/js/playlist.js' , array( 'backbone' ), null, false );
      wp_enqueue_script( 'uvis-playlist' );

      wp_register_style( 'uvis-playlist', UVIS_MODULES_URL . 'playlist/css/playlist.css' );
      wp_enqueue_style( 'uvis-playlist' );

    }


    /**
     * Registers a new post type for playlists and adds capabilities
     */
		public function registerPostType( $single, $plural ) {

		  $labels = array(
		      'name' => _x( $single, 'post type general name' ),
		      'singular_name' => _x( $single, 'post type singular name' ),
		      'add_new' => _x( 'Add New '.$single, $single, "uvis" ),
		      'add_new_item' => __( 'Add New '.$single, "uvis" ),
		      'edit_item' => __( 'Edit '.$single, "uvis" ),
		      'new_item' => __( 'New '.$single, "uvis" ),
		      'all_items' => __( 'All '.$plural, "uvis" ),
		      'view_item' => __( 'View '.$single, "uvis" ),
		      'search_items' => __( 'Search '.$plural, "uvis" ),
		      'not_found' =>  __( 'No '.$plural.' found', "uvis" ),
		      'not_found_in_trash' => __( 'No '.$plural.' found in Trash', "uvis" ),
		      'parent_item_colon' => '',
		      'menu_name' => $plural
		  );
		  $args = array(
		      'labels' => $labels,
		      'public' => true,
		      'publicly_queryable' => true,
		      'show_ui' => true,
		      'show_in_menu' => true,
		      'query_var' => true,
          'rewrite' => array('slug' => str_replace( 'uvis_', '', $this->postType ) ),
		      'capability_type' => $this->postType,
		      'has_archive' => true,
		      'hierarchical' => false,
		      'menu_position' => null,
		      'supports' => array( 'title', 'editor', $this->metaBoxID, 'author', 'comments', 'sticky', 'thumbnail' )
		  );

		  register_post_type( $this->postType, $args );

		  global $wp_roles;

      // Add capabilities for admins and authors to access this post type
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
		* Renders a template file
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
 * Adds a new column for displaying the playlist dropdown in the backend's manage posts table
 */
function uvis_manage_post_posts_columns( $cols ) {

  // Use this filter to restrict access for certain user roles
  // The filter's return value must be true or false
  $continue = apply_filters( "uvis_before_playlist_dropdown", "true" );
  if( $continue )
    $cols["uvis_addtoplaylist"] = "Add to playlist";

  return $cols;
}
add_filter( 'manage_post_posts_columns', 'uvis_manage_post_posts_columns' );


/**
 * Prints the playlist dropdown for each row in the manage posts screen
 */
function uvis_manage_post_posts_custom_column( $column_name ) {
  if( $column_name == "uvis_addtoplaylist" ) {
    uvis_playlist_dropdown();
  }
}
add_action( 'manage_post_posts_custom_column', 'uvis_manage_post_posts_custom_column' );


/**
 * Adds a new column for displaying the number of playlist items and visualizations in the backend's manage playlists table
 */
function uvis_manage_uvis_playlist_posts_columns( $cols ) {
  $cols["uvis_num_playlist_items"] = "Items";
  $cols["uvis_num_visualizations"] = get_option( "uvis_visualization_post_type_name_plural" );
  return $cols;
}
add_filter( 'manage_uvis_playlist_posts_columns', 'uvis_manage_uvis_playlist_posts_columns' );


/**
 * Prints the number of playlist items and visualizations to each playlist
 */
function uvis_manage_playlist_posts_custom_column( $column_name ) {
  global $post, $wpdb;

  if( $column_name == "uvis_num_playlist_items" ) {
    echo count( get_post_meta( $post->ID, "uvis_playlist_items", true ) );
  }
  if( $column_name == "uvis_num_visualizations" ) {
    echo $wpdb->get_var( "SELECT COUNT(ID) FROM " . $wpdb->posts . " WHERE post_type='" . get_option( "uvis_visualization_post_type" ) . "' AND post_parent=" . $post->ID );
  }
}
add_action( 'manage_uvis_playlist_posts_custom_column', 'uvis_manage_playlist_posts_custom_column' );


/**
 * Displays a dropdown menu for each post in the frontend
 *
 * Lets the user
 * - add a post to a playlist
 * - create a new playlist
 * - manage an existing playlist (sort items, remove items, save, delete)
 *
 */
function uvis_playlist_dropdown() {

  global $post;

  // Only show if user is logged in, if dropdown is enabled and if post_type is post or attachment
  if( ! is_user_logged_in() || get_option( "uvis_playlist_enable_dropdown" ) != "true" || ( $post->post_type != 'post' && $post->post_type != 'attachment' ) ) {
      return;
  }

  // Use this filter to restrict access for certain user roles
  $continue = apply_filters( "uvis_before_playlist_dropdown", "true" );
  // If the filter hook returns false it will break here
  if( ! $continue )
    return;

  // Iterator
  global $uvis_dropdowns;
  $uvis_dropdowns = ( isset( $uvis_dropdowns ) ) ? $uvis_dropdowns + 1 : 1;

  ?>

  <ul class="nav-tabs uvis-dropdown">
    <li class="dropdown">
      <a id="drop<?php echo $uvis_dropdowns; ?>" role="button" data-toggle="dropdown" post_id="<?php the_ID(); ?>" class="btn btn-xs pull-right" href="#"><img src="<?php echo UVIS_MODULES_URL; ?>playlist/images/btn-add-to-playlist.png" title="Add to <?php echo get_option( "uvis_playlist_post_type_name_singular" ); ?>" alt="Add to <?php echo get_option( "uvis_playlist_post_type_name_singular" ); ?>" border="0" /><b class="caret"></b></a>
      <ul id="menu<?php echo $uvis_dropdowns; ?>" class="dropdown-menu pull-right" role="menu" aria-labelledby="drop<?php echo $uvis_dropdowns; ?>">
        <li role="presentation" class="ui-state-disabled uvis-add-to-playlist-after"><div class="uvis-loading"><img src="<?php echo UVIS_MODULES_URL; ?>playlist/images/loading.gif" alt="Loading..." title="Loading..." class="uvis-loading" /></div></li>
        <li role="presentation" class="divider"></li>
        <li role="presentation"><a role="menuitem" class="btn btn-sm uvis-create-playlist" post_id="<?php the_ID(); ?>" tabindex="-1">Create <?php echo get_option( "uvis_playlist_post_type_name_singular" ); ?>...</a></li>
        <li role="presentation"><a role="menuitem" class="btn btn-sm uvis-manage-playlist" href="<?php echo bloginfo( "url" ); ?>/wp-admin/edit.php?post_type=<?php echo get_option( "uvis_playlist_post_type" ); ?>" target="_blank" tabindex="-1">Manage <?php echo get_option( "uvis_playlist_post_type_name_plural" ); ?>...</a></li>
      </ul>
    </li>
  </ul> <!-- /.nav-tabs -->

  <?php
}


/**
 * Prepares the frontend for managing playlists
 */
function uvis_init_playlists() {

	$uvisPlaylist = new UVis_Playlist();

	/**
	 * Enables the featured image (post-thumbnails) for a playlist
	 */

	// Get current post types where post-thumbnails is enabled
	// and prevent them from being overwritten
	global $_wp_theme_features;

	$post_thumbnails_types = $_wp_theme_features["post-thumbnails"][0];
	if( is_array( $post_thumbnails_types ) )
	  $post_thumbnails_types[] = $uvisPlaylist->postType;
	else
	  $post_thumbnails_types = array( $uvisPlaylist->postType );

	add_theme_support( "post-thumbnails", $post_thumbnails_types );


	/**
	 * Enqueue scripts for the frontend
	 */
	function uvis_playlist_enqueue_theme_scripts() {
	}
	add_action( "wp_enqueue_scripts", "uvis_playlist_enqueue_theme_scripts" );


	function uvis_playlist_print_footer_scripts() {

	  global $pagenow;

	  if( $pagenow != "index.php" ) {
	    //return;
	  }

	  ?>

	  <script>
	  var uvis_url = '<?php echo UVIS_URL; ?>';
	  var uvis_playlist_post_type_name_singular = '<?php echo esc_html( get_option( "uvis_playlist_post_type_name_singular" ) ); ?>';
	  var uvis_playlist_post_type_name_plural = '<?php echo esc_html( get_option( "uvis_playlist_post_type_name_plural" ) ); ?>';
	  </script>

	  <div id="uvis-dialog-create-playlist" title="Create <?php echo esc_html( get_option( "uvis_playlist_post_type_name_singular" ) ); ?>">
	    <input type="text" name="uvis-playlist-title" id="uvis-playlist-title" placeholder="Title" size="20" />
	  </div>

	  <div id="uvis-dialog-manage-playlist" title="Manage <?php echo esc_html( get_option( "uvis_playlist_post_type_name_singular" ) ); ?>">
	    <div id="uvis-manage-items"></div>
	  </div>

	  <div id="uvis-dialog-delete-playlist" title="Delete <?php echo esc_html( get_option( "uvis_playlist_post_type_name_singular" ) ); ?>">
	    Do you really want to delete this <?php echo esc_html( get_option( "uvis_playlist_post_type_name_singular" ) ); ?> permanently?
	  </div>

	  <div id="uvis-notification"></div>

	  <?php
	}
	add_action( 'wp_print_footer_scripts', 'uvis_playlist_print_footer_scripts', 200 );

}
add_action( 'init', 'uvis_init_playlists' );


/**
 * Theme function for displaying all items and visualizations of a playlist
 * Use this in single.php and single-uvis_playlist.php
 *
 * @param boolean : Whether to display all of the playlist's visualizations too
 */
function uvis_the_playlist_items( $show_visualizations = false ) {

  global $post;

  $playlist_items = get_post_meta( $post->ID, get_option( "uvis_playlist_post_meta" ), true );

  if( ! is_array( $playlist_items ) || count( $playlist_items ) < 1 )
    echo __("No items yet.", "uvis");

  if( uvis_is_module_active( 'visualizer/visualizer.php' ) && $show_visualizations ) {
    echo '<h2 class="uvis-headline">' . get_option( 'uvis_visualization_post_type_name_plural' ) . '</h2>';

    uvis_the_visualizations( $post->ID );
  }

  echo '<h2 class="uvis-headline">' . get_option( 'uvis_playlist_post_type_name_singular' ) . '</h2>';

  foreach( $playlist_items as $item_id ) {
    $item = get_post( $item_id );

  ?>

    <div class="uvis-playlist-item">
      <span class="uvis-playlist-item-title"><a href="<?php echo get_post_permalink( $item->ID ); ?>"><?php echo get_the_title( $item->ID ); ?></a></span><span class="uvis-playlist-item-date"><?php echo mysql2date( "d.m.Y", $item->post_date ); ?></span>
    </div>

  <?php
  }

}


/**
 * Adds a widget for displaying the most recent playlists published
 */
class Uvis_Recent_Playlists_Widget extends WP_Widget {

  /**
   * Register widget with WordPress.
   */
  function __construct() {
    parent::__construct(
      'uvis_recent_playlists_widget', // Base ID
      __( 'UVisualize! Recent ' . get_option( 'uvis_playlist_post_type_name_plural' ), 'uvis' ), // Name
      array( 'description' => __( 'Most recent ' . get_option( 'uvis_playlist_post_type_name_plural' ), 'uvis' ), ) // Args
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
      echo $args['before_title'] . apply_filters( 'uvis_playlist_widget_title', $instance['title'] ). $args['after_title'];
    }

    $posts_per_page = ( isset( $instance['number'] ) && is_numeric( $instance['number'] ) && $instance['number'] > 0 ) ? $instance['number'] : 10;

    // Get recent published playlists
    $playlists = get_posts( "post_type=" . get_option( "uvis_playlist_post_type", "uvis_playlist" ) . "&post_status=publish&posts_per_page=" . $posts_per_page . "&orderby=date&order=desc", ARRAY_A );

    echo '<div class="uvisWidget">';

    foreach( $playlists as $pl ) {
      echo '<div class="uvisRecentPlaylists clearfix">';

      if ( $instance["display_post_thumbnail"] == 'Y' ) {
        echo '<span class="uvisThumbnail"><a href="' . get_post_permalink( $pl->ID ) .'">' . get_the_post_thumbnail( $pl->ID, "thumbnail" ). '</a></span>';
      }

      echo '<span class="uvisMetadata">';
      echo '  <span class="uvisPlaylistTitle"><a href="' . get_post_permalink( $pl->ID ) .'">' . get_the_title( $pl->ID ). '</a></span>';

      if ( $instance["display_post_date"] == 'Y' ) {
        echo '  <span class="uvisPlaylistDate">' . mysql2date( "d.m.Y", $pl->post_date ) . '</span>';
      }

      if ( $instance["display_post_content"] == 'Y' ) {
        echo '  <span class="uvisVisualizationContent"><a href="' . get_post_permalink( $pl->ID ) .'">' . uvis_truncate( strip_tags( strip_shortcodes( $pl->post_content ) ), $instance['truncate'] ) . '</a></span>';
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
    $title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Most recent ' . get_option( 'uvis_playlist_post_type_name_plural' ), 'uvis' );
    $number = ! empty( $instance['number'] ) ? $instance['number'] : 10;
    $display_post_date = ( $instance['display_post_date'] != 'Y' ) ? '' : 'checked="checked"';
    $display_post_content = ( $instance['display_post_content'] != 'Y' ) ? '' : 'checked="checked"';
    $display_post_thumbnail = ( $instance['display_post_thumbnail'] != 'Y' ) ? '' : 'checked="checked"';
    $truncate = ! empty( $instance['truncate'] ) ? $instance['truncate'] : 80;
    ?>
    <p>
    <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'uvis' ); ?></label>
    <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
    </p>

    <p>
    <label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of ' . get_option( 'uvis_playlist_post_type_name_plural' ) . ' to show:', 'uvis' ); ?></label>
    <input class="widefat" id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="number" value="<?php echo esc_attr( $number ); ?>">
    </p>

    <p>
    <label for="<?php echo $this->get_field_id( 'display_post_thumbnail' ); ?>"><?php _e( 'Display thumbnail:', 'uvis' ); ?></label>
    <input class="widefat" id="<?php echo $this->get_field_id( 'display_post_thumbnail' ); ?>" name="<?php echo $this->get_field_name( 'display_post_thumbnail' ); ?>" type="checkbox" value="Y" <?php echo $display_post_thumbnail; ?>>
    </p>

    <p>
    <label for="<?php echo $this->get_field_id( 'display_post_date' ); ?>"><?php _e( 'Display date of publication:', 'uvis' ); ?></label>
    <input class="widefat" id="<?php echo $this->get_field_id( 'display_post_date' ); ?>" name="<?php echo $this->get_field_name( 'display_post_date' ); ?>" type="checkbox" value="Y" <?php echo $display_post_date; ?>>
    </p>

    <p>
    <label for="<?php echo $this->get_field_id( 'display_post_content' ); ?>"><?php _e( 'Display post content:', 'uvis' ); ?></label>
    <input class="widefat" id="<?php echo $this->get_field_id( 'display_post_content' ); ?>" name="<?php echo $this->get_field_name( 'display_post_content' ); ?>" type="checkbox" value="Y" <?php echo $display_post_content; ?>>
    </p>

    <p>
    <label for="<?php echo $this->get_field_id( 'truncate' ); ?>"><?php _e( 'Truncate content to about this number of letters:', 'uvis' ); ?></label>
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
     create_function('', 'return register_widget("Uvis_Recent_Playlists_Widget");')
);


/**
 * Adds the default options when activating the plugin module for the first time
 */
function uvis_playlist_activate_module() {
    add_option( 'uvis_playlist_post_type', 'uvis_playlist' );
    add_option( 'uvis_playlist_post_type_name_singular', 'Playlist' );
    add_option( 'uvis_playlist_post_type_name_plural', 'Playlists' );
    add_option( 'uvis_playlist_enable_dropdown', 'true' );
    add_option( 'uvis_playlist_dropdown_number', 20 );
    add_option( 'uvis_playlist_post_meta', 'uvis_playlist_items' );
}
add_action( 'uvis_activate_module', 'uvis_playlist_activate_module' );


?>