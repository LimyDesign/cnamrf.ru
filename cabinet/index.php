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

$conf = json_decode(file_get_contents(__DIR__.'/config.json'), true);

if ($_SESSION['auth'] == 'true') 
{
}
else
{
	var_dump($conf);
	echo $twig->render('auth.html', array(
		'fb_link' => 'https://www.facebook.com/dialog/oauth?' . login_query('facebook'),
		'vk_link' => 'https://oauth.vk.com/authorize?' . login_query('vkontakte'),
		'gp_link' => 'https://accounts.google.com/o/oauth2/auth?' . login_query('google-plus'),
		'ok_link' => 'http://www.odnoklassniki.ru/oauth/authorize?' . login_query('odnoklassniki'),
		'mr_link' => 'https://connect.mail.ru/oauth/authorize?' . login_query('mailru'),
		'ya_link' => 'https://oauth.yandex.ru/authorize?' . login_query('yandex')));
}

function login_query ($provider) {
	global $conf;
	$redirect_uri = rawurlencode('http://'.$_SERVER['SERVER_NAME'].'/cabinet/auth/'.$provider);
	$state = sha1($_SERVER['HTTP_USER_AGENT'].time());

	if ($provider == 'facebook') {
		return 'client_id='.$conf->provider->facebook->CLIENT_ID.'&scope=email&redirect_uri='.$redirect_uri.'&response_type=code';
	} elseif ($provider == 'vkontakte') {
		return 'client_id='.$conf->provider->vkontakte->CLIENT_ID.'&scope=email&redirect_uri='.$redirect_uri.'&response_type=code&v=5.29&state='.$state.'&display=page';
	} elseif ($provider == 'google-plus') {
		$gp_scope = rawurlencode('https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile');
		return 'client_id='.$conf->provider->googleplus->CLIENT_ID.'&scope='.$gp_scope.'&redirect_uri='.$redirect_uri.'&response_type=code&state='.$state.'&access_type=online&approval_prompt=auto&login_hint=email&include_granted_scopes=true';
	} elseif ($provider == 'odnoklassniki') {
		return 'client_id='.$conf->provider->odnoklassniki->CLIENT_ID.'&scope=GET_EMAIL&response_type=code&redirect_uri='.$redirect_uri.'&state='.$state;
	} elseif ($provider == 'mailru') {
		return 'client_id='.$conf->provider->mailru->CLIENT_ID.'&response_type=code&redirect_uri='.$redirect_uri;
	} elseif ($provider == 'yandex') {
		return 'client_id='.$conf->provider->yandex->CLIENT_ID.'&response_type=code&state='.$state;
	}
}
?>