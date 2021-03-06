<?php
// стартуем сесиию, нахуй!
session_start();

$start = microtime(true);

// автозагрузчик классов
require_once __DIR__.'/src/vendor/autoload.php';

$conf = json_decode(file_get_contents(__DIR__.'/config.json'), true);

$loader = new Twig_Loader_Filesystem(__DIR__.'/templates');
$twig = new Twig_Environment($loader, array(
	'cache' => __DIR__.'/cache',
	'auto_reload' => true,
));
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false, true);
$sms = new iqsms_json($conf['iqsms']['login'], $conf['iqsms']['password']);

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
	case 'setTariff':
		check_auth();
		setTariff($cmd[1]);
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
	case 'getTariffForDomain':
		echo json_encode(getTariffList($_REQUEST['domain']));
		exit();
	case 'addTariff':
		addTariff();
		break;
	case 'changeTariff':
		updateTariff($cmd[1]);
		break;
	case 'deleteTariff':
		deleteTariff($cmd[1]);
		break;
	case 'getUsers':
		getUsersJSON($cmd[1]);
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
	case 'updateCityBase':
		getCityFrom2GIS();
		break;
	case 'addCity':
		addCity();
		break;
	case 'changeCity':
		updateCity($cmd[1]);
		break;
	case 'deleteCity':
		deleteCity($cmd[1]);
		break;
	case 'uploadRubricsFile':
		uploadRubricsFile();
		break;
	case 'updateRubricsList':
		updateRubricsList($cmd[1]);
		break;
	case 'updateRubric':
		updateRubric($_REQUEST['rubric_id'], $_REQUEST['industry_id']);
		break;
	case 'getRubricList':
		getRubricList($cmd[1]);
		break;
	case 'addPhone':
		check_auth();
		addPhone();
		break;
	case 'confirmPhone':
		check_auth();
		confirmPhone($cmd[1]);
		break;
	case 'deletePhone':
		check_auth();
		deletePhone();
		break;
	case 'admin':
		check_admin();
	case 'dashboard':
	case 'tariff':
	case 'balans':
	case 'profile':
	case 'phonebook':
	case 'key':
	case 'log':
	case 'about':
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
				$total_users = getTotalUsers();
				// $users_data = getUserList();
				$invoices_data = getInvoiceList();
				$city_datas = getCityList();
				$country_datas = getCountryList();
				$total_rubrics = getTotalRubrics();
				$time = microtime(true) - $start;
				$timer = sprintf('%.4F', $time);
				echo $twig->render('admin.html', array(
					'admin' => true,
					'timer' => $timer,
					'is_admin' => $is_admin,
					'tariff_datas' => $tariff_datas,
					'total_users' => $total_users,
					'total_pages' => round($total_users/100),
					// 'users_data' => $users_data,
					'invoices_data' => $invoices_data,
					'city_datas' => $city_datas,
					'country_datas' => $country_datas,
					'total_import' => $_GET['total'],
					'total_insert' => $_GET['total_insert'],
					'total_rubrics' => $total_rubrics
					));
				break;
			case 'tariff':
				$tariff = getTariffList();
				$current = getCurrentTariff();
				if (!$cmd[2]) $cmd[2] = $current;
				if (getUserBalans(true) >= $tariff[$cmd[2]]['sum']) {
					if ($tariff[$cmd[2]]['code'] != $current)
						$tariff_allow = true;
					else
						$tariff_allow = false;
				} else
					$tariff_allow = false;
				$time = microtime(true) - $start;
				$timer = sprintf('%.4F', $time);
				echo $twig->render('tariff.html', array(
					'tariff' => true,
					'timer' => $timer,
					'is_admin' => $is_admin,
					'tariff_datas' => $tariff,
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
			case 'phonebook':
				$phones = getPhoneList($_SESSION['userid']);
				$time = microtime(true) - $start;
				$timer = sprintf('%.4F', $time);
				echo $twig->render('phonebook.html', array(
					'phonebook' => true,
					'timer' => $timer,
					'is_admin' => $is_admin,
					'my_phones' => $phones,
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
				$userSelectID = filter_var($cmd[1], FILTER_VALIDATE_INT);
				$userSelectPage = filter_var($cmd[2], FILTER_VALIDATE_INT);
				$userSelectID = $userSelectID ? $userSelectID : $_SESSION['userid'];
				$userSelectPage = $userSelectPage ? $userSelectPage : 1;
				$userSelectPage2 = $userSelectPage ? 100 * ($userSelectPage - 1) : 0;
				$logs = getUserLogs(100, $userSelectPage2, $userSelectID);
				$pages = getLogsPages($userSelectID);
				$time = microtime(true) - $start;
				$timer = sprintf('%.4F', $time);
				echo $twig->render('log.html', array(
					'log' => true,
					'timer' => $timer,
					'is_admin' => $is_admin,
					'logs_data' => $logs,
					'pages' => array('totalPages' => $pages, 'currentPage' => $userSelectPage, 'uid' => $userSelectID)
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
				$tariff = getTariffList();
				$time = microtime(true) - $start;
				$timer = sprintf('%.4F', $time);
				echo $twig->render('contract.html', array(
					'contract' => true,
					'timer' => $timer,
					'is_admin' => $is_admin,
					'tariff_datas' => $tariff,
					'accept' => $_SESSION['contract']
					));
				break;
			case 'about':
				$time = microtime(true) - $start;
				$timer = sprintf('%.4F', $time);
				echo $twig->render('about.html', array(
					'about' => true,
					'timer' => $timer,
					'is_admin' => $is_admin
					));
				break;
			default:
				$logs = getUserLogs(10);
				$progtrckr_module = progtrckr('module');
				$progtrckr_balans = progtrckr('balans');
				$progtrckr_tariff = progtrckr('tariff');
				$tariff = getTariffList();
				$current = getCurrentTariff();
				$time = microtime(true) - $start;
				$timer = sprintf('%.4F', $time);
				echo $twig->render('dashboard.html', array(
					'dashboard' => true,
					'timer' => $timer,
					'is_admin' => $is_admin,
					'progtrckr_module' => $progtrckr_module,
					'progtrckr_balans' => $progtrckr_balans,
					'progtrckr_tariff' => $progtrckr_tariff,
					'cnam' => $tariff[$current],
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

function setTariff($tariff) {
	global $conf;
	if ($conf['db']['type'] == 'postgres')
	{
		$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
		if (is_numeric($tariff)) {
			$query = "update users set tariffid = {$tariff}, qty = qty + (select queries from tariff where id = {$tariff}) where id = {$_SESSION['userid']} and (select (sum(debet) - sum(credit)) from log where uid = {$_SESSION['userid']}) >= (select sum from tariff where id = {$tariff}) returning id";
			$result = pg_query($query);
			$uid = pg_fetch_result($result, 0, 'id');
			pg_free_result($result);
			if ($uid == $_SESSION['userid']) {
				$query = "insert into log (uid, credit, client) values ({$_SESSION['userid']}, (select sum from tariff where id = {$tariff}), 'Активания тарифа ' || (select name from tariff where id = {$tariff}))";
				pg_query($query);
			}
			pg_close($db);
		}
	}
	header("Location: /cabinet/tariff/#info");
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
		$query = "select qty from users where id = {$_SESSION['userid']}";
		$result = pg_query($query);
		$qty = pg_fetch_result($result, 0, 'qty');
		pg_free_result($result);
		pg_close($db);
	}
	if (!$return)
	{
		$return = array('balans' => $balans, 'qty' => $qty);
		echo json_encode($return);
		exit();
	}
	else
	{
		return $balans;
	}
}

function getUsersJSON($page) {
	global $conf;
	if ($_SESSION['is_admin'] == 't') {
		$offset = ($page - 1) * 100;
		header("Content-Type: text/json");
		echo json_encode(array('users' => getUserList(100, $offset)));
		exit();
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

function getTotalUsers() {
	global $conf;
	if ($_SESSION['is_admin'] == 't') {
		if ($conf['db']['type'] == 'postgres')
		{
			$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
			$query = "select count(1) from users";
			$result = pg_query($query);
			$total_users = pg_fetch_result($result, 0, 0);
		}
		pg_close();
		return $total_users;
	}
}

function getUserList($limit = 100, $offset = 0) {
	global $conf;
	if ($_SESSION['is_admin'] == 't') {
		if ($conf['db']['type'] == 'postgres')
		{
			$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
			// $query = "select t1.id, t1.email, t1.vk, t1.ok, t1.fb, t1.gp, t1.mr, t1.ya, t1.company, t1.is_admin, t2.name as tariff, t3.name as tariff2, (select (sum(debet) - sum(credit)) as balans from log where uid = t1.id and modtime >= current_timestamp - interval '62 days') as balans from users as t1 left join tariff as t2 on t1.tariffid = t2.id left join tariff as t3 on t1.tariffid2 = t3.id order by balans desc nulls last, t1.id desc limit {$limit} offset {$offset}";
			$query = "select t1.id, t1.email, t1.vk, t1.ok, t1.fb, t1.gp, t1.mr, t1.ya, t1.company, t1.is_admin, t2.name as tariff, t3.name as tariff2, (select (sum(debet) - sum(credit)) as balans from log where uid = t1.id) as balans from users as t1 left join tariff as t2 on t1.tariffid = t2.id left join tariff as t3 on t1.tariffid2 = t3.id order by balans desc nulls last, t1.id desc limit {$limit} offset {$offset}";
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
				$users_data[$i]['tariff2'] = $row['tariff2'];
				$users_data[$i]['balans'] = is_null($row['balans']) ? $row['balans'] : number_format($row['balans'], 2, '.', ' ');
				$i++;
			}
			pg_free_result($result);
			pg_close($db);
			return $users_data;
		}
	}
}

function getTariffList($domain = 'cnamrf.ru') {
	global $conf;
	if ($conf['db']['type'] == 'postgres')
	{
		$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
		// $domain = pg_escape_string($_SERVER['SERVER_NAME']);
		// $domain = preg_replace('/^www\./', '', $domain);
		$query = "select * from tariff where domain = '{$domain}' order by sum asc";
		$result = pg_query($query);
		$tariffInfo = array();
		while ($row = pg_fetch_assoc($result)) {
			$tariffInfo[$row['code']]['id'] = $row['id'];
			$tariffInfo[$row['code']]['name'] = $row['name'];
			$tariffInfo[$row['code']]['code'] = $row['code'];
			$tariffInfo[$row['code']]['desc'] = $row['description'];
			$tariffInfo[$row['code']]['price'] = $row['price'];
			$tariffInfo[$row['code']]['qty'] = $row['queries'];
			$tariffInfo[$row['code']]['sum'] = $row['sum'];
			$tariffInfo[$row['code']]['domain'] = $row['domain'];
		}
	}
	return $tariffInfo;
}

function addTariff() {
	global $conf;
	if ($_SESSION['is_admin'] == 't') {
		if ($conf['db']['type'] == 'postgres')
		{
			$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
			$domain = pg_escape_string($_POST['tariffDomain']);
			$name = pg_escape_string($_POST['tariffName']);
			$price = pg_escape_string($_POST['tariffPrice']);
			$qty = pg_escape_string($_POST['tariffQty']);
			$sum = pg_escape_string($_POST['tariffSum']);
			$code = pg_escape_string($_POST['tariffCode']);
			$desc = pg_escape_string($_POST['tariffDescription']);
			$query = "insert into tariff (domain, name, price, queries, sum, code, description) values ('{$domain}', '{$name}', '{$price}', '{$qty}', '{$sum}', '{$code}', '{$desc}')";
			pg_query($query);
			pg_close($db);
		}
		header("Location: /cabinet/admin/#tariff");
	}
}

function updateTariff($id) {
	global $conf;
	if ($_SESSION['is_admin'] == 't') {
		if ($conf['db']['type'] == 'postgres')
		{
			$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
			$domain = pg_escape_string($_POST['tariffDomain']);
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

function getCityFrom2GIS() {
	global $conf;
	if ($conf['db']['type'] == 'postgres') {
		$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
		$query = "select apikey from users where id = {$_SESSION['userid']}";
		$result = pg_query($query);
		$apikey = pg_fetch_result($result, 0, 0);
		$result = json_decode(file_get_contents('http://api.cnamrf.ru/get2GisCities/?apikey='.$apikey));
	}
	header("Location: /cabinet/admin/?total={$result->total}&total_insert={$result->total_insert}#2gis-city");
}

function getCountryList() {
	global $conf;
	if ($conf['db']['type'] == 'postgres') {
		$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
		$query = "select * from country order by sort, name";
		$result = pg_query($query);
		$country = array(); $i = 1;
		while ($row = pg_fetch_assoc($result)) {
			$country[$i]['id'] = $row['id'];
			$country[$i]['name'] = $row['name'];
			$i++;
		}
		pg_free_result($result);
		pg_close($db);
	}
	return $country;
}

function getCityList() {
	global $conf;
	if ($conf['db']['type'] == 'postgres') {
		$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
		$query = "select city01.*, city02.name as parent, country.name as country from cities as city01 left outer join cities as city02 on city01.parent_id = city02.id left join country on city01.country_id = country.id order by name asc";
		$result = pg_query($query);
		$cities = array(); $i = 1;
		while ($row = pg_fetch_assoc($result)) {
			$cities[$i]['id'] = $row['id'];
			$cities[$i]['name'] = $row['name'];
			$cities[$i]['date'] = date('d.m.Y', strtotime($row['modtime']));
			$cities[$i]['manual'] = $row['manual'];
			$cities[$i]['country_id'] = $row['country_id'];
			$cities[$i]['parent_id'] = $row['parent_id'];
			$cities[$i]['parent'] = $row['parent'];
			$cities[$i]['country'] = $row['country'];
			$i++;
		}
		pg_free_result($result);
		pg_close($db);
	}
	return $cities;
}

function addCity() {
	global $conf;
	if ($_SESSION['is_admin'] == 't') {
		if ($conf['db']['type'] == 'postgres')
		{
			$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
			$name = pg_escape_string($_POST['cityName']);
			$parent_id = $_POST['cityParent'];
			$country_id = $_POST['cityCountry'];
			$query = "insert into cities (name, manual, parent_id, country_id) values ('{$name}', true, {$parent_id}, {$country_id})";
			pg_query($query);
			pg_close($db);
		}
		header("Location: /cabinet/admin/#2gis-city");
	}
}

function updateCity($id) {
	global $conf;
	if ($_SESSION['is_admin'] == 't') {
		if ($conf['db']['type'] == 'postgres')
		{
			$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
			$name = pg_escape_string($_POST['cityName']);
			$parent_id = $_POST['cityParent'];
			$country_id = $_POST['cityCountry'];
			$query = "update cities set name = '{$name}', parent_id = {$parent_id}, country_id = {$country_id} where id = {$id}";
			pg_query($query);
			pg_close($db);
		}
		header("Location: /cabinet/admin/#2gis-city");
	}
}

function deleteCity($id) {
	global $conf;
	if ($_SESSION['is_admin'] == 't') {
		if ($conf['db']['type'] == 'postgres')
		{
			$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
			$query = "delete from cities where id = {$id} and manual = true";
			pg_query($query);
			pg_close($db);
			header("Location: /cabinet/admin/#2gis-city");
		}
	}
}

function uploadRubricsFile() {
	global $conf;
	$ds = DIRECTORY_SEPARATOR;
	$storeFolder = 'uploads';
	if ($_SESSION['is_admin'] == 't') 
	{
		if (!empty($_FILES)) 
		{
			$tmpFile = $_FILES['file']['tmp_name'];
			$targetPath = dirname(__FILE__).$ds.'..'.$ds.$storeFolder.$ds;
			$extractDir = dirname(__FILE__).$ds.'..'.$ds.md5(rand()).$ds;
			$targetFile = $targetPath.$_FILES['file']['name'];
			move_uploaded_file($tmpFile, $targetFile);
			$zip = new ZipArchive();
			$zip->open($targetFile);
			$zip->extractTo($extractDir);
			$strings = simplexml_load_file($extractDir.'xl/sharedStrings.xml');
			$sheet = simplexml_load_file($extractDir.'xl/worksheets/sheet1.xml');
			$sharedSettingsArr = array();
			foreach ($strings->children() as $item) {
				$sharedSettingsArr[] = (string)$item->t;
			}
			$row = 0;
			foreach($sheet->sheetData->row as $item) {
				$out[$row] = array();
				$cell = 0;
				foreach($item as $child) {
					$attr = $child->attributes();
					$value = isset($child->v) ? (string)$child->v : false;
					$out[$row][$cell] = isset($attr['t']) ? $sharedSettingsArr[$value] : $value;
					$cell++;
				}
				$row++;
			}
			if ($conf['db']['type'] == 'postgres')
			{
				if (count($out)) {
					$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
					$query = "TRUNCATE TABLE rubrics RESTART IDENTITY CASCADE";
					pg_query($query);
					for ($i = 1; $i < count($out); $i++) {
						if ($out[$i][0]) {
							$translit = translit($out[$i][0]);
							$translit = strtoupper($translit);
							$translit = preg_replace('~[^-A-Z0-9_]+~u', '-', $translit);
							$translit = trim($translit, '-');
							$query = "insert into rubrics (name, translit) values ('{$out[$i][0]}', '{$translit}') returning id";
							$result = pg_query($query);
							$idLevel1 = pg_fetch_result($result, 0, 'id');
							$translit = translit($out[$i][1]);
							$translit = strtoupper($translit);
							$translit = preg_replace('~[^-A-Z0-9_]+~u', '-', $translit);
							$translit = trim($translit, '-');
							$query = "insert into rubrics (name, translit, parent) values ('{$out[$i][1]}', '{$translit}', {$idLevel1}) returning id";
							$result = pg_query($query);
							$idLevel2 = pg_fetch_result($result, 0, 'id');
							$translit = translit($out[$i][2]);
							$translit = strtoupper($translit);
							$translit = preg_replace('~[^-A-Z0-9_]+~u', '-', $translit);
							$translit = trim($translit, '-');
							$query = "insert into rubrics (name, translit, parent) values ('{$out[$i][2]}', '{$translit}', {$idLevel2})";
							pg_query($query);
						} else {
							if ($out[$i][1]) {
								$translit = translit($out[$i][1]);
								$translit = strtoupper($translit);
								$translit = preg_replace('~[^-A-Z0-9_]+~u', '-', $translit);
								$translit = trim($translit, '-');
								$query = "insert into rubrics (name, translit, parent) values ('{$out[$i][1]}', '{$translit}', {$idLevel1}) returning id";
								$result = pg_query($query);
								$idLevel2 = pg_fetch_result($result, 0, 'id');
								$translit = translit($out[$i][2]);
								$translit = strtoupper($translit);
								$translit = preg_replace('~[^-A-Z0-9_]+~u', '-', $translit);
								$translit = trim($translit, '-');
								$query = "insert into rubrics (name, translit, parent) values ('{$out[$i][2]}', '{$translit}', {$idLevel2})";
								pg_query($query);
							} else {
								$translit = translit($out[$i][2]);
								$translit = strtoupper($translit);
								$translit = preg_replace('~[^-A-Z0-9_]+~u', '-', $translit);
								$translit = trim($translit, '-');
								$query = "insert into rubrics (name, translit, parent) values ('{$out[$i][2]}', '{$translit}', {$idLevel2})";
								pg_query($query);
							}
						}
					}
					pg_free_result($result);
					pg_close($db);
				}
			}
			getRubricList();
		} else {
			$response = array('rubrics' => 'FUCK!');
		}
		echo json_encode($response, JSON_UNESCAPED_UNICODE);
		exit();
	}
}

function getRubricList() {
	global $conf;
	$rubrics = array();
	if ($conf['db']['type'] == 'postgres') {
		$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
		$query = "select id, name, translit, parent from rubrics";
		$result = pg_query($query);
		$i = 0;
		while ($row = pg_fetch_assoc($result)) {
			$rubrics[$i]['id'] = $row['id'];
			$rubrics[$i]['name'] = $row['name'];
			$rubrics[$i]['translit'] = $row['translit'];
			$rubrics[$i]['parent'] = $row['parent'];
			$i++;
		}
		pg_free_result($result);
		pg_close($db);
	}
	header("Content-Type: text/json");
	echo json_encode($rubrics, JSON_UNESCAPED_UNICODE);
	exit();
}

function getTotalRubrics() {
	global $conf;
	$total = 0;
	if ($conf['db']['type'] == 'postgres') {
		$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
		$query = "select count(id) from rubrics";
		$result = pg_query($query);
		$total = pg_fetch_result($result, 0, 0);
	}
	return $total;
}

function acceptInvoice($num) {
	global $conf;
	if ($_SESSION['is_admin'] == 't' && is_numeric($num)) {
		if ($conf['db']['type'] == 'postgres') {
			$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
			$query = "select uid, sum from invoices where id = {$num}";
			$result = pg_query($query);
			$uid = pg_fetch_result($result, 0, 'uid');
			$sum = pg_fetch_result($result, 0, 'sum');
			$query = "insert into log (uid, debet, client, invoice) values ({$uid}, '{$sum}', 'Банк. Счет № CNAM-', {$num})";
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
			$query = "select invoices.id, invoices.invoice, invoices.sum, invoices.addtime, invoices.system, users.company, log.invoice as accept from invoices left join users on invoices.uid = users.id left join log on invoices.id = log.invoice where system = 'bank' order by addtime desc limit {$limit} offset {$offset}";
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
	
	$performedDatetime = date(DATE_W3C);

	$message = 'Что-то пошло не так!';
	$techMessage = 'Вернитесь назад и попробуйте снова. Возможно на этапе проведения платежа потерялось часть данных.';
	
	$shopId = $conf['payments']['ShopID'];
	$shopPassword = $conf['payments']['ShopPassword'];

	$yaAction = $_POST['action'];
	$yaOrderSumAmount = $_POST['orderSumAmount'];
	$yaOrderSumCurrencyPaycash = $_POST['orderSumCurrencyPaycash'];
	$yaOrderSumBankPaycash = $_POST['orderSumBankPaycash'];
	$yaShopId = $_POST['shopId'];
	$yaInvoiceId = $_POST['invoiceId'];
	$yaCustomerNumber = $_POST['customerNumber'];
	$yaMD5 = $_POST['md5'];
	$yaPaymentType = $_POST['paymentType'];

	switch ($yaPaymentType) {
		case 'PC':
			$client = 'Яндекс.Деньги: Счет № ';
			break;
		case 'AC':
			$client = 'Банковская карта: Счет № ';
			break;
		case 'MC':
			$client = 'Мобильный телефон: Счет № ';
			break;
		case 'GP':
			$client = 'Наличные: Счет № ';
			break;
		case 'WM':
			$client = 'WebMoney: Счет № ';
			break;
		case 'SB':
			$client = 'Сбербанк: Счет № ';
			break;
		case 'AB':
			$client = 'Альфа-Клик: Счет № ';
			break;
		case 'МА':
			$client = 'MasterPass: Счет № ';
			break;
		case 'PB':
			$client = 'Промсвязьбанк: Счет № ';
			break;
	}

	header('Content-Type: application/xml');
	$response = '<?xml version="1.0" encoding="UTF-8"?>'."\n";

	if ($cmd == 'check')
	{
		$checkOrderStr = array(
			$yaAction,
			$yaOrderSumAmount,
			$yaOrderSumCurrencyPaycash,
			$yaOrderSumBankPaycash,
			$shopId,
			$yaInvoiceId,
			$yaCustomerNumber,
			$shopPassword);
		$md5 = strtoupper(md5(implode(';', $checkOrderStr)));

		if ($md5 != $yaMD5) {
			$code = '100';
		} else {
			$code = '0';
			if ($conf['db']['type'] == 'postgres') {
				$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
				$system = 'yamoney:'.$_POST['paymentType'];
				$query = "insert into invoices (invoice, uid, sum, system) values ({$yaInvoiceId}, {$yaCustomerNumber}, {$yaOrderSumAmount}, '{$system}')";
				$result = pg_query($query);
				$iid = pg_fetch_result($result, 0, 'id');
				pg_free_result($result);
				$query = "insert into log (uid, debet, client, invoice) values ({$yaInvoiceId}, {$yaOrderSumAmount}, '{$client}', {$iid})";
				pg_query($query);
				pg_close($db);
			}
		}

		if ($code) {
			$error_msg = "message=\"{$message}\" techMessage=\"{$techMessage}\"";
		}

		$response .= "<checkOrderResponse performedDatetime=\"{$performedDatetime}\" code=\"{$code}\" invoiceId=\"{$yaInvoiceId}\" shopId=\"{$yaShopId}\" {$error_msg} />";
	} 
	elseif ($cmd == 'aviso') 
	{
		if ($conf['db']['type'] == 'postgres') {
			$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
			$query = "select id, uid, invoice, sum from invoices where uid = {$yaCustomerNumber} and invoice = {$yaInvoiceId} and sum = {$yaOrderSumAmount}";
			file_put_contents('query.log', $query);
			$result = pg_query($query);
			$iid = pg_fetch_result($result, 0, 'id');
			$uid = pg_fetch_result($result, 0, 'uid');
			$invoice = pg_fetch_result($result, 0, 'invoice');
			$sum = pg_fetch_result($result, 0, 'sum');
			pg_free_result($result);
			if ($iid) {
				$checkOrderStr = array(
					$yaAction,
					number_format($sum, 2, '.', ''),
					$yaOrderSumCurrencyPaycash,
					$yaOrderSumBankPaycash,
					$shopId,
					$invoice,
					$uid,
					$shopPassword);
				$md5 = strtoupper(md5(implode(';', $checkOrderStr)));
				if ($md5 != $yaMD5) {
					$code = '1';
				} else {
					$code = '0';
					$query = "insert into log (uid, debet, client, invoice) values ({$uid}, {$sum}, '{$client}', {$iid})";
					pg_query($query);
				}
			} else {
				$code = '200';
			}
			pg_close($db);
		}
		$response .= "<paymentAvisoResponse performedDatetime=\"{$performedDatetime}\" code=\"{$code}\" invoiceId=\"{$yaInvoiceId}\" shopId=\"{$yaShopId}\"/>";
	}
	echo $response;
	exit();
}

function progtrckr($step) {
	if ($step == 'module')
	{
		if (getModuleConnect())
			return 'done';
		else
			return 'todo';
	}
	elseif ($step == 'tariff')
	{
		if (getCurrentTariff() == 'start')
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

function getModuleConnect() {
	global $conf;
	$officialModules = array('FreePBX', 'Lead4CRM');
	if ($conf['db']['type'] == 'postgres')
	{
		$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
		foreach ($officialModules as $module) {
			$query = "select count(client) from log where uid = {$_SESSION['userid']} and client = '{$module}'";
			$result = pg_query($query);
			$count = pg_fetch_result($result, 0, 0);
			if ($count) {
				$return = true;
				break;
			}
		}
	}
	return $return ? $return : false;
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
		case 'facebook': return 'fb'; break;
		case 'vkontakte': return 'vk'; break;
		case 'google-plus': return 'gp'; break;
		case 'odnoklassniki': return 'ok'; break;
		case 'mailru': return 'mr'; break;
		case 'yandex': return 'ya'; break;
	}
}

function addPhone() {
	global $conf;
	if ($conf['db']['type'] == 'postgres')
	{
		$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
		$uPhone = $_POST['phoneNumber'];
		$uPhone = preg_replace('/[+()-\s]/', '', $uPhone);
		$uName = $_POST['phoneName'];
		$uTranslit = $_POST['phoneTranslit'];
		if (is_numeric($uPhone)) {
			$uName = strip_tags($uName);
			$uName = pg_escape_string($uName);
			$uTranslit = strip_tags($uTranslit);
			$uTranslit = pg_escape_string($uTranslit);
			$query = "insert into phonebook (phone, name, translit, uid) values ({$uPhone}, '{$uName}', '{$uTranslit}', {$_SESSION['userid']})";
			pg_query($query);
			pg_close($db);
		}
	}
	header("Location: /cabinet/phonebook/");
}

function confirmPhone($cmd) {
	global $conf, $sms;
	$balans = getUserBalans(true);
	if ($conf['db']['type'] == 'postgres')
	{
		$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
		if ($cmd == 'sendSMS') {
			$uPhone = $_POST['phoneNumber'];
			$uPhone = preg_replace('/[+()-\s]/', '', $uPhone);
			if (is_numeric($uPhone) && $balans > 2) {
				$query = "select code from phonebook where phone = {$uPhone} and uid = {$_SESSION['userid']} and sms + (30 * interval '1 minute') < now()";
				$result = pg_query($query);
				$code = pg_fetch_result($result, 0, 'code');
				pg_free_result($result);
				if ($code) {
					$message = array(
						array(
							'clientId' => '1',
							'phone' => $uPhone,
							'text' => $code,
							'sender' => 'CNAM RF'
						)
					);
					$result = $sms->send($message, 'cnamQueue');
					if ($result['status'] == 'ok') {
						$query = "update phonebook set sms = now() where phone = {$uPhone} and uid = {$_SESSION['userid']}";
						pg_query($query);
						$query = "insert into log (uid, phone, credit, client) values ({$_SESSION['userid']}, {$uPhone}, 2, 'Подтверждение номера по СМС')";
						pg_query($query);
						echo '200';
					} else {
						echo '500';
					}
				} else {
					echo '500';
				}
			}
			pg_close($db);
			exit();
		} elseif ($cmd == 'callPSTN') {
			$uPhone = $_POST['phoneNumber'];
			$uPhone = preg_replace('/[+()-\s]/', '', $uPhone);
			if (is_numeric($uPhone) && $balans > 2) {
				$query = "select code from phonebook where phone = {$uPhone} and uid = {$_SESSION['userid']} and pstn + (30 * interval '1 minute') < now()";
				$result = pg_query($query);
				$code = pg_fetch_result($result, 0, 'code');
				pg_free_result($result);
				if ($code) {
					
					$voximplant = "https://api.voximplant.com/platform_api/StartScenarios/";
					$voximplant.= "?account_id=" . $conf['voximplant']['account_id'];
					$voximplant.= "&api_key=" . $conf['voximplant']['api_key'];
					$voximplant.= "&rule_id=292118";
					$voximplant.= "&script_custom_data={$uPhone}:{$code}";
					$result = json_decode(file_get_contents($voximplant));
					if ($result->result) {
						$query = "update phonebook set pstn = now() where phone = {$uPhone} and uid = {$_SESSION['userid']}";
						pg_query($query);
						$query = "insert into log (uid, phone, credit, client) values ({$_SESSION['userid']}, {$uPhone}, 2, 'Подтверждение номера по звонку')";
						pg_query($query);
						echo '200';
					} else {
						echo '500';
					}
				}
			}
			pg_close($db);
			exit();
		} elseif ($cmd == 'checkCode') {
			$uCode = $_POST['phoneCode'];
			$uCode = preg_replace('/[\s]/', '', $uCode);
			if (is_numeric($uCode)) {
				$query = "update phonebook set verify = true where code = {$uCode} and uid = {$_SESSION['userid']}";
				pg_query($query);
			}
			pg_close($db);
			header("Location: /cabinet/phonebook/");
		}
	}
}

function deletePhone() {
	global $conf;
	if ($conf['db']['type'] == 'postgres')
	{
		$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
		$uPhone = $_POST['phoneNumber'];
		$uPhone = preg_replace('/[+()-\s]/', '', $uPhone);
		if (is_numeric($uPhone)) {
			$query = "delete from phonebook where phone = {$uPhone} and uid = {$_SESSION['userid']}";
			pg_query($query);
			pg_close($db);
		}
	}
	header("Location: /cabinet/phonebook/");
}

function getPhoneList($userid = 0, $limit = 100, $offset = 0) {
	global $conf;
	$phones_masks = json_decode(file_get_contents(__DIR__.'/../js/phones-ru.json'));
	for ($i = 0; $i < count($phones_masks); $i++) {
		$pattern = "/\((\d{4})\)|\((\d{5})\)/";
		preg_match($pattern, $phones_masks[$i]->mask, $mask[$i]);
		unset($mask[$i][0]);
	}
	rsort($mask, SORT_NUMERIC);
	unset($phones_masks);

	if ($conf['db']['type'] == 'postgres')
	{
		$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
		if ($userid)
			$query = "select * from phonebook where uid = {$userid} order by phone asc limit {$limit} offset {$offset}";
		else
			$query = "select * from phonebook order by phone asc limit {$limit} offset {$offset}";
		$result = pg_query($query);
		$phones = array();
		while ($row = pg_fetch_assoc($result)) {
			for ($i = 0; $i < count($mask); $i++) {
				if (substr($row['phone'], 1, 5) == $mask[$i][2]) {
					$phone = '+7 (' . $mask[$i][2] . ') ' . substr($row['phone'], 6, 1) . '-' . 
						substr($row['phone'], 7, 2) . '-'. substr($row['phone'], 9, 2);
					break;
				} elseif (substr($row['phone'], 1, 4) == $mask[$i][1]) {
					$phone = '+7 (' . $mask[$i][1] . ') ' . substr($row['phone'], 5, 2) . '-' . 
						substr($row['phone'], 7, 2) . '-' . substr($row['phone'], 9, 2);
					break;
				} else {
					$phone = '+7 (' . substr($row['phone'], 1, 3) . ') ' . substr($row['phone'], 4, 3) . '-' .
						substr($row['phone'], 7, 2) . '-' . substr($row['phone'], 9, 2);
				}
			}
			$phones[$row['id']]['id'] = $row['id'];
			$phones[$row['id']]['phone'] = $phone;
			$phones[$row['id']]['name'] = $row['name'];
			$phones[$row['id']]['translit'] = $row['translit'];
			$phones[$row['id']]['uid'] = $row['uid'];
			$phones[$row['id']]['code'] = $row['code'];
			$phones[$row['id']]['verify'] = $row['verify'];
		}
	}
	return $phones;
}

function getUserLogs($limit = 100, $offset = 0, $uid) {
	global $conf;
	$phones_masks = json_decode(file_get_contents(__DIR__.'/../js/phones-ru.json'));
	for ($i = 0; $i < count($phones_masks); $i++) {
		$pattern = "/\((\d{4})\)|\((\d{5})\)/";
		preg_match($pattern, $phones_masks[$i]->mask, $mask[$i]);
		unset($mask[$i][0]);
	}
	rsort($mask, SORT_NUMERIC);
	unset($phones_masks);

	if ($conf['db']['type'] == 'postgres')
	{
		$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
		$query = "select log.id, log.phone, log.text, log.debet, log.credit, log.modtime, coalesce(log.client || invoices.invoice, log.client) as new_client, log.ip, cnam_cache.cp_id, cnam_cache.cp_hash, cnam_cache.lon, cnam_cache.lat from log left join invoices on log.invoice = invoices.id left join cnam_cache on log.id = cnam_cache.logid where log.uid = {$uid} order by log.modtime desc limit {$limit} offset {$offset}";
		$result = pg_query($query);
		$logs_data = array(); $i = 0;
		while ($row = pg_fetch_assoc($result))
		{
			if ($row['phone']) {
				for ($x = 0; $x < count($mask); $x++) {
					if (substr($row['phone'], 1, 5) == $mask[$x][2]) {
						$phone = '+7 (' . $mask[$x][2] . ') ' . substr($row['phone'], 6, 1) . '-' . 
							substr($row['phone'], 7, 2) . '-'. substr($row['phone'], 9, 2);
						break;
					} elseif (substr($row['phone'], 1, 4) == $mask[$x][1]) {
						$phone = '+7 (' . $mask[$x][1] . ') ' . substr($row['phone'], 5, 2) . '-' . 
							substr($row['phone'], 7, 2) . '-' . substr($row['phone'], 9, 2);
						break;
					} else {
						$phone = '+7 (' . substr($row['phone'], 1, 3) . ') ' . substr($row['phone'], 4, 3) . '-' .
							substr($row['phone'], 7, 2) . '-' . substr($row['phone'], 9, 2);
					}
				}
			} else $phone = '';
			if ($row['cp_id'] && $row['cp_hash'])
			{
				$query_cp = "select json from cnam_cp where id = {$row['cp_id']} and hash = '".$row['cp_hash']."'";
				$result_cp = pg_query($query_cp);
				$cp_json = pg_fetch_result($result_cp, 0, 'json');
				$logs_data[$i]['cp'] = print_r(json_decode($cp_json), true);
			}
			if ($row['lon'] && $row['lat'])
			{
				$query_geo = "select json from geodata where lon = '".$row['lon']."' and lat = '".$row['lat']."'";
				$result_geo = pg_query($query_geo);
				$gd_json = pg_fetch_result($result_geo, 0, 'json');
				$logs_data[$i]['geo'] = print_r(json_decode($gd_json), true);
			}
			$logs_data[$i]['id'] = $row['id'];
			$logs_data[$i]['phone'] = $phone;
			$logs_data[$i]['query'] = $row['text'];
			$logs_data[$i]['debet'] = number_format($row['debet'], 2, '.', ' ');
			$logs_data[$i]['credit'] = number_format($row['credit'], 2, '.', ' ');
			$logs_data[$i]['modtime'] = date('d.m.Y H:i:s', strtotime($row['modtime']));
			$logs_data[$i]['client'] = $row['new_client'];
			$logs_data[$i]['ip'] = long2ip($row['ip']);
			$i++;
		}
		pg_free_result($result);
		pg_close($db);
		return $logs_data;
	}
}

function getLogsPages($uid) {
	global $conf;
	if ($conf['db']['type'] == 'postgres')
	{
		$db = pg_connect('dbname='.$conf['db']['database']) or die('Невозможно подключиться к БД: '.pg_last_error());
		$query = "select count(1) from log where uid = {$uid}";
		$result = pg_query($query);
		$countRows = pg_fetch_result($result, 0, 0);
	}
	pg_free_result($result);
	pg_close($db);
	return ceil($countRows / 100);
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

function translit($st) {
  $cyr  = array('а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я','А','Б','В','Г','Д','Е','Ё','Ж','З','И','Й','К','Л','М','Н','О','П','Р','С','Т','У','Ф','Х','Ц','Ч','Ш','Щ','Ъ','Ы','Ь','Э','Ю','Я');
  $lat = array( 'a','b','v','g','d','e','yo','zh','z','i','j','k','l','m','n','o','p','r','s','t','u','f' ,'x' ,'c' ,'ch','sh' ,'shh' ,'','y','','e','yu' ,'ya','A','B','V','G','D','E','Yo','Zh','Z','I','Y','K','L','M','N','O','P','R','S','T','U','F','X','C','Ch','Sh' ,'Shh','','Y','','E','Yu','Ya');
  return str_replace($cyr, $lat, $st);
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