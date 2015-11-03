<?php
// function to retrieve information from db
function retrieve_token(){
  global $oauth_db_version;
  global $infusionsoft;
  global $tokenExpiration;
  global $unserializedIsToken;
  global $tokenObject;
  global $newToken;
  global $tokenID;
  global $wpdb;

  // grab the db prefix, add the table name
  $table_name = $wpdb->prefix . "isAjaxForm";

  // sql statement
  $sql="SELECT expiration, token, id FROM `$table_name`";

  // run the query, set it to variables
  $tokenStuff = $wpdb->get_results($sql);

  // expiration
  $tokenExpiration = $tokenStuff[0]->expiration;
  // serilzed token
  $tokenObject = $tokenStuff[0]->token;
  // id
  $tokenID = $tokenStuff[0]->id;

  // unserlized token
  $unserializedIsToken = unserialize($tokenObject);
  $newToken = $unserializedIsToken->accessToken;
}

// check expiration
function check_token_expiration($tokenExpiration, $unserializedIsToken, $infusionsoft, $tokenID){
  global $wpdb;

  // grab the db prefix, add the table name
  $table_name = $wpdb->prefix . "isAjaxForm";

  // get current time, subtract 10 minutes
  $needsNewToken = time() - 600 ;

  // if we need a new token, refresh it
  if($needsNewToken > $tokenExpiration){
    echo 'you need a new friggin token <br />';
    // refresh it
    $newISToken = $infusionsoft->refreshAccessToken();

    // get end of life
    $newEndOfLife = $newISToken->endOfLife;

    // serialize object for db storage
    $newSerialToken = serialize($newISToken);

    // update the db
    $wpdb->update(
  		$table_name,
  		array(
  			'token' => $serialToken,
        'expiration' => $newEndOfLife
  		),
      "WHERE id = $tokenID"
  	);

    // return true
    return true;
  } else {
    return true;
  }
}

// grab the web form ids from IS
function get_those_ids(){
  global $infusionsoft;
  global $formIDS;

  // get the form IDS
  $formIDS = $infusionsoft->webForms()->getMap();
}
