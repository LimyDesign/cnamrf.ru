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
	$github_ips = v4CIDRtoMask('192.30.252.0/22');
	$ips_data = ipv4Breakout($github_ips[0], $github_ips[1]);
	$first_ip = sprintf("%u", $ips_data['first_host']);
	$last_ip = sprintf("%u", $ips_data['last_host']);
	$webhook_ip = sprintf("%u", ip2long($_SERVER['REMOTE_ADDR']));
	// if ($last_ip > $webhook_ip && $webhook_ip > $first_ip) {
		foreach ($config['endpoints'] as $endpoint) {
			// check if the push came from the right repository and branch
			if ($payload->repository->url == 'https://github.com/'.$endpoint['repo'] &&
				$payload->ref == 'refs/heads/'.$endpoint['branch'])
			{
				// execute update script, and record its output
				ob_start();
				passthru($endpoint['run']);
				$output = ob_get_contents();
				ob_end_clean();

				// prepare and send the notification email
				if (isset($config['email']))
				{
					// send mail to someone, and the github user who pushed the commit
					$body = '<p>The Github user <a href="https://github.com/' . $payload->pusher->name . '">@' . $payload->pusher->name . '</a> has pushed to ' . $payload->repository->url . ' and consquently, ' . $endpoint['action'] . '</p><p>Here\'s a brief list of what has been changed:</p><ul>';
					foreach ($payload->commits as $commit) {
						$body .= '<li>'.$commit->message.'<br/><small style="color:#999">added: <b>'.count($commit->added).'</b> &nbsp; modified: <b>'.count($commit->modified).'</b> &nbsp; removed: <b>'.count($commit->removed).'</b> &nbsp; <a href="'.$commit->url.'">read more</a></small></li>';
					}
					$body .= '</ul><p>What follows is the output of the script:</p><pre>'.$output.'</pre><p>Cheers, <br/>Github Webhook Endpoint</p>';
					mail($config['email']['to'], $endpoint['action'], $body, $headers);
				}
				return $output;
			}
		}
	// } else {
	// 	throw new Exception("This does not appear to be a valid requests from Github. Webhook from IP: " . $_SERVER['REMOTE_ADDR'], 2);
	// }
}

function v4CIDRtoMask($cidr) {
    $cidr = explode('/', $cidr);
    return array($cidr[0], long2ip(-1 << (32 - (int)$cidr[1])));
}

function ipv4Breakout ($ip_address, $ip_nmask) {
    $hosts = array();
    //convert ip addresses to long form
    $ip_address_long = ip2long($ip_address);
    $ip_nmask_long = ip2long($ip_nmask);

    //caculate network address
    $ip_net = $ip_address_long & $ip_nmask_long;

    //caculate first usable address
    $ip_host_first = ((~$ip_nmask_long) & $ip_address_long);
    $ip_first = ($ip_address_long ^ $ip_host_first) + 1;

    //caculate last usable address
    $ip_broadcast_invert = ~$ip_nmask_long;
    $ip_last = ($ip_address_long | $ip_broadcast_invert) - 1;

    //caculate broadcast address
    $ip_broadcast = $ip_address_long | $ip_broadcast_invert;

    // foreach (range($ip_first, $ip_last) as $ip) {
    //         array_push($hosts, $ip);
    // }

    $block_info = array("network" => "$ip_net", 
    	"first_host" => "$ip_first", 
    	"last_host" => "$ip_last", 
    	"broadcast" => "$ip_broadcast");

    return $block_info;
}

try {
	if (!isset($_POST['payload'])) {
		echo "Works fine.";
	} else {
		echo run();
	}
} catch ( Exception $e ) {
	$msg = $e->getMessage();
	echo $msg;
	mail($error_mail, $msg, ''.$e);
}

?>