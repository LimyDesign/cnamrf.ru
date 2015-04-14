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

$requestURI = explode('/',$_SERVER['REQUEST_URI']);
$scriptName = explode('/',$_SERVER['SCRIPT_NAME']);
for ($i=0;$i<sizeof($scriptName);$i++)
{
	if ($requestURI[$i] == $scriptName[$i])
		unset($requestURI[$i]);
}
$cmd = array_values($requestURI);

switch ($cmd[0]) {
	case 'auth':
		auth($cmd[1]);
		break;
	case 'dashboard':
	case 'tariff':
	case 'balans':
	case 'profile':
	case 'key':
	case 'log':
		check_auth();
		break;
	case 'logout':
		logout();
		break;
}

if ($_SESSION['auth'] === true) 
{
	switch ($cmd[0]) {
		case 'tariff':
			echo $twig->render('tariff.html', array('tariff' => true));
			break;
		case 'balans':
			echo $twig->render('balans.html', array('balans' => true));
			break;
		case 'profile':
			echo $twig->render('profile.html', array(
				'profile' => true,
				'fb_link' => 'https://www.facebook.com/dialog/oauth?' . login_query('facebook'),
				'vk_link' => 'https://oauth.vk.com/authorize?' . login_query('vkontakte'),
				'gp_link' => 'https://accounts.google.com/o/oauth2/auth?' . login_query('google-plus'),
				'ok_link' => 'http://www.odnoklassniki.ru/oauth/authorize?' . login_query('odnoklassniki'),
				'mr_link' => 'https://connect.mail.ru/oauth/authorize?' . login_query('mailru'),
				'ya_link' => 'https://oauth.yandex.ru/authorize?' . login_query('yandex'),
				'facebook' => checkProviderLink('fb'),
				'vkontakte' => checkProviderLink('vk'),
				'googleplus' => checkProviderLink('gp'),
				'odnoklassniki' => checkProviderLink('ok'),
				'mailru' => checkProviderLink('mr'),
				'yandex' => checkProviderLink('ya')));
			break;
		default:
			echo $twig->render('dashboard.html', array('dashboard' => true));
			break;
	}
}
else
{
	// echo "<pre>"; var_dump($cmd); echo "</pre>";
	echo $twig->render('auth.html', array(
		'fb_link' => 'https://www.facebook.com/dialog/oauth?' . login_query('facebook'),
		'vk_link' => 'https://oauth.vk.com/authorize?' . login_query('vkontakte'),
		'gp_link' => 'https://accounts.google.com/o/oauth2/auth?' . login_query('google-plus'),
		'ok_link' => 'http://www.odnoklassniki.ru/oauth/authorize?' . login_query('odnoklassniki'),
		'mr_link' => 'https://connect.mail.ru/oauth/authorize?' . login_query('mailru'),
		'ya_link' => 'https://oauth.yandex.ru/authorize?' . login_query('yandex'),
		'home_link' => 'http://'.$_SERVER['SERVER_NAME']));
}

function login_query ($provider) {
	global $conf;
	foreach ($conf['provider'] as $key => $value) {
		$client_id[$key] = $value['CLIENT_ID'];
	}
	$redirect_uri = rawurlencode('http://'.$_SERVER['SERVER_NAME'].'/cabinet/auth/'.$provider.'/');
	if ($_SESSION['state']) 
	{
		$state = $_SESSION['state'];
	}
	else
	{
		$state = sha1($_SERVER['HTTP_USER_AGENT'].time());
		$_SESSION['state'] = $state;
	}

	if ($provider == 'facebook') {
		return 'client_id='.$client_id[$provider].'&scope=email&redirect_uri='.$redirect_uri.'&response_type=code';
	} elseif ($provider == 'vkontakte') {
		return 'client_id='.$client_id[$provider].'&scope=email&redirect_uri='.$redirect_uri.'&response_type=code&v=5.29&state='.$state.'&display=page';
	} elseif ($provider == 'google-plus') {
		$gp_scope = rawurlencode('https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile');
		return 'client_id='.$client_id[$provider].'&scope='.$gp_scope.'&redirect_uri='.$redirect_uri.'&response_type=code&state='.$state.'&access_type=online&approval_prompt=auto&login_hint=email&include_granted_scopes=true';
	} elseif ($provider == 'odnoklassniki') {
		return 'client_id='.$client_id[$provider].'&scope=GET_EMAIL&response_type=code&redirect_uri='.$redirect_uri.'&state='.$state;
	} elseif ($provider == 'mailru') {
		return 'client_id='.$client_id[$provider].'&response_type=code&redirect_uri='.$redirect_uri;
	} elseif ($provider == 'yandex') {
		return 'client_id='.$client_id[$provider].'&response_type=code&state='.$state;
	}
}

function auth ($provider) {
	global $conf;
	$redirect_uri = 'http://'.$_SERVER['SERVER_NAME'].'/cabinet/auth/'.$provider.'/';
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	if ($provider == 'facebook') {
		$data = http_build_query(array(
			'client_id' => $conf['provider'][$provider]['CLIENT_ID'],
			'client_secret' => $conf['provider'][$provider]['CLIENT_SECRET'],
			'code' => $_GET['code'],
			'redirect_uri' => $redirect_uri
		));
		curl_setopt($curl, CURLOPT_URL, 'https://graph.facebook.com/oauth/access_token?'.$data);
		parse_str($response = curl_exec($curl));
		curl_setopt($curl, CURLOPT_URL, 'https://graph.facebook.com/me?access_token='.$access_token);
		$res = json_decode(curl_exec($curl));
		auth_db($res->id, $res->email, $provider);
	} elseif ($provider == 'vkontakte') {
		$data = http_build_query(array(
			'client_id' => $conf['provider'][$provider]['CLIENT_ID'],
			'client_secret' => $conf['provider'][$provider]['CLIENT_SECRET'],
			'code' => $_GET['code'],
			'redirect_uri' => $redirect_uri
		));
		curl_setopt($curl, CURLOPT_URL, 'https://oauth.vk.com/access_token?'.$data);
		$res = json_decode(curl_exec($curl));
		auth_db($res->user_id, $res->email, $provider);
	} elseif ($provider == 'google-plus') {
		$data =  http_build_query(array(
			'client_id' => $conf['provider'][$provider]['CLIENT_ID'],
			'client_secret' => $conf['provider'][$provider]['CLIENT_SECRET'],
			'code' => $_GET['code'],
			'redirect_uri' => $redirect_uri,
			'grant_type' => 'authorization_code'
		));
		curl_setopt($curl, CURLOPT_URL, 'https://accounts.google.com/o/oauth2/token');
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		$res = json_decode(curl_exec($curl));
		curl_setopt($curl, CURLOPT_URL, 'https://www.googleapis.com/oauth2/v1/userinfo?access_token='.$res->access_token);
		curl_setopt($curl, CURLOPT_POST, false);
		$res = json_decode(curl_exec($curl));
		auth_db($res->id, $res->email, $provider);
	} elseif ($provider == 'odnoklassniki') {
		$data = http_build_query(array(
			'client_id' => $conf['provider'][$provider]['CLIENT_ID'],
			'client_secret' => $conf['provider'][$provider]['SECRET_KEY'],
			'code' => $_GET['code'],
			'redirect_uri' => $redirect_uri,
			'grant_type' => 'authorization_code'
		));
		curl_setopt($curl, CURLOPT_URL, 'https://api.odnoklassniki.ru/oauth/token.do');
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		$res = json_decode(curl_exec($curl));
		$con_param = 'application_key='.$conf['provider'][$provider]['PUBLIC_KEY'].'fields=uid,emailmethod=users.getCurrentUser';
		$ac_ask = $res->access_token.$conf['provider'][$provider]['SECRET_KEY'];
		$md5_ac_ask = md5($ac_ask);
		$sig = $con_param . $md5_ac_ask;
		$md5_sig = md5($sig);
		$data = http_build_query(array(
			'application_key' => $conf['provider'][$provider]['PUBLIC_KEY'],
			'method' => 'users.getCurrentUser',
			'access_token' => $res->access_token,
			'fields' => 'uid,email',
			'sig' => $md5_sig
		));
		curl_setopt($curl, CURLOPT_URL, 'http://api.ok.ru/fb.do?'.$data);
		curl_setopt($curl, CURLOPT_POST, false);
		$res = json_decode(curl_exec($curl));
		auth_db($res->uid, $res->email, $provider);
	} elseif ($provider == 'mailru') {
		$data = http_build_query(array(
			'client_id' => $conf['provider'][$provider]['CLIENT_ID'],
			'client_secret' => $conf['provider'][$provider]['SECRET_KEY'],
			'code' => $_GET['code'],
			'redirect_uri' => $redirect_uri,
			'grant_type' => 'authorization_code'
		));
		curl_setopt($curl, CURLOPT_URL, 'https://connect.mail.ru/oauth/token');
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		$res = json_decode(curl_exec($curl));
		$sig = 'app_id='.$conf['provider'][$provider]['CLIENT_ID'].'method=users.getInfosecure=1session_key='.$res->access_token.$conf['provider'][$provider]['SECRET_KEY'];
		$md5_sig = md5($sig);
		$data = http_build_query(array(
			'app_id' => $conf['provider'][$provider]['CLIENT_ID'],
			'method' => 'users.getInfo',
			'secure' => 1,
			'session_key' => $res->access_token,
			'sig' => $md5_sig
		));
		curl_setopt($curl, CURLOPT_URL, 'http://www.appsmail.ru/platform/api?'.$data);
		curl_setopt($curl, CURLOPT_POST, false);
		$res = json_decode(curl_exec($curl));
		auth_db($res->uid, $res->email, $provider);
	} elseif ($provider == 'yandex') {
		$data = http_build_query(array(
			'client_id' => $conf['provider'][$provider]['CLIENT_ID'],
			'client_secret' => $conf['provider'][$provider]['CLIENT_SECRET'],
			'code' => $_GET['code'],
			'grant_type' => 'authorization_code'
		));
		curl_setopt($curl, CURLOPT_URL, 'https://oauth.yandex.ru/token');
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		$res = json_decode(curl_exec($curl));
		curl_setopt($curl, CURLOPT_URL, 'http://login.yandex.ru/info?'.$res->access_token);
		curl_setopt($curl, CURLOPT_POST, false);
		$res = json_decode(curl_exec($curl));
		auth_db($res->id, $res->default_email, $provider);
	}
}

function auth_db ($id, $email, $provider) {
	global $conf;

	switch ($provider) {
		case 'facebook':
			$pr = 'fb';
			break;
		case 'vkontakte':
			$pr = 'vk';
			break;
		case 'google-plus':
			$pr = 'gp';
			break;
		case 'odnoklassniki':
			$pr = 'ok';
			break;
		case 'mailru':
			$pr = 'mr';
			break;
		case 'yandex':
			$pr = 'ya';
			break;
	}

	if ($conf['db']['type'] == 'postgres')
	{
		$db = pg_connect("host=".$conf['db']['host'].' dbname='.$conf['db']['database'].' user='.$conf['db']['username'].' password='.$conf['db']['password']) or die('Невозможно подключиться к БД: '.pg_last_error());
		if ($_SESSION['userid'])
		{
			$query = "UPDATE users SET {$pr} = {$id} WHERE id = {$_SESSION['userid']}";
			$result = pg_query($query);
			pg_free_result($result);
			pg_close($db);
			header("Location: /cabinet/profile/");
		}
		else
		{
			$query = 'SELECT * FROM users WHERE '.$pr.' = '.$id;
			$result = pg_query($query);
			if (pg_num_rows($result) != 1) 
			{
				$query = "INSERT INTO users (email, {$pr}) VALUES ('{$email}', '{$id}') RETURNING id";
				$result = pg_query($query);
				$userid = pg_fetch_result($result, 0, 0);
			}
			else
			{
				$userid = pg_fetch_result($result, 0, 'id');
			}
			$_SESSION['userid'] = $userid;
			$_SESSION['auth'] = true;
			pg_free_result($result);
			pg_close($db);
			header("Location: /cabinet/dashboard/");
		}
	}
}

function checkProviderLink ($pr) {
	global $conf;
	if ($conf['db']['type'] == 'postgres')
	{
		$db = pg_connect("host=".$conf['db']['host'].' dbname='.$conf['db']['database'].' user='.$conf['db']['username'].' password='.$conf['db']['password']) or die('Невозможно подключиться к БД: '.pg_last_error());
		$query = "SELECT {$pr} FROM users WHERE id = {$_SESSION['userid']}";
		$result = pg_query($query);
		$provider = pg_fetch_result($result, 0, $pr);
		pg_free_result($result);
		pg_close($db);
		if ($provider > 0)
			return $provider;
		else
			return 0;
	}
}

function check_auth() {
	if ($_SESSION['auth'] !== true || $_SESSION['userid'] == 0)
		header("Location: /cabinet/");
}

function logout() {
	session_destroy();
	header("Location: /cabinet/");
}
?>