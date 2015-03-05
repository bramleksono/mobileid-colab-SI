<?php
//Routes for mobileid-message function

$app->post('/message/', function () use ($app) {
	$body = json_decode($app->request()->getBody());
	$deviceid = $body->userinfo->deviceid;
	$message = $body->userinfo->message;
	
    $gcpm = new GCMPushMessage($deviceid);
    $gcpm->notification($message);
    $response = $gcpm->sendGoogleCloudMessage();
    $response = json_decode($response);
    if ($response->success) {
        echo json_encode(array(	'success' => true
		));
    }
    else {
        echo json_encode(array(	'success' => false,
                                'reason' => "GCM Failed"
		));
    }
    //var_dump($response);
});
