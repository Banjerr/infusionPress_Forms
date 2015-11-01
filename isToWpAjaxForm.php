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
$redirectUrl = get_site_url() . '/wp-content/plugins/ajaxIsForm/auth.php';

/*
 * require secret stuff
 */
require_once(  plugin_dir_path( __FILE__ ) . 'secretStuff.php');


global $oauth_db_version;
$oauth_db_version = '1.0';

// create the database
function create_oauth_table () {
  global $wpdb;
  global $oauth_db_version;

  // set the charset collate
  $charset_collate = $wpdb->get_charset_collate();
  // grab the db prefix, add the table name
  $table_name = $wpdb->prefix . "isAjaxForm";
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

// initialize and add everything
add_action( 'init', 'create_isFormsCPT' );
add_action( 'init', 'isForms_taxonomy' );
add_action( 'add_meta_boxes', 'infusionsoft_forms_add_meta_box' );
add_action('admin_menu', 'isForms_register_options_page');
add_action('save_post', 'infusionsoft_forms_save');


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
  add_options_page('IS to WP Form Settings', 'IS2WP Settings', 'manage_options', 'isForms', 'isForms_settings_page');
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
  // set the token with the unserialized token object
  $infusionsoft->setToken($unserializedIsToken);

  // check the token
  $goodToGo = check_token_expiration($tokenExpiration, $unserializedIsToken, $infusionsoft);
  // if everything is kosher, lets build a dropdown
  if($goodToGo){
      get_those_ids();
      global $formIDS;
    ?>
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
  			<option <?php echo (infusionsoft_forms_get_meta( 'infusionsoft_forms_which_form_would_you_like_to_use_' ) === '1' ) ? 'selected' : '' ?>>1</option>
  			<option <?php echo (infusionsoft_forms_get_meta( 'infusionsoft_forms_which_form_would_you_like_to_use_' ) === '2' ) ? 'selected' : '' ?>>2</option>
  			<option <?php echo (infusionsoft_forms_get_meta( 'infusionsoft_forms_which_form_would_you_like_to_use_' ) === '3' ) ? 'selected' : '' ?>>3</option>
  		</select>
  	</p>	<p>
  		<label for="infusionsoft_forms_form_html"><?php _e( 'Form HTML', 'infusionsoft_forms' ); ?></label><br>
  		<textarea name="infusionsoft_forms_form_html" id="infusionsoft_forms_form_html" ><?php echo infusionsoft_forms_get_meta( 'infusionsoft_forms_form_html' ); ?></textarea>

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
		update_post_meta( $post_id, 'infusionsoft_forms_form_html', esc_attr( $_POST['infusionsoft_forms_form_html'] ) );
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

    //if ($newToken) {
        echo '<p>You are authenticated. Get to work!</p>';
    //} else {
        echo '<a href="' . $infusionsoft->getAuthorizationUrl() . '">Click here to authorize</a>';
    //} // end oAuth IS STUFF
    ?>
    </div>
  <?php
}

/*
	Usage: infusionsoft_forms_get_meta( 'infusionsoft_forms_which_form_would_you_like_to_use_' )
*/

// call the db functions
register_activation_hook( __FILE__, 'create_oauth_table' );
