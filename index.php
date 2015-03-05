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
$app_id = "OVPsA58Uck3NCqpnrW7KTZJtThk8bIZJ11aLxlI6";
$rest_key = "wP9kY83dL9X8JwzeLehDfz6Rv2FNSz64dTcrdOum";
$master_key= "ebTqQ5LbSHU9yxl2rXx9nUL0cdFtNmaevAcmz5BX";
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
