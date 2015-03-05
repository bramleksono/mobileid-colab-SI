<?php
//collection of frequently used function

function getPID($time,$idnumber) {
    $string = $time.$idnumber;
	return hash('sha1', $string);
}

function getOTP() {
    $bytes = openssl_random_pseudo_bytes(4);
    return bin2hex($bytes);
}