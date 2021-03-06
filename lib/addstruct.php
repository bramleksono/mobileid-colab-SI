<?php

//Load Address
$clientaddr = json_decode(file_get_contents($addressfile));
$CAaddr = $clientaddr->CA;
$CAuserinitial = $CAaddr."/user/initial";
$CAuserreg = $CAaddr."/user/reg";
$CAuserregcheck = $CAaddr."/user/regcheck";
$CAuserregconfirm = $CAaddr."/user/regconfirm";
$CAmessaging = $CAaddr."/message";
$CAlogin = $CAaddr."/login";
$CAloginconfirm = $CAaddr."/login/confirm";
$CAverify = $CAaddr."/verify";
$CAverifyconfirm = $CAaddr."/verify/confirm";
$CAcreatemessagesig = $CAaddr."/createsig";
$CAverifymessagesig = $CAaddr."/verifysig";
$CAdocument = $CAaddr."/document";
$CAdocumentconfirm = $CAaddr."/document/confirm";
$CAdocumentverify = $CAaddr."/document/verify";

$SIaddr = $clientaddr->SI;
$SIuserreg = $SIaddr."/user/reg";
$SImessaging = $SIaddr."/message";
$SIlogin = $SIaddr."/login";
$SIloginconfirm = $SIaddr."/login/confirm";
$SIverify = $SIaddr."/verify";
$SIverifyconfirm = $SIaddr."/verify/confirm";
$SIdocument = $SIaddr."/document";
$SIdocumentconfirm = $SIaddr."/document/confirm";

$Webaddr = $clientaddr->Web;
$Webloginconfirm = $Webaddr."/process/confirm";
$Webdocumentreceive = $Webaddr."/document/receive";