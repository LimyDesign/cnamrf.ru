<?php
session_start();
use LD\cnamrf\Cabinet as Cabinet;
$cabinet = new Cabinet;

if ($_SESSION['auth'] == 'true') 
{
	$cabinet->dashboard();
}
else
{
	$cabinet->auth();
}
?>