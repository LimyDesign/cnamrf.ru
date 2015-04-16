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
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false, true);

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
	case 'unlink':
		providerUnlink($cmd[1]);
		break;
	case 'newkey':
		newAPIKey();
		break;
	case 'accept':
		acceptContract();
		break;
	case 'not-accept':
		acceptContract(false);
		break;
	case 'invoice':
		generateInvoice($_POST['invoice']);
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
	if ($_SESSION['contract'] === true) 
	{
		switch ($cmd[0]) {
			case 'tariff':
				echo $twig->render('tariff.html', array(
					'tariff' => true,
					'cnam' => selectTariff($cmd[2]),
					'current' => selectTariff()));
				break;
			case 'balans':
				echo $twig->render('balans.html', array('balans' => true));
				break;
			case 'profile':
				echo $twig->render('profile.html', array(
					'profile' => true,
					'userid' => $_SESSION['userid'],
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
					'yandex' => checkProviderLink('ya')
				));
				break;
			case 'key':
				echo $twig->render('key.html', array(
					'key' => true,
					'apikey' => userAPIKey()));
				break;
			case 'log':
				echo $twig->render('log.html', array(
					'log' => true,
					'logs_data' => getUserLogs()));
				break;
			case 'support':
				echo $twig->render('support.html', array(
					'support' => true));
				break;
			case 'contract':
				echo $twig->render('contract.html', array(
					'contract' => true,
					'accept' => $_SESSION['contract']));
				break;
			default:
				echo $twig->render('dashboard.html', array('dashboard' => true));
				break;
		}
	} else {
		echo $twig->render('contract.html', array(
			'contract' => true,
			'accept' => $_SESSION['contract']));
	}
}
else
{
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
		auth_db($res[0]->uid, $res[0]->email, $provider);
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
		curl_setopt($curl, CURLOPT_URL, 'https://login.yandex.ru/info?oauth_token='.$res->access_token);
		curl_setopt($curl, CURLOPT_POST, false);
		$res = json_decode(curl_exec($curl));
		auth_db($res->id, $res->default_email, $provider);
	}
}

function auth_db ($id, $email, $provider) {
	global $conf;
	$pr = convertProvider($provider);

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
			$query = "SELECT * FROM users WHERE {$pr} = '{$id}'";
			$result = pg_query($query);
			if (pg_num_rows($result) != 1) 
			{
				$state = sha1($_SERVER['HTTP_USER_AGENT'].time());
				$query = "INSERT INTO users (email, {$pr}, apikey) VALUES ('{$email}', '{$id}', '$state') RETURNING id";
				$result = pg_query($query);
				$userid = pg_fetch_result($result, 0, 0);
			}
			else
			{
				$userid = pg_fetch_result($result, 0, 'id');
				$contract = pg_fetch_result($result, 0, 'contract');
			}
			$_SESSION['userid'] = $userid;
			$_SESSION['contract'] = $result ? true : false;
			$_SESSION['auth'] = true;
			pg_free_result($result);
			pg_close($db);
			header("Location: /cabinet/dashboard/");
		}
	}
}

function generateInvoice($summ) {
	global $pdf;

	$pdf->SetCreator(PDF_CREATOR);
	$pdf->SetAuthor('Arsen Bespalov');
	$pdf->SetTitle('CNAM RF Invoice');
	$pdf->SetSubject('Invoice');
	$pdf->SetKeywords('CNAM, invoice');

	$pdf->setPrintHeader(false);
	$pdf->setPrintFooter(false);

	$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
	$pdf->SetMargins(18, 10, 32, true);
	$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
	$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

	$pdf->SetFont('arial', '', 9);
	$pdf->AddPage();
	$html = file_get_contents(__DIR__.'/templates/invoice2.html');
	$pdf->writeHTML($html, true, 0, true, 0);
	$pdf->Image(K_PATH_IMAGES . 'print_trans.png', 21, 145, 40, '', '', '', '', false);
	$pdf->Image(K_PATH_IMAGES . 'sign_trans.png', 60, 105, 40, '', '', '', '', false);
	$pdf->lastPage();
	$pdf->Output('invoice.pdf', 'D');
}

function selectTariff ($tariff) {
	global $conf;
	if (!$tariff) {
		if ($conf['db']['type'] == 'postgres')
		{
			$db = pg_connect("host=".$conf['db']['host'].' dbname='.$conf['db']['database'].' user='.$conf['db']['username'].' password='.$conf['db']['password']) or die('Невозможно подключиться к БД: '.pg_last_error());
			$query = "SELECT tariff FROM users WHERE id = {$_SESSION['userid']}";
			$result = pg_query($query);
			$userTariff = pg_fetch_result($result, 0, 'tariff');
			pg_free_result($result);
			pg_close($db);
			if (!$userTariff) $tariff = array('start' => true);
			else $tariff = array($userTariff => true);
			return $tariff;
		}
	} else {
		return array($tariff => true);
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

function providerUnlink ($provider) {
	global $conf;
	$pr = convertProvider($provider);
	if ($conf['db']['type'] == 'postgres')
	{
		$db = pg_connect("host=".$conf['db']['host'].' dbname='.$conf['db']['database'].' user='.$conf['db']['username'].' password='.$conf['db']['password']) or die('Невозможно подключиться к БД: '.pg_last_error());
		$userid = $_SESSION['userid'];
		$query = "select count(ok)+count(fb)+count(gp)+count(mr)+count(ya)+count(vk) from users where id = {$userid} and (vk != 0 or ok != 0 or fb != 0 or gp != 0 or mr != 0 or ya != 0);";
		$result = pg_query($query);
		$count = pg_fetch_result($result, 0, 0);
		if ($count > 1) {
			$query = "UPDATE users SET {$pr} = NULL WHERE id = {$_SESSION['userid']}";
			$result = pg_query($query);
			pg_free_result($result);
			pg_close($db);
		} else {
			pg_free_result($result);
			pg_close($db);
		}
		header("Location: /cabinet/profile/");
	}
}

function convertProvider ($provider) {
	switch ($provider) {
		case 'facebook':
			return 'fb';
			break;
		case 'vkontakte':
			return 'vk';
			break;
		case 'google-plus':
			return 'gp';
			break;
		case 'odnoklassniki':
			return 'ok';
			break;
		case 'mailru':
			return 'mr';
			break;
		case 'yandex':
			return 'ya';
			break;
	}
}

function getUserLogs() {
	global $conf;
	if ($conf['db']['type'] == 'postgres')
	{
		$db = pg_connect("host=".$conf['db']['host'].' dbname='.$conf['db']['database'].' user='.$conf['db']['username'].' password='.$conf['db']['password']) or die('Невозможно подключиться к БД: '.pg_last_error());
		$userid = $_SESSION['userid'];
		$query = "select phone, debet, credit, modtime, client, ip from log, users where uid = {$userid} limit 100 offset 0";
		$result = pg_query($query);
		$logs_data = array();
		while ($row = pg_fetch_assoc($result))
		{
			$logs_data[]['phone'] = $row['phone'];
			$logs_data[]['debet'] = $row['debet'];
			$logs_data[]['credit'] = $row['credit'];
			$logs_data[]['modtime'] = $row['modtime'];
			$logs_data[]['client'] = $row['client'];
			$logs_data[]['ip'] = $row['ip'];
		}
	}
}

function newAPIKey() {
	global $conf;
	if ($conf['db']['type'] == 'postgres')
	{
		$db = pg_connect("host=".$conf['db']['host'].' dbname='.$conf['db']['database'].' user='.$conf['db']['username'].' password='.$conf['db']['password']) or die('Невозможно подключиться к БД: '.pg_last_error());
		$userid = $_SESSION['userid'];
		$apikey = sha1($_SERVER['HTTP_USER_AGENT'].time());
		$query = "update users set apikey = '{$apikey}' where id = {$userid}";
		pg_query($query);
		pg_close($db);
		header("Location: /cabinet/key/");
	}
}

function userAPIKey() {
	global $conf;
	if ($conf['db']['type'] == 'postgres')
	{
		$db = pg_connect("host=".$conf['db']['host'].' dbname='.$conf['db']['database'].' user='.$conf['db']['username'].' password='.$conf['db']['password']) or die('Невозможно подключиться к БД: '.pg_last_error());
		$userid = $_SESSION['userid'];
		$query = "select apikey from users where id = {$userid}";
		$result = pg_query($query);
		$apikey = pg_fetch_result($result, 0, 'apikey');
		pg_free_result($result);
		pg_close($db);
		return $apikey;
	}
}

function acceptContract($action = true) {
	global $conf;
	if ($conf['db']['type'] == 'postgres')
	{
		$db = pg_connect("host=".$conf['db']['host'].' dbname='.$conf['db']['database'].' user='.$conf['db']['username'].' password='.$conf['db']['password']) or die('Невозможно подключиться к БД: '.pg_last_error());
		$userid = $_SESSION['userid'];
		if ($action) {
			$query = "update users set contract = 1 where id = {$userid}";
			pg_query($query);
			pg_close($db);
			$_SESSION['contract'] = true;
			header("Location: /cabinet/dashboard/");
		} else {
			$query = "delete from users where id = {$userid}";
			pg_query($query);
			pg_close($db);
			session_destroy();
			header("Location: /");
		}
	}
}

function check_auth() {
	if ($_SESSION['auth'] !== true)
		header("Location: /cabinet/");
}

function logout() {
	session_destroy();
	header("Location: /cabinet/");
}
?>