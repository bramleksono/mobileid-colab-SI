<?php
//Aplikasi Mobile ID - SI untuk kolaborasi internet.

require 'vendor/autoload.php';

//slim init
$app = new \Slim\Slim();

class ResourceNotFoundException extends Exception {}

//Config
$configfile = 'config.json';
$addressfile = 'config/address.json';

//Parse Backend
use Parse\ParseObject;
use Parse\ParseClient;
use Parse\ParseQuery;
$app_id = "";
$rest_key = "";
$master_key= "";
ParseClient::initialize( $app_id, $rest_key, $master_key );
$si_userdb_obj = new ParseObject("si_userdb");
$si_userdb_que = new ParseQuery("si_userdb");

//Lib
require 'lib/crypt.php';  // Handling cryptographic function
require 'lib/addstruct.php';  // Construct client address
require 'lib/sending.php';  // Handling sending http request function

//Routes
require 'routes/user.php';

//Time
date_default_timezone_set("Asia/Jakarta"); 

$app->run();
