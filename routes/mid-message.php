<?php
//Routes for mobileid-message function

$app->post('/message/', function () use ($app) {
	$body = json_decode($app->request()->getBody());
	
	$sendmessage = new SIcontroller();
	$error = $sendmessage->messagereq($body);
	
    //construct response
	header('Content-Type: application/json');
	echo $sendmessage->messagereqoutput($error);
});