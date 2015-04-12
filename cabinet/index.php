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
		'facebook' => '#facebook',
		'vkontakte'=>'#vkontakte',
		'google-plus'=>'#google-plus',
		'odnoklassniki'=>'#odnoklassniki',
		'mailru'=>'#mailru',
		'yandex'=>'#yandex');
	echo $twig->render('auth.html', array('provider' => $prodiver));
}
?>