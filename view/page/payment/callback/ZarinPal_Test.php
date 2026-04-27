<?php 
$v = \_::$Joint->Finance->GetPlatform(\_::$Address->UrlResource);
if($v) return $v->ReceivePayment();
return error("Somethings went wrong!");