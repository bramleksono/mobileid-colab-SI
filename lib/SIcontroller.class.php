<?php
require 'SIpid.class.php'; // Handling PID interaction with database
require 'SIdocument.class.php'; // Handling document manipulation
require 'SIkey.class.php'; // Handling user private key
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
    
    private function compareDocumenthash($filepath, $hash) {
        $filehash = hitunghashfile($filepath);
        return hash_compare($filehash,$hash);
    }
    
    private function base64_to_jpeg( $base64_string, $output_file ) {
        $ifp = fopen( $output_file, "wb" ); 
        fwrite( $ifp, base64_decode( $base64_string) ); 
        fclose( $ifp ); 
        return( $output_file ); 
    }
    
    // Message Section
    
    public function messagereq($request) {
        $deviceid = $request["userinfo"]["deviceid"];
	    $message = $request["userinfo"]["message"];
	
        $gcpm = new GCMPushMessage($deviceid);
        $gcpm->notification($message);
        $response = $gcpm->sendGoogleCloudMessage();
        $response = json_decode($response, true);
        
        if ($response["success"]) {
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
    
    // Login and Verification Section
    
    public function verifyreq($request) {
        global $SIverifyconfirm;
        $error=1;
        
        $message = $request["message"];
        $deviceid = $request["deviceid"];
    	$idnumber = $request["userinfo"]["nik"];
    	$callback = $request["callback"];
    	
    	$current_date = new DateTime("now");
    	$time = $current_date->format('Y-m-d H:i:s');
    	$key=getkey($time);
    	
    	//get PID from time and nik
        $PID = $this->getPID($time,$idnumber);
        $this->PID = $PID;
        
    	//get OTP
        $OTP = $this->getOTP();
        
        //construct form
	    $form =  (object) array("userinfo" => $request["userinfo"], "OTP" => $OTP, "callback" => $callback);

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
    		$this->reason = "Cannot contacting database";
    	}

		//send request to GCM
    	$gcpm = new GCMPushMessage($deviceid);
        $gcpm->fillDataVerify($message, $PID, $OTP, $SIverifyconfirm);
        $response = $gcpm->sendGoogleCloudMessage();
        $response = json_decode($response, true);
        if ($response) {
            if ($response["success"]) {
                $error=0;
        }
            else {
                $this->reason = "GCM return errror";
                $error=1;
            }        	    
        } else {
            $this->reason = "Cannot send GCM";
            $error = 1;
        }
	    return $error;
    }
    
    public function verifyreqoutput($error) {
        switch ($error) {
    	case 0:
    		return json_encode(array(	'success' => true,
    					'PID' => $this->PID
    		));
    		break;
    	default:
    		return json_encode(array(	'success' => false,
    					'reason' => $this->reason
    		));
    		break;
    	}
    }
    
    public function verifyconfirm($request) {
        global $CAverifyconfirm;
        $PID = $request["PID"];
        $postedHMAC = $request["HMAC"];
        
        $error = 3;
        
        $data = new SIpid($PID);
    	$data->fetchPIDDB();
    	
    	//process database result
    	if ($data->isExist()) {
    		//decode and show userinfo
    		$form = $data->getPID();
    		$userinfo = json_encode($form["userinfo"]);
    		$OTP = $form["OTP"];
    		$callback = $form["callback"];
    		$idnumber =$form["userinfo"]["nik"]; 
    		
    		$userinfo = preg_replace('/\s+/', '', $userinfo);
    		$signature = hitunghashdata($userinfo);
    		$hmacresult = proseshmac($signature,$OTP,$postedHMAC);
    		if ($hmacresult) {
    			//send callback
    			$form =  (object) array("PID" => $PID, "callback" => $callback, "userinfo" => (object) array("nik" => $idnumber));
    			$form = json_encode($form);
    			sendjson($form,$CAverifyconfirm);
    			$error = 0;
    		} else {
    		    $error = 1;
    		}
    	} else {
            $error=2;
    	}
    	
    	return $error;
    }
    
    public function verifyconfirmoutput($error) {
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
    
    // Document Signing Section
    
    public function documentreq($request) {
        global $SIdocumentconfirm;
        $error=1;
        
        $message = $request["message"];
        $deviceid = $request["deviceid"];
    	$idnumber = $request["userinfo"]["nik"];
    	$callback = $request["callback"];
    	$documentnumber = $request["documentnumber"];
    	
    	//check hash
    	$fileurl = $request["document"]["fileurl"];
    	$filehash = $request["document"]["filehash"];
    	$filepath = "./temp/original.pdf";
    	file_put_contents($filepath, fopen($fileurl, 'r'));
    	if ($this->compareDocumenthash($filepath,$filehash)) {
    		$error = 0;
    	} else {
    	    $error = 1;
    		$this->reason = "Invalid hash";
    	}
    	
    	if ($error == 0) {
            $current_date = new DateTime("now");
        	$time = $current_date->format('Y-m-d H:i:s');
        	$key=getkey($time);
        	
        	//get PID from time and nik
            $PID = $this->getPID($time,$idnumber);
            $this->PID = $PID;
            
        	//get OTP
            $OTP = $this->getOTP();
            
            //construct form
    	    $form =  (object) array("userinfo" => $request["userinfo"], "OTP" => $OTP, "callback" => $callback, "documentnumber" => $documentnumber, "document" => $request["document"]);

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
        		$this->reason = "Cannot contacting database";
        	} 
    	}
    	
        if ($error == 0) {
    		//send request to GCM
        	$gcpm = new GCMPushMessage($deviceid);
            $gcpm->fillDataDocSign($filehash, $message, $PID, $OTP, $SIdocumentconfirm);
            $response = $gcpm->sendGoogleCloudMessage();
            $response = json_decode($response, true);
            if ($response) {
                if ($response["success"]) {
                    $error=0;
            }
                else {
                    $this->reason = "GCM return errror";
                    $error=1;
                }        	    
            } else {
                $this->reason = "Cannot send GCM";
                $error = 1;
            }    	    
    	}
    	
	    return $error;
    }
    
    public function documentreqoutput($error) {
        switch ($error) {
    	case 0:
    		return json_encode(array(	'success' => true,
    					'PID' => $this->PID
    		));
    		break;
    	default:
    		return json_encode(array(	'success' => false,
    					'reason' => $this->reason
    		));
    		break;
    	}
    }
    
    public function documentconfirm($request) {
        global $CAdocumentconfirm;
        $PID = $request["PID"];
        $postedHMAC = $request["HMAC"];
        $postedPIN = $request["Passphrase"];
        
        $error = 1;
        
        $data = new SIpid($PID);
    	$data->fetchPIDDB();
    	
    	//process database result
    	if ($data->isExist()) {
    		//decode and show userinfo
    		$form = $data->getPID();
    		//get hash
    		$signature = $form["document"]["filehash"];
    		//calculate hmac
    		$OTP = $form["OTP"];

    		$callback = $form["callback"];
    		$idnumber = $form["userinfo"]["nik"]; 
    		
    		$hmacresult = proseshmac($signature,$OTP,$postedHMAC);
    		if ($hmacresult) {
                $current_date = new DateTime("now");
        	    $time = $current_date->format('Y-m-d H:i:s');
        	    
    		    //generate pdf with message
                
    		    $documentname = $form["document"]["documentname"];
    		    $message = "Document with name ".$documentname." has been signed by ".$idnumber." at time ".$time;
                $signature = $form["userinfo"]["signature"];
                
                $signaturefile = "./temp/signature.jpg";
                $this->base64_to_jpeg( $signature, $signaturefile);
                
                $generatedpath = $_SERVER["DOCUMENT_ROOT"].'/temp/generated.pdf';
                
                $documentcontroller = new SIdocument();
                $documentcontroller->createsignpage($message,$signaturefile,$generatedpath);
                
                //download original document
            	$fileurl = $form["document"]["fileurl"];
            	$filepath = "./temp/original.pdf";
            	file_put_contents($filepath, fopen($fileurl, 'r'));
            	
            	//combine to final document
            	$finalpath = "./temp/".$form["documentnumber"].".signed.pdf";
            	$documentcontroller->createsignedpdf($filepath,$generatedpath,$finalpath);
            	
            	//get private key using posted PIN
            	$keycontroller = new SIkey($idnumber);
            	$privkey = $keycontroller->getPrivateKey();
            	$privatekey = utf8_decode($privkey);
            	//generate signature
            	$finalhash = hitunghashfile($finalpath);
            	$message = "hash: ".$finalhash." time: ".$time." WIB";
			    $pphrase = getkey($postedPIN);
            	
	            $key = openssl_pkey_get_private($privatekey, $pphrase);
                if (empty($key)) {
                    $this->reason = "Cannot open Key. Invalid PIN.";
                }
                else {
                    $error = 0;
                }
    		} else {
    		    $this->reason = "HMAC failed";
    		}
    	} else {
            $this->reason = "Cannot find PID";
    	}
    	
    	if ($error == 0) {
    	    //generate signature
    	    openssl_sign($message, $finalsignature, $key, OPENSSL_ALGO_SHA256);
            $finalsignature = base64_encode($finalsignature);
            
    		//send callback to Web service
    		//content: signedfile, documentnumber, signature, hash
    		
    		$form =  (object) array("documentnumber" => $form["documentnumber"], "signature" => $finalsignature, "signedhash" => $finalhash, "signedtime" => $time);
    		$form = json_encode($form);
    		
    		echo $result = sendfile($finalpath, $form, $callback);
            //sample output : {"signedtime":"2015-03-28 07:15:29","finalhash":"1a1f05a1f6cf823fab41f69c0a00f64a64d5874d08265b9ffd5624092b5534a9","signature":"lftadtwwszA9DN7s6VDXOzHRPRowUV6AFRH4mWeKY//GPQ1mDulI1Wesrf4AzniN53W7+mwehjAF4gXLTV5MG68xUwoVKFKN2fK90kLSsnBmxPAJ1nxRVMmizZV3MbYZOLYyHj6IvIpaO00b7PgThTsqCncIH7hnHIdSpEx2ugp6Y3dcpAjqR9h/bRGO+btvRSsDsnuBMbajmRcKoUCGuj0S3G7QZGLmx6ifHLFqfl9Rzm+7wtfskKUbG93UqXM0DdnuiswMtwtQ4/LfHoOCLZezF5R4uVjd2sj/aga9J74W2zFhv5GMGba7Tmc6rC8ilVoUJsk2dQDkV0x/PAjcffMXz+Hiil9B/utM54hsZAa9nfVk3nhX30rkgRjUimdrwquRtfJrZUQitvO27WYx4TPie8wQMHj92l+XyLgPmC8sf7EiVKWBf13JTUA5eCOGZA9/txVb8ItTAn65vMokARzjEJqhEdihRFTfu+zjUErznMAzJD+Qk3wHTLM/PTbXI8lI6aOb+d7H6FSU+rca5/WbuyRkMIxcwgb1X1r79Zk7vD3QLykGV0v52ogxuRwa2CFRgX1Dt/eivollcQyAEYuxcX2evaPhlUkHMtLOKgyMPoi2rwdFU/+4DdzMimHlQ3dzgAYvOc2/XIYkX9DBSztaBgOF1LdFg4fIjm1XkBM=","documentnumber":"28007529dd4734671c2060cbcb7c2b85"}
    	}
    	return $error;
    }
    
    public function documentconfirmoutput($error) {
        switch ($error) {
    	case 0:
    		return json_encode(array(	'success' => true
    		));
    		break;
    	case 1:
    		return json_encode(array(	'success' => false,
    					'reason' => $this->reason
    		));
    		break;
    	default:
    		return json_encode(array(	'success' => false,
    					'reason' => "Unknown error"
    		));
    		break;
    	}    }
    
}