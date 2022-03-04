<?php

global $CONFIG;

$too_login = new tooLogin($CONFIG);
if($too_login->isLogged() !== 200) header("Location: {$CONFIG['site']['base']}login");

?>