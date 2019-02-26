<?php

//detect source and path correction
$port_live = ($_SERVER["SERVER_PORT"]) ? ':'.$_SERVER["SERVER_PORT"] : '';
$path_live = (@$_SERVER["HTTPS"] == "on") ? "https://" : "http://";
$path_live .= $_SERVER["SERVER_NAME"].dirname($_SERVER["PHP_SELF"]);
//$fn_www = (preg_match('/www/', $path_live)) ? '':'www.';
$fn_www = '';
$path_live = preg_replace('/^(https?:\/\/)?(.+)$/', "$1{$fn_www}$2", $path_live);

$port_local = ($_SERVER["SERVER_PORT"]) ? ':'.$_SERVER["SERVER_PORT"] : '';
$port_local = ($port_local !== 80) ? $port_local : '';
$path_local = (@$_SERVER["HTTPS"] == "on") ? "https://" : "http://";
$path_local .= $_SERVER["SERVER_NAME"].$port_local.dirname($_SERVER["PHP_SELF"]).'/';

$isLocal = (preg_match('/(too|8888)/', $_SERVER["HTTP_HOST"])) ? true:false;
$path = (!$isLocal) ? $path_live : $path_local;

$http_prefix = (@$_SERVER["HTTPS"] == "on") ? "https://" : "http://";

$CONFIG = array(
	"status" => array(
		"debug" => (!$isLocal) ? false:true,
	),
	
	"database" => array(
		"host" => "localhost", //server
		"port" => "", //server port
		
		"username" => ($isLocal) ? "root" : "agustitorre", //user
		"password" => ($isLocal) ? "root" : "I4b31k^d_v", //pass
		"database" => ($isLocal) ? "agustitorellomata" : "agustitorellomat_web", //table
	),
	
	"site" => array(
		//site config
		"sitetitle" => "Agustí Torelló Mata",
		"sitetitlefull" => "Agusti Torello Mata", //S.L
		"sitecopyz" => "&copy; ".date('Y')." Agusti Torello Mata - Todos los derechos reservados.",
		"sitetitleseo" => "Blu",
		
		//directory
		"base_prefix" => str_replace('//', '', $http_prefix),
		"base_script" => str_replace(array('http:', 'https:'), array('', ''), $path),
		"base" => $path,
		"basedir" => $path.'/',
		"templatedir" => $path."templates/",
		"templatepath" => "templates/",
		"template"	=> dirname(__FILE__)."/templates/",
		"pluginsdir" => $path."plugins/",
		"pluginspath" => "plugins/",
		"cachedir" => "cache/",
		"cachepath" => $path."cache/",
		
		"date_today_reservas" => date('d-m-Y'),
		"date_today" => date('Y-m-d'),
		"date_max_guestlist" => date('d.m.Y', strtotime('+ 10 days')),
	),
);

?>