<?php

//Load Address
$clientaddr = json_decode(file_get_contents($addressfile));
$CAaddr = $clientaddr->CA;
$CAuserreg = $CAaddr."/user/reg";
$CAuserregconfirm = $CAaddr."/user/regconfirm";

$SIaddr = $clientaddr->SI;
