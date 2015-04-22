<?php
// стартуем сесиию, нахуй!
session_start();

$start = microtime(true);

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
		check_auth();
		providerUnlink($cmd[1]);
		break;
	case 'newkey':
		check_auth();
		newAPIKey();
		break;
	case 'accept':
		check_auth();
		acceptContract();
		break;
	case 'not-accept':
		check_auth();
		acceptContract(false);
		break;
	case 'invoice':
		check_auth();
		generateInvoice($_POST['invoice']);
		break;
	case 'getUserBalans':
		check_auth();
		getUserBalans();
		break;
	case 'payment':
		yandexPayments($cmd[1]);
		break;
	case 'addTariff':
		addTariff();
		break;
	case 'changeTariff':
		updateTariff($cmd[1]);
		break;
	case 'deleteTariff':
		deleteTariff($cmd[1]);
		break;
	case 'changeUser':
		updateUser($cmd[1]);
		break;
	case 'deleteUser':
		deleteUser($cmd[1]);
		break;
	case 'acceptInvoice':
		acceptInvoice($cmd[1]);
		break;
	case 'withdrawInvoice':
		withdrawInvoice($cmd[1]);
		break;
	case 'admin':
		check_admin();
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
	if ($_SESSION['contract'] == 't') 
	{
		$is_admin = $_SESSION['is_admin'];
		switch ($cmd[0]) {
			case 'admin':
				$tariff_datas = getTariffList();
				$users_data = getUserList();
				$invoices_data = getInvoiceList();
				$time = microtime(true) - $start;
				$timer = sprintf('%.4F', $time);
				echo $twig->render('admin.html', array(
					'admin' => true,
					'timer' => $timer,
					'is_admin' => $is_admin,
					'tariff_datas' => $tariff_datas,
					'users_data' => $users_data,
					'invoices_data' => $invoices_data,
					));
				break;
			case 'tariff':
				$tariff = getTariffInfo();
				$current = getCurrentTariff();
				if (!$cmd[2]) $cmd[2] = $current;
				if (getUserBalans(true) >= $tariff[$cmd[2]]['sum']) {
					if ($tariff[$cmd[2]]['code'] != $current)
						$tariff_allow = true;
					else
						$tariff_allow = false;
				} else
					$tariff_allow = false;
				// $cnam = selectTariff($cmd[2]);
				// $current = currentTariff();
				$time = microtime(true) - $start;
				$timer = sprintf('%.4F', $time);
				echo $twig->render('tariff.html', array(
					'tariff' => true,
					'timer' => $timer,
					'is_admin' => $is_admin,
					'cnam' => $tariff[$cmd[2]],
					'current' => $current,
					'tariff_allow' => $tariff_allow,
					));
				break;
			case 'balans':
				if ($cmd[1] == 'fail') $fail = true;
				$time = microtime(true) - $start;
				$timer = sprintf('%.4F', $time);
				echo $twig->render('balans.html', array(
					'balans' => true,
					'timer' => $timer,
					'is_admin' => $is_admin,
					'yaShopId' => $conf['payments']['ShopID'],
					'yaSCId' => $conf['payments']['SCID'],
					'userid' => $_SESSION['userid'],
					'company_name' => $_SESSION['company'],
					'fail' => $fail,
					));
				break;
			case 'profile':
				$fbq = login_query('facebook');
				$vkq = login_query('vkontakte');
				$gpq = login_query('google-plus');
				$okq = login_query('odnoklassniki');
				$mrq = login_query('mailru');
				$yaq = login_query('yandex');
				$checkProviderLink = checkProviderLink();
				$time = microtime(true) - $start;
				$timer = sprintf('%.4F', $time);
				echo $twig->render('profile.html', array(
					'profile' 		=> true,
					'timer' 		=> $timer,
					'is_admin' 		=> $is_admin,
					'userid' 		=> $_SESSION['userid'],
					'fb_link' 		=> 'https://www.facebook.com/dialog/oauth?' . $fbq,
					'vk_link' 		=> 'https://oauth.vk.com/authorize?' .  $vkq,
					'gp_link' 		=> 'https://accounts.google.com/o/oauth2/auth?' .  $gpq,
					'ok_link' 		=> 'http://www.odnoklassniki.ru/oauth/authorize?' .  $okq,
					'mr_link' 		=> 'https://connect.mail.ru/oauth/authorize?' .  $mrq,
					'ya_link' 		=> 'https://oauth.yandex.ru/authorize?' .  $yaq,
					'facebook' 		=> $checkProviderLink['fb'],
					'vkontakte' 	=> $checkProviderLink['vk'],
					'googleplus' 	=> $checkProviderLink['gp'],
					'odnoklassniki'	=> $checkProviderLink['ok'],
					'mailru' 		=> $checkProviderLink['mr'],
					'yandex' 		=> $checkProviderLink['ya'],
					));
				break;
			case 'key':
				$apikey = userAPIKey();
				$time = microtime(true) - $start;
				$timer = sprintf('%.4F', $time);
				echo $twig->render('key.html', array(
					'key' => true,
					'timer' => $timer,
					'is_admin' => $is_admin,
					'apikey' => $apikey
					));
				break;
			case 'log':
				$logs = getUserLogs();
				$time = microtime(true) - $start;
				$timer = sprintf('%.4F', $time);
				echo $twig->render('log.html', array(
					'log' => true,
					'timer' => $timer,
					'is_admin' => $is_admin,
					'logs_data' => $logs
					));
				break;
			case 'support':
				$time = microtime(true) - $start;
				$timer = sprintf('%.4F', $time);
				echo $twig->render('support.html', array(
					'support' => true,
					'timer' => $timer,
					'is_admin' => $is_admin,
					));
				break;
			case 'contract':
				$time = microtime(true) - $start;
				$timer = sprintf('%.4F', $time);
				echo $twig->render('contract.html', array(
					'contract' => true,
					'timer' => $timer,
					'is_admin' => $is_admin,
					'accept' => $_SESSION['contract']
					));
				break;
			default:
				$logs = getUserLogs(10);
				$progtrckr_module = progtrckr('module');
				$progtrckr_balans = progtrckr('balans');
				$progtrckr_tariff = progtrckr('tariff');
				$time = microtime(true) - $start;
				$timer = sprintf('%.4F', $time);
				echo $twig->render('dashboard.html', array(
					'dashboard' => true,
					'timer' => $timer,
					'is_admin' => $is_admin,
					'progtrckr_module' => $progtrckr_module,
					'progtrckr_balans' => $progtrckr_balans,
					'progtrckr_tariff' => $progtrckr_tariff,
					'logs_data' => $logs,
					));
				break;
		}
	} else {
		$time = microtime(true) - $start;
		$timer = sprintf('%.4F', $time);
		echo $twig->render('contract.html', array(
			'contract' => true,
			'timer' => $timer,
			'is_admin' => $is_admin,
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
	if (isset($_SERVER['HTTPS'])) {
		if ($_SERVER['HTTPS'] == 'on') {
			$protocol = 'https://';
		} else {
			$protocol = 'http://';
		}
	} else {
		$protocol = 'http://';
	}
	$redirect_uri = rawurlencode($protocol.$_SERVER['SERVER_NAME'].'/cabinet/auth/'.$provider.'/');
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
	if (isset($_SERVER['HTTPS'])) {
		if ($_SERVER['HTTPS'] == 'on') {
			$protocol = 'https://';
		} else {
			$protocol = 'http://';
		}
	} else {
		$protocol = 'http://';
	}
	$redirect_uri = $protocol.$_SERVER['SERVER_NAME'].'/cabinet/auth/'.$provider.'/';
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
		$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
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
				$query = "INSERT INTO users (email, {$pr}, apikey) VALUES ('{$email}', '{$id}', '$state') RETURNING id, contract";
				$result = pg_query($query);
				$userid = pg_fetch_result($result, 0, 'id');
				$contract = pg_fetch_result($result, 0, 'contract');
			}
			else
			{
				$userid = pg_fetch_result($result, 0, 'id');
				$contract = pg_fetch_result($result, 0, 'contract');
				$company = pg_fetch_result($result, 0, 'company');
				$is_admin = pg_fetch_result($result, 0, 'is_admin');
			}
			$_SESSION['userid'] = $userid;
			$_SESSION['contract'] = $contract;
			$_SESSION['company'] = $company;
			$_SESSION['is_admin'] = $is_admin;
			$_SESSION['auth'] = true;
			pg_free_result($result);
			pg_close($db);
			header("Location: /cabinet/dashboard/");
		}
	}
}

function getUserBalans($return = false) {
	global $conf;
	if ($conf['db']['type'] == 'postgres')
	{
		$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
		$query = "select (sum(debet) - sum(credit)) as balans from log where uid = {$_SESSION['userid']}";
		$result = pg_query($query);
		$balans = pg_fetch_result($result, 0, 'balans');
		$balans = $balans ? $balans : '0';
		pg_free_result($result);
		pg_close($db);
	}
	if (!$return)
	{
		echo $balans;
		exit();
	}
	else
	{
		return $balans;
	}
}

function updateUser($id) {
	global $conf;
	if ($_SESSION['is_admin'] == 't') {
		if ($conf['db']['type'] == 'postgres')
		{
			$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
			if ($_POST['admin'] == 't') 
				$query = "update users set is_admin = true where id = {$id}";
			else 
				$query = "update users set is_admin = false where id = {$id}";
			pg_query($query);
			pg_close($db);
			header("Location: /cabinet/admin/#users");
		}
	}
}

function deleteUser($id) {
	global $conf;
	if ($_SESSION['is_admin'] == 't') {
		if ($conf['db']['type'] == 'postgres') {
			$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
			$query = "select count(debet) + count(credit) from logs where uid = {$id} and modtime >= current_timestamp - interval '62 days'";
			$result = pg_query($query);
			$ops = pg_fetch_result($result, 0, 0);
			pg_free_result($result);
			if ($ops == 0) {
				$query = "delete from users where id = {$id}";
				pg_query($query);
			}
			pg_close($db);
			header("Location: /cabinet/admin/#users");
		}
	}
}

function getUserList($limit = 100, $offset = 0) {
	global $conf;
	if ($_SESSION['is_admin'] == 't') {
		if ($conf['db']['type'] == 'postgres')
		{
			$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
			$query = "select users.id, users.email, users.vk, users.ok, users.fb, users.gp, users.mr, users.ya, users.company, users.is_admin, tariff.name as tariff, (select (sum(debet) - sum(credit)) as balans from log where uid = users.id and modtime >= current_timestamp - interval '62 days') as balans from users left join tariff on users.tariffid = tariff.id order by balans desc nulls last limit {$limit} offset {$offset}";
			$result = pg_query($query);
			$users_data = array(); $i = 0;
			while ($row = pg_fetch_assoc($result)) {
				$users_data[$i]['id'] = $row['id'];
				$users_data[$i]['email'] = $row['email'];
				$users_data[$i]['vk'] = $row['vk'];
				$users_data[$i]['ok'] = $row['ok'];
				$users_data[$i]['fb'] = $row['fb'];
				$users_data[$i]['gp'] = $row['gp'];
				$users_data[$i]['mr'] = $row['mr'];
				$users_data[$i]['ya'] = $row['ya'];
				$users_data[$i]['company'] = $row['company'];
				$users_data[$i]['admin'] = $row['is_admin'];
				$users_data[$i]['tariff'] = $row['tariff'];
				$users_data[$i]['balans'] = $row['balans'];
				$i++;
			}
			pg_free_result($result);
			pg_close($db);
			return $users_data;
		}
	}
}

function addTariff() {
	global $conf;
	if ($_SESSION['is_admin'] == 't') {
		if ($conf['db']['type'] == 'postgres')
		{
			$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
			$domain = pg_escape_string($_SERVER['SERVER_NAME']);
			$name = pg_escape_string($_POST['tariffName']);
			$price = pg_escape_string($_POST['tariffPrice']);
			$qty = pg_escape_string($_POST['tariffQty']);
			$sum = pg_escape_string($_POST['tariffSum']);
			$code = pg_escape_string($_POST['tariffCode']);
			$desc = pg_escape_string($_POST['tariffDescription']);
			$query = "insert into tariff (domain, name, price, queries, sum, code, description) values ('{$domain}', '{$name}', '{$price}', '{$qty}', '{$sum}', '{$code}', '{$desc}')";
			pg_query($query);
			pg_close($db);
			header("Location: /cabinet/admin/#tariff");
		}
	}
}

function updateTariff($id) {
	global $conf;
	if ($_SESSION['is_admin'] == 't') {
		if ($conf['db']['type'] == 'postgres')
		{
			$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
			$domain = pg_escape_string($_SERVER['SERVER_NAME']);
			$name = pg_escape_string($_POST['tariffName']);
			$price = pg_escape_string($_POST['tariffPrice']);
			$qty = pg_escape_string($_POST['tariffQty']);
			$sum = pg_escape_string($_POST['tariffSum']);
			$code = pg_escape_string($_POST['tariffCode']);
			$desc = pg_escape_string($_POST['tariffDescription']);
			$query = "update tariff set name = '{$name}', price = {$price}, queries = {$qty}, sum = {$sum}, code = '{$code}', description = '{$desc}' where id = {$id}";
			pg_query($query);
			pg_close($db);
			header("Location: /cabinet/admin/#tariff");
		}
	}
}

function deleteTariff($id) {
	global $conf;
	if ($_SESSION['is_admin'] == 't') {
		if ($conf['db']['type'] == 'postgres')
		{
			$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
			$query = "delete from tariff where id = {$id}";
			pg_query($query);
			pg_close($db);
			header("Location: /cabinet/admin/#tariff");
		}
	}
}

function getTariffList() {
	global $conf;
	if ($conf['db']['type'] == 'postgres')
	{
		$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
		$domain = pg_escape_string($_SERVER['SERVER_NAME']);
		$query = "select * from tariff where domain = '{$domain}' order by sum asc";
		$result = pg_query($query);
		$tariff_datas = array(); $i = 0;
		while ($row = pg_fetch_assoc($result)) {
			$tariff_datas[$i]['id'] = $row['id'];
			$tariff_datas[$i]['name'] = $row['name'];
			$tariff_datas[$i]['code'] = $row['code'];
			$tariff_datas[$i]['desc'] = $row['description'];
			$tariff_datas[$i]['price'] = $row['price'];
			$tariff_datas[$i]['qty'] = $row['queries'];
			$tariff_datas[$i]['sum'] = $row['sum'];
			$i++;
		}
		pg_free_result($result);
		pg_close($db);
	}
	return $tariff_datas;
}

function getTariffInfo() {
	global $conf;
	if ($conf['db']['type'] == 'postgres')
	{
		$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
		$domain = pg_escape_string($_SERVER['SERVER_NAME']);
		$query = "select * from tariff where domain = '{$domain}' order by sum asc";
		$result = pg_query($query);
		$tariffInfo = array();
		while ($row = pg_fetch_assoc($result)) {
			$tariffInfo[$row['code']]['name'] = $row['name'];
			$tariffInfo[$row['code']]['code'] = $row['code'];
			$tariffInfo[$row['code']]['desc'] = $row['description'];
			$tariffInfo[$row['code']]['price'] = $row['price'];
			$tariffInfo[$row['code']]['qty'] = $row['queries'];
			$tariffInfo[$row['code']]['sum'] = $row['sum'];
		}
	}
	return $tariffInfo;
}

function getCurrentTariff($field = 'code') {
	global $conf;
	if ($conf['db']['type'] == 'postgres')
	{
		$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
		$query = "select tariff.{$field} from users left join tariff on users.tariffid = tariff.id where users.id = {$_SESSION['userid']}";
		$result = pg_query($query);
		$tariff = pg_fetch_result($result, 0, $field);
		if (!$tariff) $tariff = 'start';
		pg_free_result($result);
		pg_close($db);
	}
	return $tariff;
}

function getTariffPrice($code) {
	global $conf;
	if ($conf['db']['type'] == 'postgres') {
		$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
		$query = "select sum from tariff where code = '{$code}'";
		$result = pg_query($query);
		$price = pg_fetch_result($result, 0, 'sum');
		pg_free_result($result);
		pg_close($db);
	}
	return $price;
}

function acceptInvoice($num) {
	global $conf;
	if ($_SESSION['is_admin'] == 't' && is_numeric($num)) {
		if ($conf['db']['type'] == 'postgres') {
			$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
			$query = "select sum from invoices where id = {$num}";
			$result = pg_query($query);
			$sum = pg_fetch_result($result, 0, 'sum');
			$query = "insert into log (uid, debet, client, invoice) values ({$_SESSION['userid']}, '{$sum}', 'Банк. Счет № CNAM-', {$num})";
			pg_query($query);
			pg_free_result($result);
			pg_close($db);
			header("Location: /cabinet/admin/#invoices");
		}
	}
}

function withdrawInvoice($num) {
	global $conf;
	if ($_SESSION['is_admin'] == 't' && is_numeric($num)) {
		if ($conf['db']['type'] == 'postgres') {
			$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
			$query = "delete from log where invoice = {$num}";
			pg_query($query);
			pg_close($db);
			header("Location: /cabinet/admin/#invoices");
		}
	}
}

function getInvoiceList($limit = 100, $offset = 0) {
	global $conf;
	if ($_SESSION['is_admin'] == 't') {
		if ($conf['db']['type'] == 'postgres') {
			$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
			$query = "select invoices.id, invoices.invoice, invoices.sum, invoices.addtime, invoices.system, users.company, log.invoice as accept from invoices left join users on invoices.uid = users.id left join log on invoices.id = log.invoice order by addtime desc limit {$limit} offset {$offset}";
			$result = pg_query($query);
			$invoices_data = array(); $i++;
			while ($row = pg_fetch_assoc($result)) {
				$invoices_data[$i]['id'] = $row['id'];
				$invoices_data[$i]['invoice'] = $row['invoice'];
				$invoices_data[$i]['sum'] = $row['sum'];
				$invoices_data[$i]['addtime'] = $row['addtime'];
				$invoices_data[$i]['system'] = $row['system'];
				$invoices_data[$i]['company'] = $row['company'];
				$invoices_data[$i]['accept'] = $row['accept'];
				$i++;
			}
			pg_free_result($result);
			pg_close($db);
			return $invoices_data;
		}
	}
}

function yandexPayments($cmd) {
	global $conf;
	if ($cmd == 'check')
	{
		header('Content-Type: application/xml');
		
		$performedDatetime = date(DATE_W3C);
		$shopId = $conf['payments']['ShopID'];
		$shopPassword = $conf['payments']['ShopPassword'];
		if ($shopId != $_POST['invoiceId'])
			$code = '100';
		else 
			$code = '0';
		$invoiceId = $_POST['invoiceId'];

		$checkOrderStr = array(
			'checkOrder',
			$_POST['orderSumAmount'],
			$_POST['orderSumCurrencyPaycash'],
			$_POST['orderSumBankPaycash'],
			$shopId,
			$invoiceId,
			$_SESSION['userid'],
			$shopPassword);
		$md5 = md5(implode(';', $checkOrderStr));

		$response .= '<?xml version="1.0" encoding="UTF-8"?>'."\n";
		$response .= "<checkOrderResponse performedDatetime=\"{$performedDatetime}\" code=\"{$code}\" invoiceId=\"{$invoiceId}\" shopId=\"{$_POST['shopId']}\" message=\"Нам денег не надо!\" techMessage=\"Идите гуляйте! Хватит задродствовать! А мы и так справимся, без твоих денег!\"/>";
		echo $response;
	}
	exit();
}

function progtrckr($step) {
	if ($step == 'module')
	{
		return 'todo';
	}
	elseif ($step == 'tariff')
	{
		if (array_key_exists('start', getCurrentTariff()))
			return 'todo';
		else
			return 'done';
	}
	elseif ($step == 'balans')
	{
		if (getUserBalans(true) > 0)
			return 'done';
		else
			return 'todo';
	}
}

function setUserCompany($company) {
	global $conf;
	if ($conf['db']['type'] == 'postgres')
	{
		$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
		$company = pg_escape_string($company);
		$query = "update users set company = '{$company}' where id = {$_SESSION['userid']}";
		pg_query($query);
		pg_close($db);
		$_SESSION['company'] = $company;
	}
	return $_SESSION['company'];
}

function generateInvoice($summ) {
	global $pdf, $twig, $conf;

	$pdf->SetCreator('CNAM RF');
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

	$user_sum = str_replace(',', '.', $_POST['invoice']);
	$user_sum = str_replace(' ', '', $user_sum);
	$sum = number_format($user_sum, 2, '-', ' ');
	$sum_alt = number_format($user_sum, 2, '.', '\'');
	$invoice_num = date('ymdHis').rand(0,9);
	$html = $twig->render('invoice.html', array(
		'invoice_number' => 'CNAM-'.writeInvoice($invoice_num, $user_sum),
		'invoice_date' => russian_date().' г.',
		'client_company' => setUserCompany($_POST['company-name']),
		'userid' => $_SESSION['userid'],
		'price' => $sum,
		'summ' => $sum,
		'summ_alt' => $sum_alt,
		'total' => $sum,
		'summ_text' => mb_ucfirst(num2str($user_sum))
		));
	$pdf->writeHTML($html, true, 0, true, 0);
	$pdf->Image(K_PATH_IMAGES . 'print_trans.png', 21, 140, 40, '', '', '', '', false);
	$pdf->Image(K_PATH_IMAGES . 'sign_trans.png', 50, 124, 60, '', '', '', '', false);
	$pdf->lastPage();
	$pdf->Output('invoice.pdf', 'D');
}

function writeInvoice($num, $sum, $system = 'bank') {
	global $conf;
	if ($conf['db']['type'] == 'postgres') {
		$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
		$query = "insert into invoices (invoice, uid, sum, system) values ({$num}, {$_SESSION['userid']}, '{$sum}', '{$system}')";
		pg_query($query);
		pg_close($db);
	}
	return $num;
}

function checkProviderLink() {
	global $conf;
	if ($conf['db']['type'] == 'postgres')
	{
		$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
		$query = "SELECT vk, ok, fb, gp, mr, ya FROM users WHERE id = {$_SESSION['userid']}";
		$result = pg_query($query);
		while ($row = pg_fetch_assoc($result)) {
			$provider['vk'] = $row['vk'];
			$provider['ok']	= $row['ok'];
			$provider['fb'] = $row['fb'];
			$provider['gp'] = $row['gp'];
			$provider['mr'] = $row['mr'];
			$provider['ya'] = $row['ya'];
		}
		pg_free_result($result);
		pg_close($db);
		return $provider;
	}
}

function providerUnlink ($provider) {
	global $conf;
	$pr = convertProvider($provider);
	if ($conf['db']['type'] == 'postgres')
	{
		$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
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

function getUserLogs($limit = 100, $offset = 0) {
	global $conf;
	if ($conf['db']['type'] == 'postgres')
	{
		$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
		$userid = $_SESSION['userid'];
		$query = "select log.phone, log.debet, log.credit, log.modtime, (log.client || invoices.invoice) as new_client, log.ip from log left join invoices on log.invoice = invoices.id where log.uid = {$userid} order by log.modtime desc limit {$limit} offset {$offset}";
		$result = pg_query($query);
		$logs_data = array(); $i = 0;
		while ($row = pg_fetch_assoc($result))
		{
			$logs_data[$i]['phone'] = $row['phone'];
			$logs_data[$i]['debet'] = number_format($row['debet'], 2, '.', ' ');
			$logs_data[$i]['credit'] = number_format($row['credit'], 2, '.', ' ');
			$logs_data[$i]['modtime'] = date('d.m.Y H:i:s', strtotime($row['modtime']));
			$logs_data[$i]['client'] = $row['new_client'];
			$logs_data[$i]['ip'] = $row['ip'];
			$i++;
		}
		pg_free_result($result);
		pg_close($db);
		return $logs_data;
	}
}

function newAPIKey() {
	global $conf;
	if ($conf['db']['type'] == 'postgres')
	{
		$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
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
		$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
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
		$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
		$userid = $_SESSION['userid'];
		if ($action) {
			$query = "update users set contract = true where id = {$userid}";
			pg_query($query);
			pg_close($db);
			$_SESSION['contract'] = 't';
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

function russian_date() {
	$date = explode('.',date('d.m.Y'));
	switch ($date[1]) {
		case 1: $m = 'января'; break;
		case 2: $m = 'февраля'; break;
		case 3: $m = 'марта'; break;
		case 4: $m = 'апреля'; break;
		case 5: $m = 'мая'; break;
		case 6: $m = 'июня'; break;
		case 7: $m = 'июля'; break;
		case 8: $m = 'августа'; break;
		case 9: $m = 'сентября'; break;
		case 10: $m = 'октября'; break;
		case 11: $m = 'ноября'; break;
		case 12: $m = 'декабря'; break;
	}
	return $date[0].' '.$m.' '.$date[2];
}

function num2str($num) {
    $nul='ноль';
    $ten=array(
        array('','один','два','три','четыре','пять','шесть','семь', 'восемь','девять'),
        array('','одна','две','три','четыре','пять','шесть','семь', 'восемь','девять'),
    );
    $a20=array('десять','одиннадцать','двенадцать','тринадцать','четырнадцать' ,'пятнадцать','шестнадцать','семнадцать','восемнадцать','девятнадцать');
    $tens=array(2=>'двадцать','тридцать','сорок','пятьдесят','шестьдесят','семьдесят' ,'восемьдесят','девяносто');
    $hundred=array('','сто','двести','триста','четыреста','пятьсот','шестьсот', 'семьсот','восемьсот','девятьсот');
    $unit=array( // Units
        array('копейка' ,'копейки' ,'копеек',	 1),
        array('рубль'   ,'рубля'   ,'рублей'    ,0),
        array('тысяча'  ,'тысячи'  ,'тысяч'     ,1),
        array('миллион' ,'миллиона','миллионов' ,0),
        array('миллиард','милиарда','миллиардов',0),
    );
    //
    list($rub,$kop) = explode('.',sprintf("%015.2f", floatval($num)));
    $out = array();
    if (intval($rub)>0) {
        foreach(str_split($rub,3) as $uk=>$v) { // by 3 symbols
            if (!intval($v)) continue;
            $uk = sizeof($unit)-$uk-1; // unit key
            $gender = $unit[$uk][3];
            list($i1,$i2,$i3) = array_map('intval',str_split($v,1));
            // mega-logic
            $out[] = $hundred[$i1]; # 1xx-9xx
            if ($i2>1) $out[]= $tens[$i2].' '.$ten[$gender][$i3]; # 20-99
            else $out[]= $i2>0 ? $a20[$i3] : $ten[$gender][$i3]; # 10-19 | 1-9
            // units without rub & kop
            if ($uk>1) $out[]= morph($v,$unit[$uk][0],$unit[$uk][1],$unit[$uk][2]);
        } //foreach
    }
    else $out[] = $nul;
    $out[] = morph(intval($rub), $unit[1][0],$unit[1][1],$unit[1][2]); // rub
    $out[] = $kop.' '.morph($kop,$unit[0][0],$unit[0][1],$unit[0][2]); // kop
    return trim(preg_replace('/ {2,}/', ' ', join(' ',$out)));
}

function morph($n, $f1, $f2, $f5) {
    $n = abs(intval($n)) % 100;
    if ($n>10 && $n<20) return $f5;
    $n = $n % 10;
    if ($n>1 && $n<5) return $f2;
    if ($n==1) return $f1;
    return $f5;
}

function mb_ucfirst($str, $encoding='UTF-8') {
   $str = mb_ereg_replace('^[\ ]+', '', $str);
   $str = mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding).
          mb_substr($str, 1, mb_strlen($str), $encoding);
   return $str;
}

function check_admin() {
	if ($_SESSION['is_admin'] == 'f')
		header("Location: /cabinet/dashboard/");
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