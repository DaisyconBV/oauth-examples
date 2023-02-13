<?php

require_once __DIR__ . '/pkce.php';
require_once __DIR__ . '/functions.php';

$host = 'https://login.daisycon.com';
$authorizeUri = "{$host}/oauth/authorize";
$accessTokenUri = "{$host}/oauth/access-token";
$options = getopt(
	'c:s:o:r:',
	[
		'clientId:',
		'clientSecret:',
		'outputFile:',
		'redirectUri:',
		'help',
	]
);

if (true === isset($options['help']))
{
	echo 'Usage: ', PHP_EOL,
		'-c | --clientId        (required) provide your client ID here', PHP_EOL,
		'-c | --clientSecret    (optional) provide your client secret here', PHP_EOL,
		'-c | --outputFile      (optional) provide a file to write the JSON tokens to', PHP_EOL,
		'-c | --redirectUri     (required) provide a custom redirect URI', PHP_EOL,
		'--help                 show this help', PHP_EOL;
	exit;
}

$clientId = $options['c'] ?? $options['clientId'] ?? null;
$clientSecret = $options['s'] ?? $options['clientSecret'] ?? null;
$outputFile = $options['o'] ?? $options['outputFile'] ?? null;
$redirectUri = $options['r'] ?? $options['redirectUri'] ?? "${host}/oauth/cli";

if (true === empty($clientId))
{
	echo 'ERROR: Client ID is required', PHP_EOL;
	die;
}

$pkce = new Pkce();
$codeVerifier = $pkce->getCodeVerifier();

$params = http_build_query([
	'client_id'      => $clientId,
	'response_type'  => 'code',
	'redirect_uri'   => $redirectUri,
	'code_challenge' => $pkce->getCodeChallenge(),
]);

echo 'Please open the following URL in your browser, then copy paste the responded "code" back here', PHP_EOL, PHP_EOL, $authorizeUri, '?', $params, PHP_EOL, PHP_EOL;

$code = askForResponse();

$response = httpPost(
	$accessTokenUri,
	[
		'grant_type'    => 'authorization_code',
		'redirect_uri'  => $redirectUri,
		'client_id'     => $clientId,
		'client_secret' => $clientSecret,
		'code'          => $code,
		'code_verifier' => $codeVerifier,
	]
);

$response = json_decode("{\"token_type\":\"Bearer\",\"expires_in\":1800,\"access_token\":\"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1aWQiOjE1NTUyMiwidXNnIjo2LCJwdWJsaXNoZXJzIjpbeyJpZCI6Mjc2OTcsInNlcnZpY2VfZ3JvdXBzIjo0fV0sImlhdCI6MTY3NjI4MTU5MSwiZXhwIjoxNjc2MjgzMzkxLCJpc3MiOiJvYXV0aCIsImF1ZCI6IjEwMiJ9.REjg7IFPnS7HD1taIQKzX2LBz36gnLDiTEJk-XjogEE\",\"refresh_token\":\"def502003bb3138b2e1971efe412ba5f16680f8d0247b74c6b3505f0ef530f8717a3db9adad976e4cf0c23ccb4ced41963dfc9399113e18898b2f69cf15b2bff53e1e932561dedb31b7ff37d4830d7688da44b451b0dab8c7b4786b29b57199d9d9e2dd17878ae0801e06f3bacce6336ed4d8201b16c3dd7830ace55f1ee9486e1795107cdb6b0a048bd211e5e93c521371b16db0cebaa19f2d969b10872623a3963f4e57e9975df615c9aa9a10cca02bd18a7d76460449905ede101ef08c015cf6667a2373e431915c63e5921cb629d7e61e14f60e30075ec4f4e1268e223cf7ec3d09a50cb4a124357d4ee12405583f8b0991b961ef0bfd1fd5b8bb076bd177ce530ac07d7eea63ef5c3521a62c0007cc795be85da8b7c0a36e83e91811b6712b01cfdf1b6d2933c202533316d21baf6e36d30507b3c84b0949279a6f3d10bf0c39b2664dda53d3a9bb1a399af3fcb2138117ce07468bc0afe17e7ac16645b87a633a173a7f51148b8b9d74b00d5f489a92335a12f3e348e1ebeb93a483c81da799dccf30eec1bceeb3032b25c06cb71c1506a1c9901b5ab810f02ab2f4cfccb04dd7289c31cb8383ddcce3ceddc058e6894a84a5f581cc5db7f8074f46e58705d3b5547ea64d57cfdb824b7\"}");

if (true === empty($outputFile))
{
	echo 'Here are your access tokens, save them somewhere safe', PHP_EOL,
	 '{';

	$first = true;
	foreach($response as $key => $value)
	{
		echo ($first ? '' : ',' . PHP_EOL),
			"\t" , '"', $key, '": ', json_encode($value);
		$first = false;
	}
	echo PHP_EOL, '}', PHP_EOL, PHP_EOL;
	exit;
}

file_put_contents($outputFile, json_encode($response));
echo "Tokens written to output file: {$outputFile}\n\n";
exit;

function askForResponse(int $attempt = 0): string
{
	if ($attempt > 3)
	{
		echo 'ERROR: Response code not received', PHP_EOL;
		die;
	}
	echo 'Please enter the response code:', PHP_EOL;
	$fin = fopen('php://stdin', 'r');
	$code = trim(fgets($fin));
	fclose($fin);

	if (empty($code))
	{
		++$attempt;
		return askForResponse($attempt);
	}
	return $code;
}
