<?php
// стартуем сесиию, нахуй!
session_start();

// автозагрузчик классов
require_once __DIR__.'/src/vendor/autoload.php';

$loader = new Twig_Loader_Filesystem(__DIR__.'/templates');
$twig = new Twig_Environment($loader, array(
	'cache' => __DIR__.'/cache',
	'auto_reload' => true,
));

if ($_SESSION['auth'] == 'true') 
{
}
else
{
	$provider = array(
		'facebook' => '',
		'vkontakte'=>'',
		'google-plus'=>'',
		'odnoklassniki'=>'',
		'mailru'=>'',
		'yandex'=>'');
	echo $twig->render('auth.html', array('provider' => $prodiver));
}
?>