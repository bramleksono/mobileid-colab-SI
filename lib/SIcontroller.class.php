<?php
require 'SIpid.class.php'; // Handling GCM push message to device
require 'GCMPushMessage.php'; // Handling GCM push message to device
require 'crypt.php';  // Handling cryptographic function
require 'addstruct.php';  // Construct client address
require 'sending.php';  // Handling sending http request function

//Parse Backend
use Parse\ParseObject;
use Parse\ParseQuery;

class SIcontroller {
    
    private function getPID($time,$idnumber) {
        $string = $time.$idnumber;
    	return hash('sha1', $string);
    }
    
    private function getOTP() {
        $bytes = openssl_random_pseudo_bytes(4);
        return bin2hex($bytes);
    }
    
    // Message Section
    
    public function messagereq($request) {
        $deviceid = $request->userinfo->deviceid;
	    $message = $request->userinfo->message;
	
        $gcpm = new GCMPushMessage($deviceid);
        $gcpm->notification($message);
        $response = $gcpm->sendGoogleCloudMessage();
        $response = json_decode($response);
        
        if ($response->success) {
            $error = 0;
        } else {
            $error = 1;
        }
    }
    
    public function messagereqoutput($error) {
        switch ($error) {
    	case 0:
    		//send pubkey 
            return json_encode(array(	'success' => true
		    ));
    		break;
    	default:
    		return json_encode(array(	'success' => false,
                                'reason' => "GCM Failed"
		    ));
    		break;
    	}
    }
    
    // Login Section
    
    public function loginreq($request) {
        global $SIloginconfirm;
        $error=2;
        
        $message = $request->message;
        $deviceid = $request->deviceid;
    	$idnumber = $request->userinfo->nik;
    	$callback = $request->callback;
    	
    	$current_date = new DateTime("now");
    	$time = $current_date->format('Y-m-d H:i:s');
    	$key=getkey($time);
    	
    	//get PID from time and nik
        $PID = $this->getPID($time,$idnumber);
        $this->PID = $PID;
        
    	//get OTP
        $OTP = $this->getOTP();
        
        //construct form
	    $form =  (object) array("userinfo" => $request->userinfo, "OTP" => $OTP, "callback" => $callback);

	    //encrypt user info
	    $result = encryptdb(json_encode($form),$key);
    	$data = $result[0];
    	$iv = $result[1];

        //save PID to database
        $piddb = new SIpid($PID);
        $pidresult = $piddb->storePIDDB($PID,$data,$time,$iv);
    	if ($pidresult) {
    		$error = 0;
    	} else {
    		$error = 1;
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
	    return $error;
    }
    
    public function loginreqoutput($error) {
        switch ($error) {
    	case 0:
    		return json_encode(array(	'success' => true,
    					'PID' => $this->PID
    		));
    		break;
    	case 1:
    		return json_encode(array(	'success' => false,
    					'reason' => "Cannot contacting database"
    		));
    		break;
    	default:
    		return json_encode(array(	'success' => false,
    					'reason' => "Cannot send GCM"
    		));
    		break;
    	}
    }
    
    public function loginconfirm($request) {
        global $CAloginconfirm;
        $PID = $request->PID;
        $postedHMAC = $request->HMAC;
        
        $error = 3;
        
        $data = new SIpid($PID);
    	$data->fetchPIDDB();
    	
    	//process database result
    	if ($data->isExist()) {
    		//decode and show userinfo
    		$form = $data->getPID();
    		$userinfo = json_encode($form->userinfo);
    		$OTP = $form->OTP;
    		$callback = $form->callback;
    		
    		$userinfo = preg_replace('/\s+/', '', $userinfo);
    		$signature = hitunghashdata($userinfo);
    		$hmacresult = proseshmac($signature,$OTP,$postedHMAC);
    		if ($hmacresult) {
    			//send callback
    			$form =  (object) array("PID" => $PID, "callback" => $callback);
    			$form = json_encode($form);
    			sendjson($form,$CAloginconfirm);
    			$error = 0;
    		} else {
    		    $error = 1;
    		}
    	} else {
            $error=2;
    	}
    }
    
    public function loginconfirmoutput($error) {
        switch ($error) {
    	case 0:
    		return json_encode(array(	'success' => true
    		));
    		break;
    	case 1:
    		return json_encode(array(	'success' => false,
    					'reason' => "HMAC failed"
    		));
    		break;
    	default:
    		return json_encode(array(	'success' => false,
    					'reason' => "Cannot find PID"
    		));
    		break;
    	}
    }
}