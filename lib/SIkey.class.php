<?php

//Parse Backend
use Parse\ParseObject;
use Parse\ParseQuery;

class SIkey {
    public function SIkey($idnumber){
		$this->idnumber = $idnumber;
	}
	
	private function searchOneDB($column, $query) {
		$web_project_que = new ParseQuery("si_userdb");
    	$web_project_que->equalTo($column, $query);
    	$results = $web_project_que->first();
    	return $results;
	}
	
	private function decryptDB() {
	    $encrypted = $this->privkey;
    	$data = utf8_decode($encrypted->get('privkey'));
    	$iv =  utf8_decode($encrypted->get('iv'));
    	$key=getkey($encrypted->get('created'));
		return decryptdb($data,$iv,$key);
	}
	
	public function getPrivateKey() {
	    $idnumber = $this->idnumber;
	    $encrypted = $this->searchOneDB('nik',$idnumber);
	    $this->privkey = $encrypted;
	    return $this->decryptDB();
	}
	
}
    
