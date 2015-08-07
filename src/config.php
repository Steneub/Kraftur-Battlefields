<?php
//$MySQL_hostname	= "mysql.pie-hole.com";
$MySQL_hostname	= "localhost:3306";
$MySQL_database = "kraftur";
$MySQL_user	= "kraftur_user";
$MySQL_userpw	= "gouranga";
$MySQL_prefix  = "Kraftur_";
$MySQL_context = $MySQL_database.'.'.$MySQL_prefix;

$handle = mysql_pconnect($MySQL_hostname, $MySQL_user, $MySQL_userpw);
if (!$handle) die('Could not connect: ' . mysql_error());
?>