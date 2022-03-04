<?php

global $CONFIG;

$too_login = new tooLogin($CONFIG);
$fn_isLoggedOut = $too_login->logout(false);

if($fn_isLoggedOut == 200) header("Location: {$CONFIG['site']['base']}login");

?>