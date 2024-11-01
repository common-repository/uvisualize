<?php
/*
Plugin Name: UVisualize! Options Management
Plugin URI: http://cba.fro.at/uvisualize
Description: Modify the settings of the plugin
Author: Ingo Leindecker
Author URI: http://fro.at/ingol
*/

class UVis_Options {

  // Required fields for jQuery form validation ( The index in <input name="values[index]"> )
  public $requiredOptions = array( "uvis_playlist_post_type_name_singular", "uvis_playlist_post_type_name_plural", "uvis_playlist_dropdown_number", "uvis_playlist_post_type", "uvis_playlist_post_meta", "uvis_visualization_post_type", "uvis_visualization_post_type_name_singular", "uvis_visualization_post_type_name_plural" );

	public function __construct() {

		// Initializes Options Settings
		add_action ( 'admin_init', array ( &$this, 'uvis_options_page_init' ) );

		// Registers the options page
		add_action ( 'admin_menu', array ( &$this, 'uvis_options_register_admin_page' ), 100 );

		// Includes the footer scripts
		add_action ( 'admin_print_footer_scripts', array ( &$this, 'uvis_options_print_footer_scripts' ) );

		global $pagenow;

		if( $pagenow == 'admin.php' && isset( $_GET["page"] ) &&  $_GET["page"] == "uvis-options" ) {
			wp_register_script( 'jquery-validate', plugins_url( '/vendor/jquery/jquery.validate.min.js', dirname(__FILE__ )) );
			wp_enqueue_script( 'jquery-validate' );
		}

	}

	function uvis_options_register_admin_page() {
		add_submenu_page('uvis-admin', _('Settings'), _('Settings'), 'manage_options', 'uvis-options', array(&$this, 'uvis_options_create_admin_page' ));
	}

	public function uvis_options_create_admin_page() {

		?>

	<script language="javascript">
	// Switches option sections
	jQuery(document).ready(function($) {

		$('form#uvis-options table.form-table').each( function(i) {
			$(this).attr('id', i + 1);
		});
		$('form#uvis-options h3').each( function(i) {
			$(this).attr('class', i + 1);
		});

		// Hide all by default
		$('table.form-table').hide();
		$('h3').hide();

		// Display active group
		var activetab = '';
		if (typeof(localStorage) != 'undefined') {
			activetab = localStorage.getItem("activetab");
		}

		if (activetab != '' && $(activetab).length) {
			$('table' + activetab).fadeIn();
			$('h3.' + activetab.replace('#','')).fadeIn();
		} else {
			$('table.form-table:first').fadeIn();
			$('h3:first').fadeIn();
		}

		if (activetab != '' && $(activetab + '-tab').length) {
			$(activetab + '-tab').addClass('nav-tab-active');
		} else {
			$('.nav-tab-wrapper a:first').addClass('nav-tab-active');
		}

		$('.nav-tab-wrapper a').click(function(evt) {
			$('.nav-tab-wrapper a').removeClass('nav-tab-active');
			$(this).addClass('nav-tab-active').blur();
			var clicked_group = $(this).attr('href');
			if (typeof(localStorage) != 'undefined') {
				localStorage.setItem("activetab", $(this).attr('href'));
			}
			$('table.form-table').hide();
			$('h3').hide();
			$(clicked_group).fadeIn();
			$('h3.' + clicked_group.replace('#','')).fadeIn();
			evt.preventDefault();
		});
	});
	</script>

	<style type="text/css">
	.nav-tab-wrapper {
		border-bottom:1px solid #ccc;
	}
	span.wp-default {
		color:gray;
		padding-left:10px;
	}
	span.error {
		color:red;
		padding-left:10px;
	}
	</style>

	<div class="wrap">
			<?php screen_icon("options-general"); ?>
			<h2>UVisualize! <?php _e("Settings"); ?></h2>

			<h4 class="nav-tab-wrapper">
				<a id="1-tab" class="nav-tab" href="#1"><?php _e("Visualizer"); ?></a>
		 		<a id="2-tab" class="nav-tab" href="#2"><?php _e("Playlist"); ?></a>
				<a id="3-tab" class="nav-tab" href="#3"><?php _e("Map"); ?></a>
			</h4>

			<form method="post" action="options.php" id="uvis-options">
		<?php

				// Print hidden setting fields
				settings_fields( 'uvis-visualizer-options' );
				do_settings_sections( 'uvis-visualizer-options' );

		?>
					<?php submit_button(); ?>
			</form>
	</div>
	<?php
	}

	public function uvis_options_page_init() {

		$uploads_dir = wp_upload_dir();

		$required = array();
		global $required;

		register_setting( 'uvis-visualizer-options', 'values', array( $this, 'uvis_options_check_form' ) );
      add_settings_section(
      'uvis-the-visualizer-options',
      __('Visualizer', 'uvis'),
      array( $this, 'uvis_options_print_visualizer_options_info' ),
      'uvis-visualizer-options'
    );

		/* Playlist settings */

		register_setting( 'uvis-playlist-options', 'values', array( $this, 'uvis_options_check_form' ) );
			add_settings_section(
			'uvis-the-playlist-options',
			__('Playlists', 'uvis'),
			array( $this, 'uvis_options_print_playlists_options_info' ),
			'uvis-visualizer-options'
		);

    add_settings_field(
      'uvis_playlist_enable_dropdown',
      __('Enable the dropdown menu in frontend?', 'uvis'),
      array( $this, 'uvis_options_create_checkbox'),
      'uvis-visualizer-options',
      'uvis-the-playlist-options',
      array("option_name" => "uvis_playlist_enable_dropdown",
            "helper"      => __("Requires you to call the function <code>uvis_playlist_dropdown()</code> in your theme to add the playlist menu to every post in your frontend.", "uvis"))
    );

/*
    add_settings_field(
      'uvis_playlist_post_type_name_singular',
      __('How a &quot;Playlist&quot; should be actually called (singular)', 'uvis'),
      array( $this, 'uvis_options_create_text_input'),
      'uvis-visualizer-options',
      'uvis-the-playlist-options',
      array("option_name" => "uvis_playlist_post_type_name_singular",
            "helper"      => __("Name the term as it fits best to your project. E.g. 'Playlist', 'Collection', 'Library', 'Item list', ... &nbsp;Must not be empty.", "uvis"),
            "required"    => true )
    );

    add_settings_field(
      'uvis_playlist_post_type_name_plural',
      __('Plural of the above:', 'uvis'),
      array( $this, 'uvis_options_create_text_input'),
      'uvis-visualizer-options',
      'uvis-the-playlist-options',
      array("option_name" => "uvis_playlist_post_type_name_plural",
            "helper"      => __("Must not be emtpy.", "uvis"),
            "required"    => true )
    );
*/

    add_settings_field(
      'uvis_playlist_dropdown_number',
      __('How many recent modified playlists to show in the dropdown menu', 'uvis'),
      array( $this, 'uvis_options_create_text_input'),
      'uvis-visualizer-options',
      'uvis-the-playlist-options',
      array("option_name" => "uvis_playlist_dropdown_number",
            "helper"      => __("Must not be empty.", "uvis"),
            "default"     => 20,
            "condition"   => "is_integer",
            "required"    => true )
    );
/*
    add_settings_field(
      'uvis_playlist_post_type',
      __('Name of the playlist\'s post_type', 'uvis'),
      array( $this, 'uvis_options_create_text_input'),
      'uvis-visualizer-options',
      'uvis-the-playlist-options',
      array("option_name" => "uvis_playlist_post_type",
            "helper"      => __("Must not be empty. Must only contain alphanumeric characters and underscores.", "uvis"),
            "default"     => "uvis_playlist",
            "condition"   => "uvis_is_alphanumeric",
            "required"    => true,
            "disabled"    => true
          )
    );

    add_settings_field(
      'uvis_playlist_post_meta',
      __('Name of the post meta_key where the playlist items will be stored', 'uvis'),
      array( $this, 'uvis_options_create_text_input'),
      'uvis-visualizer-options',
      'uvis-the-playlist-options',
      array("option_name" => "uvis_playlist_post_meta",
            "helper"      => __("Must not be empty. Must only contain alphanumeric characters and underscores.", "uvis"),
            "default"     => "uvis_playlist_items",
            "condition"   => "uvis_is_alphanumeric",
            "required"    => true,
            "disabled"    => true
          )
    );
*/

    /* Visualizer settings */

    register_setting( 'uvis-visualizer-options', 'values', array( $this, 'uvis_options_check_form' ) );
      add_settings_section(
      'uvis-the-visualizer-options',
      'Visualizer',
      array( $this, 'uvis_options_print_visualizer_options_info' ),
      'uvis-visualizer-options'
    );

/*
    add_settings_field(
      'uvis_visualization_post_type_name_singular',
      __('How a &quot;Visualization&quot; should be actually called (singular)', 'uvis'),
      array( $this, 'uvis_options_create_text_input'),
      'uvis-visualizer-options',
      'uvis-the-visualizer-options',
      array("option_name" => "uvis_visualization_post_type_name_singular",
            "helper"      => __("Name the term as it fits best to your project. E.g. 'Visualization', 'Presentation', 'Display', ... &nbsp;Must not be empty.", "uvis"),
            "required"    => true )
    );

    add_settings_field(
      'uvis_visualization_post_type_name_plural',
      __('Plural of the above:', 'uvis),
      array( $this, 'uvis_options_create_text_input'),
      'uvis-visualizer-options',
      'uvis-the-visualizer-options',
      array("option_name" => __("uvis_visualization_post_type_name_plural", "uvis"),
            "helper"      => ("Must not be emtpy.", "uvis"),
            "required"    => true )
    );
*/

    add_settings_field(
      'uvis_convert_shortcodes',
      __('Treat shortcodes as attachments', 'uvis'),
      array( $this, 'uvis_options_create_checkbox'),
      'uvis-visualizer-options',
      'uvis-the-visualizer-options',
      array("option_name" => "uvis_convert_shortcodes",
            "helper"      => __("Whether shortcodes should be treated as attached media files (Local files only! Externally linked content won't be considered)", "uvis") )
    );
/*
    add_settings_field(
      'uvis_visualization_post_type',
      __('Name of the visualization\'s post_type', 'uvis'),
      array( $this, 'uvis_options_create_text_input'),
      'uvis-visualizer-options',
      'uvis-the-visualizer-options',
      array("option_name" => "uvis_visualization_post_type",
            "helper"      => __("Must not be empty. Must only contain alphanumeric characters and underscores.", "uvis"),
            "default"     => "uvis_visualization",
            "condition"   => "uvis_is_alphanumeric",
            "required"    => true,
            "disabled"    => true
          )
    );
*/

		/* Map Settings */

		register_setting( 'uvis-map-options', 'values', array( $this, 'uvis_options_check_form' ) );
			add_settings_section(
			'uvis-map-options',
			'Map',
			array( $this, 'uvis_options_print_map_options_info' ),
			'uvis-visualizer-options'
		);


		add_settings_field(
			'uvis_map_basemaps',
			__('Basemaps', 'uvis'),
			array( $this, 'uvis_options_create_basemap_select'),
			'uvis-visualizer-options',
			'uvis-map-options',
			array("option_name" => "uvis_map_basemaps",
					"helper" => "",
					//"required" => "true"
					)
		);

	}

	public function uvis_options_print_visualizer_options_info() {
		print '';
	}

	public function uvis_options_print_playlists_options_info() {
		print '';
	}

	public function uvis_options_print_map_options_info() {
		print '';
	}


	/**
	 * Processes the submitted form
	 */
	public function uvis_options_check_form( $input ) {

    $input = ( is_array( $input ) && ! empty( $input) ) ? $input : $_POST["values"];
		$options = array_keys( $input );

    unset( $input["uvis_basemap_title"] );
    unset( $input["uvis_basemap_handle"] );
    unset( $input["uvis_basemap_url"] );
    unset( $input["uvis_basemap_subdomains"] );
    unset( $input["uvis_basemap_maxZoom"] );
    unset( $input["uvis_basemap_description"] );

		// Take care of checkboxes
		if( ! in_array( 'uvis_is_windows', $options ) )
			$input['uvis_is_windows'] = 'false';

    if( ! in_array( 'uvis_playlist_enable_dropdown', $options ) )
      $input['uvis_playlist_enable_dropdown'] = 'false';

    if( ! in_array( 'uvis_convert_shortcodes', $options ) )
      $input['uvis_convert_shortcodes'] = 'false';

		foreach( $input as $option_name => $option_value ) {

			// Execute conditions after submit
			if( $_POST['condition'][$option_name] && trim( $_POST['condition'][$option_name] ) != '' ) {

				$conditions = explode( ',', $_POST['condition'][$option_name] );

        // Kick none-alphanumeric characters
        if( in_array( 'uvis_is_alphanumeric', $conditions ) ) {
          $option_value = uvis_is_alphanumeric( $option_value );
        }

				// Set trailing slashes
				if( in_array( 'uvis_options_has_trailing_slash', $conditions ) ) {
					$option_value = uvis_options_has_trailing_slash( $option_value );
				}

				// Serialize arrays
				if( in_array( 'uvis_options_serialize_array', $conditions ) ) {
					$option_value = array_filter( explode(',', trim( str_replace(' ', '', $option_value ) ) ) );
					sort( $option_value );
				}

			}

      // Put together the basemaps array
      if( $option_name == 'uvis_basemaps' ) {

        $uvis_basemaps = array();
        $i = 0;

        foreach( $input['uvis_basemaps'] as $basemap ) {

          $bm = explode( "/#-#uvis#-#/", $basemap );

          $uvis_basemaps[$i]["handle"] = $bm[0];
          $uvis_basemaps[$i]["title"] = $bm[1];
          $uvis_basemaps[$i]["url"] = $bm[2];
          $uvis_basemaps[$i]["subdomains"] = ( isset( $bm[3] ) && trim( $bm[3] ) != "" ) ? explode(",", $bm[3] ) : array();
          $uvis_basemaps[$i]["maxZoom"] = ( isset( $bm[4] ) && trim( $bm[4] ) != "" ) ? $bm[4] : '';
          $uvis_basemaps[$i]["description"] = ( isset( $bm[5] ) ) ? $bm[5] : '';

          $i++;
        }

        $option_value = $uvis_basemaps;
      }

			update_option( $option_name, $option_value );

		}

		return true;
	}


	/**
	 * Creates a text input field
	 *
	 * Arrays will be transformed into comma separated values
	 *
	 * @param array $args(
	 *						boolean|null ['disabled']
	 *						string|null ['default'] the default variable
	 *						string|null ['helper'] helper text
	 */
	public function uvis_options_create_text_input( $args ) {

		global $required;

		// Disables the input field
		$disabled = ( ! empty( $args['disabled'] ) ) ? 'disabled="disabled" class="disabled"' : '';

		// Print the default constant or variable for this usecase
		$default = ( ! empty ( $args['default'] ) ) ? '<span class="default"> Default: <code>'.$args['default'].'</code></span>' : '';

		// Convert arrays to comma separated values
		$option_value = is_array( get_option( $args['option_name'] ) ) ? trim( str_replace( ' ', '', implode( ',', get_option( $args['option_name'] ) ) ) ) : trim ( get_option( $args['option_name'] ) );

		// Print hidden input to test for condition after submit
		echo ( ! empty( $args['condition'] ) ) ? uvis_options_create_hidden_input( 'condition['. $args['option_name'] . ']', $args['condition'] ) : '';

		$required = ( ! empty( $args['required'] ) ) ? 'class="required"' : '';

    // If the option doesn't exist yet, give the input the default value
    if( $required != '' && ! $option_value ) {
      update_option( $args['option_name'], $args['default'] );
      $option_value = $args['default'];
    }

		// Test result of the existing value
		$condition_result = ( ! empty( $args['condition'] ) ) ? uvis_options_test_for_condition( $option_value, $args['condition'] ) : '';

		?><label><input type="text" id="<?php echo $args['option_name']; ?>" name="values[<?php echo $args['option_name']; ?>]" value="<?php echo esc_attr( $option_value ); ?>" <?php echo $disabled; ?> <?php echo $required; ?> size="50" /><?php echo $default; ?><span class="error"><?php echo $condition_result; ?></span></label><?php

		if( $args['helper'] && ! empty( $args['helper'] ) )
			?><p class="description"><?php echo $args['helper']; ?></p><?php
	}


	/**
	 * Creates a checkbox
	 *
	 * @param array $args(
	 *						boolean ['disabled'] state of the checkbox field
	 *						boolean ['option_name'] the option's name in the db
	 *						string  ['default'] the option's default value
	 *						string  ['helper'] helper text
	 *        )
	 */
	public function uvis_options_create_checkbox( $args ) {

		// Disable the checkbox
		$disabled = ( ! empty( $args['disabled'] ) && $args['disabled'] === true ) ? 'disabled="disabled" class="disabled"' : '';

		// See if it's checked
		$checked = ( get_option( $args['option_name'] ) == 'true' ) ? 'checked="checked"' : '';

		// Print the default constant or variable for this usecase
		$default = ( ! empty( $args['default'] ) ) ? '<span class="default">'.$args["default"].'</span>' : '';

		?><label><input type="checkbox" id="<?php echo $args['option_name']; ?>" name="values[<?php echo $args['option_name']; ?>]" value="true" <?php echo $disabled; ?> <?php echo $checked; ?> /> Yes <?php echo $default; ?></label><?php

		if( $args['helper'] && !empty( $args['helper'] ) )
			?><p class="description"><?php echo $args['helper']; ?></p><?php
	}



	/**
	 * Creates a select drop down
	 *
	 * @param array $args(
	 *						boolean ['disabled'] state of the select field
	 *						boolean ['option_name'] the option's name in the db
	 *						string  ['default'] the option's default value
	 *						string  ['helper'] helper text
	 *            array   ['values'] the option values to select from
	 *            array   ['labels'] the corresponding label of the option
	 *        )
	 */
	public function uvis_options_create_select( $args ) {

		// Disable the checkbox
		$disabled = ( $args['disabled'] === true ) ? 'disabled="disabled" class="disabled"' : '';

		// Print the default constant or variable for this usecase
		$default = ( $args['default'] ) ? '<span class="default">' . $args["default"] . '</span>' : '';

		if( is_array( $args['values'] ) && count( $args['values'] ) > 0 ) {
		  ?><select id="<?php echo $args['option_name']; ?>" name="values[<?php echo $args['option_name']; ?>]" <?php echo $disabled; ?>><?php
			foreach( $args['values'] as $key => $val ) {
				// See if it's the selected one
				$selected = ( get_option( $args['option_name'] ) == $val ) ? 'selected="selected"' : '';

			?><option value="<?php echo $val; ?>" <?php echo $selected; ?>><?php echo $args['labels'][$key]; ?></option><?php
			}

		?></select><?php
		}

		if( $args['helper'] && !empty( $args['helper'] ) )
			?><p class="description"><?php echo $args['helper']; ?></p><?php
	}


  /**
   *  Creates the basemap configuration
   */
  public function uvis_options_create_basemap_select( $args ) {

    if( isset( $_GET["restorebasemaps"] ) && $_GET["restorebasemaps"] == "true" ) {
      $default_basemaps = get_option( "uvis_default_basemaps" );
      update_option( "uvis_basemaps", $default_basemaps );
      wp_redirect( "admin.php?page=uvis-options&msg=" . urlencode( "Default basemaps restored." ) );
    }

    if( isset( $_GET["msg"] ) && $_GET["msg"] != "" )
      echo '<div class="updated">' . $_GET["msg"] . '</div>';

    $dlmtr = '/#-#uvis#-#/';

    $uvis_basemaps = ( is_array ( get_option( 'uvis_basemaps' ) ) ) ? get_option( 'uvis_basemaps' ) : get_option( 'uvis_default_basemaps' );

    ?>

    <select id="uvis_basemaps" style="width:50%;">
      <option id="uvis-option-add-basemap">Add new</option>
    <?php
      foreach( $uvis_basemaps as $basemap ) {
        $basemap["subdomains"] = is_array( $basemap["subdomains"] ) ? implode( ",", $basemap["subdomains"] ) : $basemap["subdomains"];
        echo '<option value="' . $basemap["handle"] . $dlmtr . esc_attr( strip_tags( $basemap["title"] ) ) . $dlmtr . $basemap["url"] . $dlmtr . $basemap["subdomains"] . $dlmtr . $basemap["maxZoom"] . $dlmtr . esc_attr( strip_tags( $basemap["description"] ) ) . '">' . $basemap["title"] . '</option>';
      }
    ?>

    </select>
    <br /><br />

    <?php

    $args['option_name'] = "uvis_basemap_title";
    $args['helper'] = __("The map's name", "uvis");

    $this->uvis_options_create_text_input( $args );
    echo "<br />";

    $args['option_name'] = "uvis_basemap_handle";
    $args['helper'] = __("A unique term for identifying the basemap. Don't change this if there are already visualizations using this map, otherwise the map can't be related anymore. Only alphanumeric characters, underscores and dashes.", "uvis");

    $this->uvis_options_create_text_input( $args );
    echo "<br />";

    $args['option_name'] = "uvis_basemap_url";
    $args['helper'] = __("URL to the basemap tiles (e.g. &quot;http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png&quot;) Don't use backslashes for escaping.", "uvis");

    $this->uvis_options_create_text_input( $args );
    echo "<br />";

    $args['option_name'] = "uvis_basemap_subdomains";
    $args['helper'] = __("Name of possible subdomains. The placeholder {s} is needed for this in the URL above. Separate domain names by comma!", "uvis");
    $args['condition'] = "uvis_options_serialize_array";

    $this->uvis_options_create_text_input( $args );
    echo "<br />";

    $args['option_name'] = "uvis_basemap_maxZoom";
    $args['helper'] = __("Maximum zoom level for this map.", "uvis");
    $args['condition'] = "";

    $this->uvis_options_create_text_input( $args );
    echo "<br />";

    $args['option_name'] = "uvis_basemap_description";
    $args['helper'] = __("Description and copyright notes.", "uvis");
    $args['condition'] = "";

    $this->uvis_options_create_text_input( $args );
    echo "<br />";

    ?>

    <span class="button" id="uvis-add-basemap"><?php _e("Add"); ?></span> <span class="button" id="uvis-update-basemap" style="display:none;">Update</span> <span class="button" id="uvis-delete-basemap"><?php _e("Delete"); ?></span>
    <div id="uvis-hidden"></div>

    <p><a href="admin.php?page=uvis-options&restorebasemaps=true" class="alignright button"><?php _e("Restore default basemaps", "uvis"); ?></a></p>

    <script>
    jQuery(document).ready( function() {

      // Clicking the Add-button
      jQuery("#uvis-add-basemap").live("click", function() {

        var basemap_handle = jQuery("#uvis_basemap_handle").val().replace(/[^\w-]+/g, "").toLowerCase(); // Alphanumeric chars and dashes only
        var basemap_title = jQuery("#uvis_basemap_title").val().replace(/\"/g, ''); // Get rid of quotes
        var basemap_url = jQuery("#uvis_basemap_url").val();
        var basemap_subdomains = jQuery("#uvis_basemap_subdomains").val();
        var basemap_maxZoom = jQuery("#uvis_basemap_maxZoom").val();
        var basemap_description = jQuery("#uvis_basemap_description").val();
        var basemap_option_val = basemap_handle + '<?php echo $dlmtr; ?>' + basemap_title + '<?php echo $dlmtr; ?>' + basemap_url + '<?php echo $dlmtr; ?>' + basemap_subdomains + '<?php echo $dlmtr; ?>' + basemap_maxZoom + '<?php echo $dlmtr; ?>' +  basemap_description;
        basemap_option_val = basemap_option_val.replace(/\"/g, ''); // Get rid of quotes

        // Add new option to select
        if( basemap_title != '' && basemap_url != '' && basemap_handle != '') {
          jQuery("select#uvis_basemaps").append( '<option value="' + basemap_option_val + '">' + basemap_title + '</option>');
          jQuery("select#uvis_basemaps option:last").trigger("click").attr("selected", "selected");
          uvisUpdateHiddenBasemaps();
        }

        jQuery("#uvis-add-basemap").hide();
        jQuery("#uvis-delete-basemap").show();

      });

      // Selecting an option
      jQuery("select#uvis_basemaps option").live( "click", function() {

        var selected_basemap_arr = jQuery(this).val().split('<?php echo $dlmtr; ?>');

        jQuery("#uvis_basemap_handle").val( selected_basemap_arr[0] );
        jQuery("#uvis_basemap_title").val( selected_basemap_arr[1] );
        jQuery("#uvis_basemap_url").val( selected_basemap_arr[2] );
        jQuery("#uvis_basemap_subdomains").val( selected_basemap_arr[3] );
        jQuery("#uvis_basemap_maxZoom").val( selected_basemap_arr[4] );
        jQuery("#uvis_basemap_description").val( selected_basemap_arr[5] );

        jQuery("#uvis-add-basemap").hide();
        jQuery("#uvis-delete-basemap").show();

      });


      // Selecting the Add-Basemap option
      jQuery("option#uvis-option-add-basemap").live( "click", function() {

        jQuery("#uvis_basemap_handle").val('');
        jQuery("#uvis_basemap_title").val('');
        jQuery("#uvis_basemap_url").val('');
        jQuery("#uvis_basemap_subdomains").val('');
        jQuery("#uvis_basemap_maxZoom").val('');
        jQuery("#uvis_basemap_description").val('');
        jQuery("#uvis-add-basemap").show();
        jQuery("#uvis-delete-basemap").hide();

      });

      // Clicking the Update button
      jQuery("#uvis-update-basemap").live( "click", function() {

        var basemap_handle = jQuery("#uvis_basemap_handle").val().replace(/[^\w-]+/g, "").toLowerCase(); // Alphanumeric chars and dashes only
        var basemap_title = jQuery("#uvis_basemap_title").val().replace(/\"/g, ''); // Get rid of quotes
        var basemap_url = jQuery("#uvis_basemap_url").val();
        var basemap_subdomains = jQuery("#uvis_basemap_subdomains").val();
        var basemap_maxZoom = jQuery("#uvis_basemap_maxZoom").val();
        var basemap_description = jQuery("#uvis_basemap_description").val();
        var basemap_option_val = basemap_handle + '<?php echo $dlmtr; ?>' + basemap_title + '<?php echo $dlmtr; ?>' + basemap_url + '<?php echo $dlmtr; ?>' + basemap_subdomains + '<?php echo $dlmtr; ?>' + basemap_maxZoom + '<?php echo $dlmtr; ?>' +  basemap_description;;

        basemap_option_val = basemap_option_val.replace(/\"/g, ''); // Get rid of quotes

        jQuery("select#uvis_basemaps option:selected").val( basemap_option_val ).text( basemap_title );

        uvisUpdateHiddenBasemaps();

      });

      // Clicking the delete button
      jQuery("#uvis-delete-basemap").live( "click", function() {

        if( jQuery("select#uvis_basemaps option:selected").text() != "Add new")
          jQuery("select#uvis_basemaps option:selected").remove(); // Remove the option

        jQuery("option#uvis-option-add-basemap").trigger("click"); // Select the add new option

        uvisUpdateHiddenBasemaps();

      });

    });

    jQuery("form#uvis-options input").live( "blur", function() {
      jQuery("#uvis-update-basemap").trigger("click");
    });

    function uvisUpdateHiddenBasemaps() {
        jQuery("#uvis-hidden").html('');
        var options = jQuery("select#uvis_basemaps option");
        options.each( function( key, value ) {
          if( key > 0 )
            jQuery("#uvis-hidden").append('<input type="hidden" name="values[uvis_basemaps][]" value="' + options[key].value + '" />');
        });
    }

    uvisUpdateHiddenBasemaps();
    jQuery("option#uvis-option-add-basemap").trigger("click");
    </script>

    <?php

  }



	function uvis_options_print_footer_scripts() {

    global $pagenow;

    if( $pagenow == "admin.php" && $_GET["page"] == "uvis-options" ) {

		?>
		<script language="javascript">
		jQuery( function($) {

			var errorLabelContainer = $('<p class="error errorlabels"></p>').appendTo('form#uvis-options').hide();

			$('form#uvis-options').validate({
				rules : {
			<?php
				foreach($this->requiredOptions as $ro) {
			?>
					'values[<?php echo $ro; ?>]' : {
						required: true,
						minlength: 1
					},
			<?php
				}
			?>
				}
			});
		});
		</script>
		<?php

    } // on the right page?

	}

} // End of class


/**
 * Creates a hidden input field.
 *
 * @param string : var name
 * @param string : var value
 * @return string
 */
function uvis_options_create_hidden_input( $name, $value ) {
  return '<input type="hidden" name="' . $name . '" value="' . $value . '" />';
}


/**
 * Tests a submitted form value (e.g. a server path) for given conditions
 *
 * @param string : the value (e.g. "/var/www/")
 * @param string : php functions to test the value for separated by commas (e.g. "is_dir,is_executable")
 * @return string : error message if the condition returned false
 */
function uvis_options_test_for_condition( $option_value, $conditions = '' ) {

  $condition_result = '';
  $conditions_a = array();

  // Only these functions in keys are executed, otherwise ignored for security reasons
  $errormsg['is_dir'] = __("This directory doesn't exist.", "uvis");
  $errormsg['file_exists'] = __("This file doesn't exist.", "uvis");
  $errormsg['is_writable'] = __("This path is not writeable.", "uvis");
  $errormsg['is_executable'] = __("This path is not executable.", "uvis");
  $errormsg['is_integer'] = __("This is not an integer.", "uvis");

  $allowed_functions = array_keys( $errormsg );

  if( trim( $conditions ) != '' ) {
    // Split conditions by comma
    $conditions_a = explode( ',', $conditions );

    // ...and call the function on the value
    foreach( $conditions_a as $condition ) {

      $condition = trim( $condition );

      if( function_exists( $condition ) && in_array( $condition, $allowed_functions ) ) {

        $eval = "if( ! " . $condition . "('" . addslashes( $option_value ) . "') ) return \" " . $errormsg[$condition] . "\";";

        // Exceptions
        if( $condition == 'is_integer' && is_numeric( $option_value ) ) {
          $eval = "if( ! " . $condition . "(" . $option_value . ") ) return \" " . $errormsg[$condition] . "\";";
        }
        if( $condition == 'is_integer' && ! is_numeric( $option_value ) ) {
          $eval = "if( 1 === 1) return \$errormsg[\$condition];";
        }

        $condition_result .= eval($eval);

      }
    }
  }

  return $condition_result;

}


/**
 * Removes all characters except letters, digits and underscores from a string
 *
 * @param string
 * @return string
 */
function uvis_is_alphanumeric( $str ) {
 return preg_replace( '%([^a-z0-9_])%siU', '', $str );
}


/**
 * Forces a string to end with a trailing slash
 *
 * @param string
 * @return string
 */
function uvis_options_has_trailing_slash( $str ) {

  $slash = '/';

  if( $_POST['values']['uvis_is_windows'] == 'true' ) {
    $slash = '\\';
  }

  if( substr( $str, -1 ) != '/' && substr( $str, -1 ) != '\\' ) {
    $str = $str . $slash;
  }

  return $str;
}

?>