<?php
/**
* Plugin Name: InfusionPress Forms
* Plugin URI: http://countryfriedcoders.me
* Description: WP Plugin to easily add IS forms with AJAX submission and custom Thank You popups
* Version: 1.0
* Author: Ben Redden
* Author URI: http://benjaminredden.we.bs
* License: GPL2.0
*/

/*
 * require vendor autoload
 */
require_once 'vendor/autoload.php';

/*
 * require retrieval stuff
 */
require_once(  plugin_dir_path( __FILE__ ) . 'retrieve.php');

/*
 * get the site url for the redirect
 */
$redirectUrl = get_site_url() . '/wp-content/plugins/infusionpress_forms/auth.php';

/*
 * require secret stuff
 */
require_once(  plugin_dir_path( __FILE__ ) . 'secretStuff.php');


global $oauth_db_version;
$oauth_db_version = '1.0';

// create the table in da database
function create_oauth_table () {
  global $wpdb;
  global $oauth_db_version;

  // set the charset collate
  $charset_collate = $wpdb->get_charset_collate();
  // grab the db prefix, add the table name
  $table_name = $wpdb->prefix . "infusionPress";
  // make that table
  $sql = "CREATE TABLE $table_name (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    token text NOT NULL,
    expiration int(15) NOT NULL,
    UNIQUE KEY id (id)
  ) $charset_collate;";

  require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
  dbDelta( $sql );

  add_option( 'oauth_db_version', $ouath_db_version );
}

// add initial data to set everything up right
function add_dummyData(){
  global $wpdb;

  // dummy data
  $serialToken = '0';
  $endOfLife = '1';

  // table name, with prefix
  $table_name = $wpdb->prefix . 'infusionPress';

  // put some initial data in there
  $wpdb->insert(
  	$table_name,
  	array(
  		'token' => $serialToken,
      'expiration' => $endOfLife
  	)
  );
}

// initialize and add everything
add_action( 'init', 'create_isFormsCPT' );
add_action( 'init', 'isForms_taxonomy' );
add_action( 'add_meta_boxes', 'infusionsoft_forms_add_meta_box' );
add_action('admin_menu', 'isForms_register_options_page');
add_action('save_post', 'infusionsoft_forms_save');
add_action( 'admin_footer', 'ajax_formHtml' );
add_shortcode('infusionpress-form', 'infusionpress_shortcode');
add_action( 'wp_ajax_grab_form_html', 'grab_form_html_callback' );
add_action( 'wp_enqueue_scripts', 'infusionpress_scripts' );

// isForms CPT
function create_isFormsCPT() {
  register_post_type( 'isForm',
    array(
        'labels' => array(
          'name' => __( 'IS Form' ),
          'singular_name' => __( 'IS Form' )
        ),
        'supports' => array( 'title', 'revisions' ),
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-welcome-write-blog',
    )
  );
} // end create_isFormsCPT

// isForms taxonomy
function isForms_taxonomy() {
  register_taxonomy(
      'forms',
      'isForm_post',
      array(
        'label' => __( 'Form Categories' ),
        'rewrite' => array( 'slug' => 'form' ),
      )
  );
} // end isForms_taxonomy

// add it to the menu
function isForms_register_options_page() {
  add_options_page('InfusionPress Settings', 'InfusionPress Settings', 'manage_options', 'isForms', 'isForms_settings_page');
}

function infusionsoft_forms_add_meta_box() {
	add_meta_box(
		'infusionsoft_forms-infusionsoft-forms',
		__( 'Infusionsoft Forms', 'infusionsoft_forms' ),
		'infusionsoft_forms_html',
		'isForm',
		'normal',
		'high'
	);
}

function infusionsoft_forms_get_meta( $value ) {
	global $post;

	$field = get_post_meta( $post->ID, $value, true );
	if ( ! empty( $field ) ) {
		return is_array( $field ) ? stripslashes_deep( $field ) : stripslashes( wp_kses_decode_entities( $field ) );
	} else {
		return false;
	}
}

function infusionsoft_forms_html( $post) {
	wp_nonce_field( '_infusionsoft_forms_nonce', 'infusionsoft_forms_nonce' ); ?>

	<p>Your Infusionsoft Forms</p>
  <?php
  retrieve_token();
  global $tokenObject;
  global $tokenExpiration;
  global $newToken;
  global $infusionsoft;
  global $unserializedIsToken;
  global $tokenID;
  // set the token with the unserialized token object
  $infusionsoft->setToken($unserializedIsToken);

  // check the token
  $goodToGo = check_token_expiration($tokenExpiration, $unserializedIsToken, $infusionsoft, $tokenID);
  // if everything is kosher, lets build a dropdown
  if($goodToGo){
    get_those_ids();
    global $formIDS;
    ?>
    <script>
      jQuery(document).ready(function(){
        // get the value of the input and change the preview color
        var newBgColor = jQuery('#bgColor-picker').val();
        var newFontColor = jQuery('#fontColor-picker').val();
        jQuery('.colorPickers').on('click', '#bgColor-picker',function(){
          var newBgColor = jQuery(this).val();
        });
        jQuery('.colorPickers').on('click', '#fontColor-picker',function(){
          var newFontColor = jQuery(this).val();
        });

        // start up the color picker on the elements
        jQuery('#bgColor-picker').iris({
          change: function(event, ui){
            // change the preview color
            jQuery('.bgColor-pickerPreview').css( 'background-color', ui.color.toString());
          }
        });
        jQuery('#fontColor-picker').iris({
          change: function(event, ui){
            // change the preview color
            jQuery('.fontColor-pickerPreview').css( 'background-color', ui.color.toString());
          }
        });
      });
    </script>
  	<p>
  		<label for="infusionsoft_forms_which_form_would_you_like_to_use_"><?php _e( 'Which form would you like to use?', 'infusionsoft_forms' ); ?></label><br>
  		<select name="infusionsoft_forms_which_form_would_you_like_to_use_" id="infusionsoft_forms_which_form_would_you_like_to_use_">
        <?php
        // loop through all the forms we retrieve from the IS API
        foreach($formIDS as $formID => $formName){
          // if this option was previously selected, mark it as such
          if (infusionsoft_forms_get_meta( "infusionsoft_forms_which_form_would_you_like_to_use_" ) === "$formID" ){
            echo '<option selected value="'. $formID .'">'. $formName .'</option>';
          } else { // or else just echo them out
            echo '<option value="'. $formID .'">'. $formName .'</option>';
          }
        }
        ?>
  		</select>
  	</p>
    <p>
  		<label for="infusionsoft_forms_form_html"><?php _e( 'Form HTML', 'infusionsoft_forms' ); ?></label><br>
      <?php
			$content = infusionsoft_forms_get_meta( 'infusionsoft_forms_form_html' );
			$editor_id = 'infusionsoft_forms_form_html';
			$settings = array(
				'teeny' => true,
				'media_buttons' => false,
				'tinymce' => false,
			);

			wp_editor( html_entity_decode($content), $editor_id, $settings );
			?>
  	</p>
    <p>
      <div class="colorPickers">
        <label for="infusionsoft_forms_thanks_bgColor"><?php _e( 'Thank You Message BG Color (default is black)', 'infusionsoft_forms' ); ?></label><br>
        <input type="text" id="bgColor-picker" value="<?php echo infusionsoft_forms_get_meta('infusionsoft_forms_thanks_bgColor'); ?>" name="infusionsoft_forms_thanks_bgColor"><br>
        <div class="bgColor-pickerPreview" style="display:inline-block; margin: 2% 0; 2%;width:30px;height:30px;background-color:<?php echo infusionsoft_forms_get_meta('infusionsoft_forms_thanks_bgColor'); ?>"></div><br>
        <label for="infusionsoft_forms_thanks_fontColor"><?php _e( 'Thank You Message Font Color (default is white)', 'infusionsoft_forms' ); ?></label><br>
        <input type="text" id="fontColor-picker" value="<?php echo infusionsoft_forms_get_meta('infusionsoft_forms_thanks_fontColor'); ?>" name="infusionsoft_forms_thanks_fontColor"><br>
        <div class="fontColor-pickerPreview" style="display:inline-block; margin: 2% 0; 2%;width:30px;height:30px;background-color:<?php echo infusionsoft_forms_get_meta('infusionsoft_forms_thanks_fontColor'); ?>"></div>
      </div><!--.colorPickers-->
    </p>
    <p>
  		<label for="infusionsoft_forms_thanks_message"><?php _e( 'Thank You Message', 'infusionsoft_forms' ); ?></label><br>
      <?php
			$content = infusionsoft_forms_get_meta( 'infusionsoft_forms_thanks_message' );
			$editor_id = 'infusionsoft_forms_thanks_message';
			$settings = array(
				'teeny' => false,
				'media_buttons' => true,
				'tinymce' => true,
			);

			wp_editor( html_entity_decode($content), $editor_id, $settings );
			?>
  	</p>
    <p>
      <label for="infusionpress_shortcode"><?php _e( 'Infusionpress Shortcode', 'infusionsoft_forms' ); ?></label>
      <p>This is the shortcode you will use for this form. Simply copy/paste into any page/post and you're good to go!</p>
      <input type="text" name="infusionpress_shortcode" value="<?php echo '[infusionpress-form id=\''. $post->ID .'\']'; ?>">
    </p><?php
  }
}

function infusionsoft_forms_save( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! isset( $_POST['infusionsoft_forms_nonce'] ) || ! wp_verify_nonce( $_POST['infusionsoft_forms_nonce'], '_infusionsoft_forms_nonce' ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	if ( isset( $_POST['infusionsoft_forms_which_form_would_you_like_to_use_'] ) )
		update_post_meta( $post_id, 'infusionsoft_forms_which_form_would_you_like_to_use_', esc_attr( $_POST['infusionsoft_forms_which_form_would_you_like_to_use_'] ) );
	if ( isset( $_POST['infusionsoft_forms_form_html'] ) )
		update_post_meta( $post_id, 'infusionsoft_forms_form_html',  $_POST['infusionsoft_forms_form_html'] ) ;
  if ( isset( $_POST['infusionsoft_forms_thanks_message'] ) )
		update_post_meta( $post_id, 'infusionsoft_forms_thanks_message',  $_POST['infusionsoft_forms_thanks_message'] ) ;
  if ( isset( $_POST['infusionsoft_forms_thanks_bgColor'] ) )
    update_post_meta( $post_id, 'infusionsoft_forms_thanks_bgColor', esc_attr( $_POST['infusionsoft_forms_thanks_bgColor'] ) );
  if ( isset( $_POST['infusionsoft_forms_thanks_fontColor'] ) )
    update_post_meta( $post_id, 'infusionsoft_forms_thanks_fontColor', esc_attr( $_POST['infusionsoft_forms_thanks_fontColor'] ) );
}

// settings page
function isForms_settings_page(){
  // if they shouldnt be here, make them leave
  if ( !current_user_can( 'manage_options' ) )  {
    wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
  }
  if ( $_SERVER["REQUEST_METHOD"] == "POST" ){
  }
  ?>
    <div>
    <?php screen_icon(); ?>
    <h2>IS to WP Form Settings</h2>

    <?php
    global $infusionsoft;
    retrieve_token();
    global $newToken;

    if ($newToken) {
        echo '<p>You are authenticated. Get to work!</p>';
    } else {
        echo '<a href="' . $infusionsoft->getAuthorizationUrl() . '">Click here to authorize</a>';
    } // end oAuth IS STUFF
    ?>
    </div>
  <?php
}

// AJAX function to make a call to grab the desired form HTML
function ajax_formHtml() {
  echo '
	<script type="text/javascript" >
	jQuery(document).ready(function($) {
    jQuery("#infusionsoft_forms_which_form_would_you_like_to_use_").change(function(){
      var desiredFormID = jQuery(this).attr("value");
      var data = {
        "action": "grab_form_html",
        "formID": desiredFormID
      };

  		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
  		jQuery.post(ajaxurl, data, function(response) {
  			jQuery("#infusionsoft_forms_form_html").val(response);
  		});
    })
	});
	</script>';
}

// AJAX callback handler that makes the call to grab the desired form HTML
function grab_form_html_callback() {
	global $wpdb; // this is how you get access to the database
  global $infusionsoft;
  global $unserializedIsToken;

  // make sure the token is good or refresh it if not
  retrieve_token();

  // set the token with the unserialized token object
  $infusionsoft->setToken($unserializedIsToken);

  // ID of requested form
	$desiredFormID = intval( $_POST['formID'] );

  // call up that HTML
  $desiredFormHTML = $infusionsoft->webForms()->getHTML($desiredFormID);

  // find just the form, because Infusinsoft returns some silly stuff
  $regex = '/<form (.*?)>(.*?)<\/form>/is';

  preg_match_all($regex, $desiredFormHTML, $match);

  // set the plain ol chosen form as a var
  $plainChosenForm = $match[0][0];

  echo $plainChosenForm;

	wp_die(); // this is required to terminate immediately and return a proper response
}

// shortcode for IS form
function infusionpress_shortcode($atts){
   $formCode = '<div class="infusionPressForm">'.get_post_meta($atts['id'], 'infusionsoft_forms_form_html', true).'</div><!--.infusionPressForm--><div class="infusionPressTY" style="display:none; background-color:'.get_post_meta($atts['id'], 'infusionsoft_forms_thanks_bgColor', true).';"><div class="tyHolder" style="color: '.get_post_meta($atts['id'], 'infusionsoft_forms_thanks_fontColor', true).';"><span class="closeBtn"><p></p></span>'.get_post_meta($atts['id'], 'infusionsoft_forms_thanks_message', true).'</div><!--.tyHolder--></div><!--.infusionPressTY-->';
   return $formCode;
}

// enqueue the JS/styles on the frontside
function infusionpress_scripts() {
  wp_enqueue_script( 'jquery' );
  wp_enqueue_script( 'jQuery-validate', 'http://ajax.aspnetcdn.com/ajax/jquery.validate/1.14.0/jquery.validate.min.js', array( 'jquery' ), '1.14.0', true);
  wp_enqueue_script( 'validate-additional-methods', 'http://ajax.aspnetcdn.com/ajax/jquery.validate/1.14.0/additional-methods.min.js', array( 'jquery' ), '1.14.0', true);
  wp_enqueue_script( 'jquery-ui-datepicker' );
  wp_enqueue_script( 'inf-press-functions', plugin_dir_url( __FILE__ ) . 'js/infPressFunctions.js', array( 'jquery' ), '1.0.0', true );
  // Localize the script with new data
  $wpBaseURL = plugin_dir_url( __FILE__ );
  wp_localize_script( 'inf-press-functions', 'wpBaseURL', $wpBaseURL );
  wp_enqueue_style( 'infusionPressForms', plugin_dir_url(__FILE__) . 'style/style.css', array(), '1.0.0', 'all'  );
}

// wp iris color picker stuff
function infusionPressColorPicker(){
    wp_enqueue_script( 'iris' );
}
add_action('admin_enqueue_scripts','infusionPressColorPicker');

// call the db functions
register_activation_hook( __FILE__, 'create_oauth_table' );
register_activation_hook( __FILE__, 'add_dummyData');
