<?php

//Parse Backend
use Parse\ParseObject;
use Parse\ParseQuery;

$si_pid_obj = new ParseObject("si_pid");
$si_pid_que = new ParseQuery("si_pid");

$app->post('/login', function () use ($app,$si_pid_obj) {
    //example query : {"message":"Login request for Mobile ID website","deviceid":"APA91bH04os8rdZEIxOu7_LxQEnGnnYeJbzZEMbsLUgQ96Z46fjaBBBMyzv0o5HaNYF4O5zCAc02_BHSn3UfrZkYIc6GtJb3lMqOD7XVAE2WCqAe6b5-f8Q1GV9yv54zKaJ8se-wADgmPJiImSgUHoSq1sLS143me7NFxXiv3XXvxHjedpdkbgY","callback":"http://postcatcher.in/catchers/54f7074cc895880300002ba1","userinfo":{"berlaku":"123","kewarganegaraan":"123","pekerjaan":"123","statperkawinan":"123","agama":"123","kecamatan":"123","keldesa":"123","rtrw":"123","alamat":"123","goldarah":"123","jeniskelamin":"123","ttl":"Bandung/123123123","nama":"Bramanto Leksono","nik":"1231230509890001"}}
    global $SIloginconfirm;
    $error=2;
    
    $body = json_decode($app->request()->getBody());
    $message = $body->message;
    $deviceid = $body->deviceid;
	$idnumber = $body->userinfo->nik;
	$callback = $body->callback;
	
	$current_date = new DateTime("now");
	$time = $current_date->format('Y-m-d H:i:s');
	$key=getkey($time);
	
	//get PID from time and nik
	$PID = getPID($time,$idnumber);
	
	//get OTP
    $OTP   = getOTP();
	
	//construct form
	$form =  (object) array("userinfo" => $body->userinfo, "OTP" => $OTP, "callback" => $callback);

	//encrypt user info
	$result = encryptdb(json_encode($form),$key);
	
	$data = $result[0];
	$iv = $result[1];
	
	$si_pid_obj->set("pid", $PID);
	$si_pid_obj->set("data", utf8_encode($data));
	$si_pid_obj->set("created", $time);
	$si_pid_obj->set("iv", utf8_encode($iv));
	
	//save request to database
	try {
		$si_pid_obj->save();
		//retrieve registration code
		$regcode = $si_pid_obj->getObjectId();
		//echo 'New object created with objectId: ' . $regcode;
		$error=0;
	} catch (ParseException $ex) {
		// Execute any logic that should take place if the save fails.
		// error is a ParseException object with an error code and message.
		// echo 'Failed to create new object, with error message: ' + $ex->getMessage();
		$error=1;
	}
	
	//send request to GCM
	$gcpm = new GCMPushMessage($deviceid);
    $gcpm->fillDataLogin($message, $PID, $OTP, $SIloginconfirm);
    $response = $gcpm->sendGoogleCloudMessage();
    $response = json_decode($response);
	if ($response->success) {
		$error = 0;
	} else {
		$error = 2;
	}
	
	//output request code to device and CA
    switch ($error) {
	case 0:
		echo json_encode(array(	'success' => true,
					'PID' => $PID
		));
		break;
	case 1:
		echo json_encode(array(	'success' => false,
					'reason' => "Cannot contacting database"
		));
		break;
	default:
		echo json_encode(array(	'success' => false,
					'reason' => "Cannot send GCM"
		));
		break;
	}
});

$app->post('/login/confirm', function () use ($app,$si_pid_que) {
	//example query : {"PID":"625ae82c6b5502a08195389c93be6263f1c65185","HMAC":"afbf1cd5f1778a9961f9adbe67797bfeb3a3287489caacc4523e407e4dcc1ae6"}
	global $CAloginconfirm;
	
	$body = json_decode($app->request()->getBody());
	$PID = $body->PID;
    $postedHMAC = $body->HMAC;
    
    $error = 2;
    
    //check if database exist
	$si_pid_que->equalTo("pid", $PID);
	$results = $si_pid_que->find();
	
	//process database result
	if (empty($results[0])) {
		echo "No result";
        $error=1;
	} else {
		//echo "PID ".$PID." found.";
		$data = utf8_decode($results[0]->get('data'));
		$iv =  utf8_decode($results[0]->get('iv'));
		$key=getkey($results[0]->get('created'));
		
		//decode and show userinfo
		$form = json_decode(decryptdb($data,$iv,$key));
		$userinfo = json_encode($form->userinfo);
		$OTP = $form->OTP;
		$callback = $form->callback;
		
		$userinfo = preg_replace('/\s+/', '', $userinfo);
		$signature = hitunghashdata($userinfo);
		$hmacresult = proseshmac($signature,$OTP,$postedHMAC);
		if ($hmacresult) {
			//send callback
			echo "HMAC correct";
			$form =  (object) array("PID" => $PID, "callback" => $callback);
			$form = json_encode($form);
			sendjson($form,$CAloginconfirm);
		} else {
			echo "HMAC failed";
		}
	}
});