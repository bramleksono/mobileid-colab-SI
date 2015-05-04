<?php
//Aplikasi Mobile ID - SI untuk kolaborasi internet.

require 'vendor/autoload.php';

//slim init
$app = new \Slim\Slim();

class ResourceNotFoundException extends Exception {}

//Config
$configfile = 'config.json';
$addressfile = 'config/address.json';

//Lib
require 'lib/SIcontroller.class.php'; // Handling User Class
require 'config/parse.php';  // Initialize parse database

//Routes
require 'routes/mid-user.php';
require 'routes/mid-message.php';
require 'routes/mid-login.php';
require 'routes/mid-verify.php';
require 'routes/mid-document.php';

//Time
date_default_timezone_set("Asia/Jakarta"); 

$app->run();
