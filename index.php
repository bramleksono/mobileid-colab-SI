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
use Parse\ParseClient;
$app_id = "";
$rest_key = "";
$master_key= "";
ParseClient::initialize( $app_id, $rest_key, $master_key );

//Lib
require 'lib/SIcontroller.class.php'; // Handling User Class

//Routes
require 'routes/mid-user.php';
require 'routes/mid-message.php';
require 'routes/mid-login.php';

//Time
date_default_timezone_set("Asia/Jakarta"); 

$app->run();
