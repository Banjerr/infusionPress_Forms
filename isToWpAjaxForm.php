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
 * get the site url for the redirect
 */
$redirectUrl = get_site_url() . '/wp-content/plugins/ajaxIsForm/auth.php';

$infusionsoft = new \Infusionsoft\Infusionsoft(array(
    'clientId'     => 'bdgdbfsy6d5bk9d6h8q2aszs',
    'clientSecret' => 'hSacUV7z5j',
    'redirectUri'  =>  $redirectUrl
));

global $oauth_db_version;
$oauth_db_version = '1.0';

// create the database
function create_oauth_table () {
  global $wpdb;
  global $oauth_db_version;

  $charset_collate = $wpdb->get_charset_collate();

  $table_name = $wpdb->prefix . "isAjaxForm";

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
add_action('admin_menu', 'isForms_register_options_page');
add_action( 'add_meta_boxes', 'infusionsoft_forms_add_meta_box' );

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
		'advanced',
		'high'
	);
}

function infusionsoft_forms_html( $post) {
	wp_nonce_field( '_infusionsoft_forms_nonce', 'infusionsoft_forms_nonce' ); ?>

	<p>Pick which IS form youd like to use</p>

	<p>
		<label for="infusionsoft_forms_which_form_would_you_like_to_use_"><?php _e( 'Which form would you like to use?', 'infusionsoft_forms' ); ?></label><br>
		<select name="infusionsoft_forms_which_form_would_you_like_to_use_" id="infusionsoft_forms_which_form_would_you_like_to_use_">
			<option>Form</option>
		</select>
	</p><?php
}

function infusionsoft_forms_save( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! isset( $_POST['infusionsoft_forms_nonce'] ) || ! wp_verify_nonce( $_POST['infusionsoft_forms_nonce'], '_infusionsoft_forms_nonce' ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	if ( isset( $_POST['infusionsoft_forms_which_form_would_you_like_to_use_'] ) )
		update_post_meta( $post_id, 'infusionsoft_forms_which_form_would_you_like_to_use_', esc_attr( $_POST['infusionsoft_forms_which_form_would_you_like_to_use_'] ) );
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

    if ($infusionsoft->getToken()) {
        // Save the serialized token to the current session for subsequent requests
        $_SESSION['token'] = serialize($infusionsoft->getToken());
    } else {
        echo '<a href="' . $infusionsoft->getAuthorizationUrl() . '">Click here to authorize</a>';
    } // end oAuth IS STUFF
    ?>
    </div>
  <?php
}

/*
	Usage: infusionsoft_forms_get_meta( 'infusionsoft_forms_which_form_would_you_like_to_use_' )
*/

// call the db functions
register_activation_hook( __FILE__, 'create_oauth_table' );
