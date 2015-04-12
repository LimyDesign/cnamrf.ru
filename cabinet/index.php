<?php
session_start();
use LD\cnamrf\Cabinet;

if ($_SESSION['auth'] == 'true') 
{
	$cabinet->dashboard();
}
else
{
	$cabinet->auth();
}
?>