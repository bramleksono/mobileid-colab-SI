<?php

$app->post('/user/reg', function () use($app) {
	//echo "This is user registration section";	
	
	$body = json_decode($app->request()->getBody());
	
	//initialize error
	$error = 2;
	
	if (checkregrequest($body)) {
		//check purpose
		$purpose = $body->meta->purpose;
		
		switch ($purpose) {
		case "userreg":
			//echo "You want user reg";
			$pin = $body->userinfo->pin;
			$nik = $body->userinfo->nik;
			
			$filename = $nik.".pem";
			//construct key
			$pphrase = getkey($pin);
			
			//get keypair
			$keypair = getrsakeypair($pphrase);
			//var_dump($keypair);
			
			//save private key in SI
			file_put_contents($filename, $keypair[0]);
			
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
	if (!isset($data->meta->purpose))
		return false;
	if (!isset($data->userinfo->pin))
		return false;
	if (!isset($data->userinfo->nik))
		return false;
	return true;		
}
