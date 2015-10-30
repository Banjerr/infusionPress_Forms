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

	$table_name = $wpdb->prefix . 'isAjaxForm';

	$wpdb->insert(
		$table_name,
		array(
			'token' => $serialToken,
      'expiration' => $endOfLife
		)
	);
}

// write the oauth token object and endOfLife to db
oauth_install_data();

// redirect to the settings page
header('Location:' . get_site_url() . '/wp-admin/options-general.php?page=isForms');
