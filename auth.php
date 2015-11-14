<?php
// require wp load
require_once('../../../wp-load.php');

// save to db
function oauth_install_data() {
  global $infusionsoft;
	global $wpdb;

  // get the code
  $isCode = $_GET['code'];

  // get the token
  $isToken = $infusionsoft->requestAccessToken($isCode);

  // get end of life
  $endOfLife = $isToken->endOfLife;

  // serialize object for db storage
  $serialToken = serialize($isToken);

  // table name, with prefix
	$table_name = $wpdb->prefix . 'infusionPress';

  // update the db
  $wpdb->query( $wpdb->prepare("
    UPDATE $table_name
    SET token = %s,
        expiration = %s
    WHERE id = 1;
    ",
    $serialToken, $endOfLife
  ));
}

// write the oauth token object and endOfLife to db
oauth_install_data();

// redirect to the settings page
header('Location:' . get_site_url() . '/wp-admin/options-general.php?page=isForms');
