<?php
//Aplikasi Mobile ID - SI untuk kolaborasi internet.

require 'vendor/autoload.php';
//require 'lib/redbean/rb.php';

//slim init
$app = new \Slim\Slim();

//Config
$configfile = 'config.json';
$addressfile = 'config/address.json';

//Lib
require 'lib/crypt.php';  // Handling cryptographic function
require 'lib/addstruct.php';  // Construct client address
require 'lib/sending.php';  // Handling sending http request function

//Routes
require 'routes/user.php';

//Time
date_default_timezone_set("Asia/Jakarta"); 

$app->run();
