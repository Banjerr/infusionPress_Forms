<?php
// function to retrieve information from db
function retrieve_token(){
  global $oauth_db_version;
  global $infusionsoft;
  global $tokenExpiration;
  global $unserializedIsToken;
  global $tokenObject;
  global $newToken;
  global $wpdb;

  // grab the db prefix, add the table name
  $table_name = $wpdb->prefix . "isAjaxForm";

  // sql statement
  $sql="SELECT expiration, token FROM `$table_name`";

  // run the query, set it to variables
  $tokenStuff = $wpdb->get_results($sql);

  // expiration
  $tokenExpiration = $tokenStuff[0]->expiration;
  // serilzed token
  $tokenObject = $tokenStuff[0]->token;

  // unserlized token
  $unserializedIsToken = unserialize($tokenObject);
  $newToken = $unserializedIsToken->accessToken;
}

// check expiration
function check_token_expiration($tokenExpiration, $unserializedIsToken, $infusionsoft){
  // get current time, subtract 1 hour
  $needsNewToken = time() - 60 * 60 * 1000 ;

  // if we need a new token, refresh it
  if($needsNewToken > $tokenExpiration){
    echo 'you need a new friggin token <br />';
    // refresh it
    //$infusionsoft->setToken($unserializedIsToken->accessToken);
    //var_dump($infusionsoft);
    $infusionsoft->refreshAccessToken();
    // update the db

    // return true
    return true;
  } else {
    return true;
  }
}

// grab the web form ids from IS
function get_those_ids(){
  global $infusionsoft;
  var_dump($infusionsoft);
  // get the form IDS
  $formIDS = $infusionsoft->webForms()->getMap();

  // make the dropdown
  echo '<select name="infusionsoft_forms_which_form_would_you_like_to_use_" id="infusionsoft_forms_which_form_would_you_like_to_use_">';
  foreach($formIDS as $formID => $formName){
    echo '<option value="'. $formID .'">'. $formName .'</option>';
  }
  echo '</select>';
}
