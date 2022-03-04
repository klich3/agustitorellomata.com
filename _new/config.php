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
		
		"username" => ($isLocal) ? "root" : "myagustito", //user
		"password" => ($isLocal) ? "root" : "wogPcj3K", //pass
		"database" => ($isLocal) ? "agustitorellomata" : "torelloweb", //table
	),
	
	"site" => array(
		//site config
		"sitetitle" => "Agustí Torelló Mata",
		"sitetitlefull" => "Agusti Torello Mata", //S.L
		"sitecopyz" => "&copy; ".date('Y')." Agusti Torello Mata - Todos los derechos reservados.",
		"sitetitleseo" => "ATM",
		
		//directory
		"base_prefix" => str_replace('//', '', $http_prefix).'/',
		"base_script" => str_replace(array('http:', 'https:'), array('', ''), $path).'/',
		"base" => $path.'/',
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
	
	"templates" => array(
		"standartEmail" => '<!DOCTYPE html>
		<html>
		  <head>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<meta name="viewport" content="width:device-width, initial-scale:1.0" />
			<title>%title%</title>
			<style type="text/css">
			  body {
				width: 100%;
				background-color: #ebebeb;
				margin: 0;
				padding: 0;
				-webkit-font-smoothing: antialiased;
				font-family: Georgia, Times, serif;
			  }
			  table {
				border-collapse: collapse;
			  }
			</style>
		  </head>
		  <body
			leftmargin="0"
			topmargin="0"
			marginwidth="0"
			marginheight="0"
			style="font-family: Arial, Times, serif"
		  >
			<table
			  width="100%"
			  border="0"
			  cellpadding="0"
			  cellspacing="0"
			  align="center"
			>
			  <tr>
				<td width="100%" valign="top">
				  <table
					width="620"
					align="center"
					class="deviceWidth"
					border="0"
					cellspacing="0"
					cellpadding="0"
				  >
					<tbody>
					  <tr>
						<td
						  style="
							padding: 0px 20px 20px;
							text-align: left;
							color: #6a7480;
							line-height: 18px;
							font-family: Arial, sans-serif;
							font-size: 14px;
							font-weight: normal;
							vertical-align: top;
						  "
						  bgcolor="#ebebeb"
						>
						  <br />
						  <br />
						  <div style="text-align: center">
							  %site_logo%
						  </div>
						  
						  <br />
						  <p
							style="
							  -webkit-text-size-adjust: 100%;
							  -ms-text-size-adjust: 100%;
							  margin-top: 0;
							  margin-bottom: 24px;
							  margin-right: 0;
							  margin-left: 0;
							"
						  >
							%message%<br /><br /><span>%regards%</span><br /><span
							  ><b>%site_name%</b></span
							>
						  </p>
						</td>
					  </tr>
					</tbody>
				  </table>
				  <div style="height: 25px">&nbsp;</div>
				  
				  <table width="100%" border="0" cellspacing="0" align="center">
					<tr>
					  <td style="padding: 30px">
						<table
						  width="580"
						  border="0"
						  cellpadding="0"
						  cellspacing="0"
						  align="center"
						  class="deviceWidth"
						>
						  <tr>
							<td>
							  <table
								width="45%"
								cellpadding="0"
								cellspacing="0"
								border="0"
								align="left"
								class="deviceWidth"
							  >
								<tr>
								  <td
									valign="top"
									style="
									  font-size: 11px;
									  color: #9197a3;
									  font-family: Arial, sans-serif;
									  padding-bottom: 20px;
									  line-height: 16px;
									"
									class="center"
								  >
									<br />Copyright &#169; %site_name%.<br />
									%copyz%<br /><br /><span
									  style="
										font-size: 11px;
										color: #9197a3;
										font-family: Arial, sans-serif;
										line-height: 14px;
									  "
									  >%site_dir%</span
									><br /><br />
								  </td>
								</tr>
							  </table>
							</td>
						  </tr>
						</table>
					  </td>
					</tr>
				  </table>
				</td>
			  </tr>
			</table>
		  </body>
		</html>
		',
	),
);

?>