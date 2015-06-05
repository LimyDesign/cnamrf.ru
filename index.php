<?php
session_start();

require_once __DIR__.'/cabinet/src/vendor/autoload.php';

$loader = new Twig_Loader_Filesystem(__DIR__.'/html');
$twig = new Twig_Environment($loader, array(
	'cache' => __DIR__.'/cabinet/cache',
	'auto_reload' => true,
));

echo $twig->render('index.html');
?>