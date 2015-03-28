<?php

$app->post('/document', function () use ($app) {
    //example query : {"document":{"filehash":"6659b399f20a5ec968741b2659a4c209417b12ca9ef20404d91c13aff31872a7","fileurl":"http://files.parsetfss.com/ec7e7074-b676-4984-92ba-13c0c26c2d0d/tfss-c45261a4-45ab-4846-9a42-90e990ed10cf-ProgLan-23213321-Tugas1.pdf"},"callback":"tes","message":"Signing request for Dokumen 3 document from Tes 1 project with description Tes","deviceid":"APA91bEe5cIMWAgYlED-kSpZeBcWAwJsJEpR2dkbjvvbQ8Q829uc1YBK1aQR4Bl8DyPUqkYWm9zSQuIuuybBkqlKG_mTDzOKYmPBhIX7_csoX14EOO-mekBg7YSjcdHDYWYnZncvIVdpSeXAZq74HNpWboggu244em1Bs0sBbbSh05Nm-3H-Xsc","userinfo":{"berlaku":"23-05-2200","kewarganegaraan":"Indonesia","pekerjaan":"Mahasiswa","statperkawinan":"Belum Kawin","agama":"Islam","kecamatan":"Cibeunying Kaler","keldesa":"Cigadung","rtrw":"001/001","alamat":"Jalan Ligar Sejoli no 5","goldarah":"A","jeniskelamin":"Laki-laki","ttl":"Bandung/23 Mei 1989","nama":"Bramanto Leksono","nik":"1231230509890001"}}
    $body = json_decode($app->request()->getBody(), true);
    
    $controller = new SIcontroller();
	$error = $controller->documentreq($body);
	
	//output request code to device and CA
	header('Content-Type: application/json');
	echo $controller->documentreqoutput($error);
});

$app->post('/document/confirm', function () use ($app) {
    //example query : {"HMAC":"8f18efd1e69dc59847f1b5396a983c764533afe615015255d77b9cc29caae723","Passphrase":"123456","PID":"54e2d6218a6ddd2d5c596b38cbfbcca4210a2963"}
    $body = json_decode($app->request()->getBody(), true);
	
	$controller = new SIcontroller();
	$error = $controller->documentconfirm($body);
	
	//output result to device
	header('Content-Type: application/json');
	echo $controller->documentconfirmoutput($error);
});