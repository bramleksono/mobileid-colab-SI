<?php
/*
	Class to send push notifications using Google Cloud Messaging for Android

	Example usage
	-----------------------
	$an = new GCMPushMessage($apiKey);
	$an->setDevices($devices);
	$response = $an->send($message);
	-----------------------
	
	$apiKey Your GCM api key
	$devices An array or string of registered device tokens
	$message The mesasge you want to push out

	@author Matt Grundy

	Adapted from the code available at:
	http://stackoverflow.com/questions/11242743/gcm-with-php-google-cloud-messaging

*/
class GCMPushMessage {
	function GCMPushMessage($id){
		$this->ids = array($id);
	}
	
	function notification($message){
		$json_message = '{"info":"'."notification".
						'","content":"'.$message.
						'"}';
		$this->data = array('message' => $json_message);
	}

	function fillDataIDverify($message, $AppID, $PID, $OTP, $SIcallbackaddr){
		$json_message = '{"info":"'."data ID".
		                '","content":"'.$message.
						'","AppID":"'.$AppID.
						'","PID":"'.$PID.
						'","OTP":"'.$OTP.
						'","SIaddress":"'.$SIcallbackaddr.
						'"}';
		$this->data = array('message' => $json_message);
	}
	
	function fillDataLogin($message, $PID, $OTP, $SIcallbackaddr){
		$json_message = '{"info":"'."login".
		                '","content":"'.$message.
						'","PID":"'.$PID.
						'","OTP":"'.$OTP.
						'","SIaddress":"'.$SIcallbackaddr.
						'"}';
		$this->data = array('message' => $json_message);
	}

	function fillDataWebSign($data, $SIsigncallbackaddr){
		$json_message = '{"info":"websign","title":"'.$data["title"].','.$data["content"].
						'","content":"'.$data["content"].
						'","hash":"'.$data["hash"].
						'","userid":"'.$data["userid"].
						'","id":"'.$data["id"].
						'","OTP":"'.$data["otp"].
						'","PID":"'.$data["pid"].
						'","SIaddress":"'.$SIsigncallbackaddr.
						'"}';
		$this->data = array('message' => $json_message);
	}
	
	function fillDataDocSign($data, $SIdocsigncallbackaddr){
		$json_message = '{"info":"docsign","title":"'.$data["title"].
						'","content":"'.$data["content"].
						'","hash":"'.$data["hash"].
						'","userid":"'.$data["userid"].
						'","id":"'.$data["id"].
						'","OTP":"'.$data["otp"].
						'","PID":"'.$data["pid"].
						'","SIaddress":"'.$SIdocsigncallbackaddr.
						'"}';
		$this->data = array('message' => $json_message);
	}

	function sendGoogleCloudMessage()
	{
		$data = $this->data;
		$ids = $this->ids;

		// $data = array('title' => 'test title', 'message' => $message);
		// $ids = array($id);
		//print "device id:".$ids[0]."\r\n";

		//------------------------------
		// Replace with real GCM API 
		// key from Google APIs Console
		// 
		// https://code.google.com/apis/console/
		//------------------------------

		$apiKey = 'AIzaSyDanY1FqEuq-qKd7CUS2PjQwGumZPsJiEY';

		//------------------------------
		// Define URL to GCM endpoint
		//------------------------------

		$url = 'https://android.googleapis.com/gcm/send';

		//------------------------------
		// Set GCM post variables
		// (Device IDs and push payload)
		//------------------------------

		$post = array(
		                'registration_ids'  => $ids,
		                'data'              => $data,
		                );

		//------------------------------
		// Set CURL request headers
		// (Authentication and type)
		//------------------------------

		$headers = array( 
		                    'Authorization: key=' . $apiKey,
		                    'Content-Type: application/json'
		                );

		//------------------------------
		// Initialize curl handle
		//------------------------------

		$ch = curl_init();

		//------------------------------
		// Set URL to GCM endpoint
		//------------------------------

		curl_setopt( $ch, CURLOPT_URL, $url );

		//------------------------------
		// Set request method to POST
		//------------------------------

		curl_setopt( $ch, CURLOPT_POST, true );

		//------------------------------
		// Set our custom headers
		//------------------------------

		curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );

		//------------------------------
		// Get the response back as 
		// string instead of printing it
		//------------------------------

		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

		//------------------------------
		// Set post data as JSON
		//------------------------------

		curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $post ) );


		//QUICK SSL HACK!!
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );

		//------------------------------
		// Actually send the push!
		//------------------------------

		$result = curl_exec( $ch );

		//------------------------------
		// Error? Display it!
		//------------------------------

		if ( curl_errno( $ch ) )
		{
		    echo 'GCM error: ' . curl_error( $ch );
		}

		//------------------------------
		// Close curl handle
		//------------------------------

		curl_close( $ch );

		//------------------------------
		// Debug GCM response
		//------------------------------

		return $result;
	}

	function error($msg){
		echo "Android send notification failed with error:";
		echo "\t" . $msg;
		exit(1);
	}
}