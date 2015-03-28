<?php
use Parse\ParseObject;
use Parse\ParseQuery;
$si_userdb_obj = new ParseObject("si_userdb");
$si_userdb_que = new ParseQuery("si_userdb");

$app->post('/user/reg', function () use($app,$si_userdb_obj,$si_userdb_que) {
	//echo "This is user registration section";	
	
	$body = json_decode($app->request()->getBody(), true);
	
	//initialize error
	$error = 2;
	
	if (checkregrequest($body)) {
		//check purpose
		$purpose = $body["meta"]["purpose"];
		
		switch ($purpose) {
		case "userreg":
			//echo "You want user reg";
			$pin = $body["userinfo"]["pin"];
			$nik = $body["userinfo"]["nik"];
			//construct key
			$pphrase = getkey($pin);
			
			//get keypair
			$keypair = getrsakeypair($pphrase);
			//var_dump($keypair);
			//delete existing nik
			$si_userdb_que->equalTo("nik", $nik);
			$results = $si_userdb_que->find();
			//echo "Successfully retrieved " . count($results) . " scores.";
			// Do something with the returned ParseObject values
			for ($i = 0; $i < count($results); $i++) {
			  	$object = $results[$i];
			  	//echo "Object ".$object->getObjectId()." deleted.";
			  	$object->destroy();
			}
			
			//save private key in database
			//use time as key
			$current_date = new DateTime("now");
			$time = $current_date->format('Y-m-d H:i:s');
			$key=getkey($time);
			//encrypt private key
			$result = encryptdb($keypair[0],$key);
			//table: nik| privkey| iv| created
			$si_userdb_obj->set("nik", $nik);
			$si_userdb_obj->set("privkey", utf8_encode($result[0]));
			$si_userdb_obj->set("iv", utf8_encode($result[1]));
			$si_userdb_obj->set("created", $time);
			try {
				$si_userdb_obj->save();
				//retrieve registration code
				//$regcode = $si_userdb_obj->getObjectId();
				//echo 'New object created with objectId: ' . $regcode;
			} catch (ParseException $ex) {
				// Execute any logic that should take place if the save fails.
				// error is a ParseException object with an error code and message.
				// echo 'Failed to create new object, with error message: ' + $ex->getMessage();
			}
			//no error, continue to response
			$error=0;
			break;
		default:
			$error=1;
			break;
		}
	}
	else 
		$error=2;
	
	//construct response to CA
	header('Content-Type: application/json');
	switch ($error) {
	case 0:
		//send pubkey 
		echo json_encode(array(	'success' => true,
								'pubkey' => $keypair[1]
		));
		break;
	case 1:
		//send pubkey 
		echo json_encode(array(	'success' => false,
								'reason' => "Invalid purpose"
		));
		break;		
	default:
		//send pubkey 
		echo json_encode(array(	'success' => false,
								'reason' => "Request not complete"
		));
		break;
	}
	
});

function checkregrequest($data) {
	if (!isset($data["meta"]["purpose"]))
		return false;
	if (!isset($data["userinfo"]["pin"]))
		return false;
	if (!isset($data["userinfo"]["nik"]))
		return false;
	return true;		
}