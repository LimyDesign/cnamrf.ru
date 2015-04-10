<?php
// script errors will be send to this email:
$error_mail = "me@arsen.pw";

function run() {
	global $rawInput;

	// read config.json
	$config_filename = 'config.json';
	if (!file_exists($config_filename)) {
		throw new Exception("Can't find " . $config_filename, 1);
	}
	$config = json_decode(file_get_contents($config_filename), true);

	$postBody = $_POST['payload'];
	$payload = json_decode($postBody);

	if (isset($config['email'])) {
		$headers  = 'From: ' . $config['email']['from'] . "\r\n";
		$headers .= 'CC: ' . $payload->pusher->email . "\r\n";
		$headers .= "MIME-Version: 1.0\r\n";
		$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
	}

	// check if the request comes from github server
	$github_ips = array('207.97.227.253', '50.57.128.197', '108.171.174.178', '50.57.231.61');
	if (in_array($_SERVER['REMOTE_ADDR'], $github_ips)) {
		foreach ($config['endpoints'] as $endpoint) {
			// check if the push came from the right repository and branch
			if ($payload->repository->url == 'https://github.com/'.$endpoint['repo'] &&
				$payload->ref == 'refs/heads/'.$endpoint['branch'])
			{
				// execute update script, and record its output
				ob_start();
				passthru($endpoint['run']);
				$output = ob_end_contents();

				// prepare and send the notification email
				if (isset($config['email']))
				{
					// send mail to someone, and the github user who pushed the commit
					$body = '<p>The Github user <a href="https://github.com/' . $payload->pusher->name . '">@' . $payload->pusher->name . '</a> has pushed to ' . $payload->repository->url . ' and consquently, ' . $endpoint['action'] . '</p><p>Here\'s a brief list of what has been changed:</p><ul>';
					foreach ($payload->commits as $commit) {
						$body .= '<li>'.$commit->message.'<br/><small style="color:#999">added: <b>'.count($commit->added).'</b> &nbsp; modified: <b>'.count($commit->modified).'</b> &nbsp; removed: <b>'.count($commit->removed).'</b> &nbsp; <a href="'.$commit->url.'">read more</a><small></li>';
					}
					$body .= '</ul><p>What follows is the output of the script:</p><pre>'.$output.'</pre><p>Cheers, <br/>Github Webhook Endpoint</p>';
					mail($config['email']['to'], $endpoint['action'], $body, $headers);
				}
				return true;
			}
		}
	} else {
		throw new Exception("This does not appear to be a valid requests from Github. Webhook from IP: " . $_SERVER['REMOTE_ADDR'], 2);
	}
}

try {
	if (!isset($_POST['payload'])) {
		echo "Works fine.";
	} else {
		run();
	}
} catch ( Exception $e ) {
	$msg = $e->getMessage();
	mail($error_mail, $msg, ''.$e);
}

?>