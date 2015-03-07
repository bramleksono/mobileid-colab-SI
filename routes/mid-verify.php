<?php

$app->post('/verify', function () use ($app) {
    //example query : {"callback":"http://postcatcher.in/catchers/54f7074cc895880300002ba1","message":"Verification request from user 1231230509890005","deviceid":"APA91bH04os8rdZEIxOu7_LxQEnGnnYeJbzZEMbsLUgQ96Z46fjaBBBMyzv0o5HaNYF4O5zCAc02_BHSn3UfrZkYIc6GtJb3lMqOD7XVAE2WCqAe6b5-f8Q1GV9yv54zKaJ8se-wADgmPJiImSgUHoSq1sLS143me7NFxXiv3XXvxHjedpdkbgY","userinfo":{"berlaku":"123","kewarganegaraan":"123","pekerjaan":"123","statperkawinan":"123","agama":"123","kecamatan":"123","keldesa":"123","rtrw":"123","alamat":"123","goldarah":"123","jeniskelamin":"123","ttl":"Bandung/123123123","nama":"Bramanto Leksono","nik":"1231230509890001"}}
    $body = json_decode($app->request()->getBody());
    
    $sendlogin = new SIcontroller();
	$error = $sendlogin->verifyreq($body);
	
	//output request code to device and CA
	header('Content-Type: application/json');
	echo $sendlogin->verifyreqoutput($error);
});

$app->post('/verify/confirm', function () use ($app) {
    //example query : {"PID":"1d31138b752871d136f735476b44a670f831c715","HMAC":"afbf1cd5f1778a9961f9adbe67797bfeb3a3287489caacc4523e407e4dcc1ae6"}
    $body = json_decode($app->request()->getBody());
	
	$processlogin = new SIcontroller();
	$error = $processlogin->verifyconfirm($body);
	
	//output request code to device and CA
	header('Content-Type: application/json');
	echo $processlogin->verifyconfirmoutput($error);
});