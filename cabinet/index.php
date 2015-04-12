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
	echo $twig->render('auth.html', array(
		'fb_link' => '#facebook',
		'vk_link' => '#vkontakte',
		'gp_link' => '#google-plus',
		'ok_link' => '#odnoklassniki',
		'mr_link' => '#mailru',
		'ya_link' => '#yandex'));
}
?>