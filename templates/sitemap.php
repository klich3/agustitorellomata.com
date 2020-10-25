<?php

global $CONFIG, $fn_page_args, $db;

$fn_q = $db->FetchAll("
	SELECT *
	FROM `pages`
	WHERE `active`='1'
");

echo '<?xml version="1.0" encoding="utf-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">';

foreach($fn_q as $k => $v)
{
	echo "<url><loc>{$v->obj_hash}</loc><lastmod>{$v->update_date}</lastmod>";
}

echo '</urlset>';
		
?>