<?php

if($_POST){
  // get the stuff that was posted
  $formInputs = $_POST;
  $formAction = $_POST['formAction'];

  // set up the input array for the cURL
  $inputStuff = array();

  // assign stuff as key value pairs
  foreach($formInputs as $inputName => $inputValue){
    $inputStuff[$inputName] = $inputValue;
  }

  // urlencode the stuff for cURL
  foreach ($inputStuff as $key => $value){
    $inputStuffString .= urlencode($key) . "=" . urlencode($value) . "&";
  }

  // open the cURL
  $ch = curl_init();

  // set the cURL options
  curl_setopt($ch, CURLOPT_URL, $formAction);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $inputStuffString);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_REFERER, $_SERVER['HTTP_REFERER']);

  // run the cURL
  $curlResponse = curl_exec($ch);

  // close connection
  curl_close( $ch );

  // $test = json_encode($inputStuffString);
  // echo $test;
  // exit;
}

?>
