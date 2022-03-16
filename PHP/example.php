<?php

require_once __DIR__ . '/pkce.php';
require_once __DIR__ . '/functions.php';

// Start: PKCE challenge request
session_start();
$clientId = '';
$clientSecret = '';
$redirectUri = '';


$pkce = new Pkce();
$_SESSION['code_verifier'] = $pkce->getCodeVerifier();

$params = [
	'client_id'      => $clientId,
	'response_type'  => 'code',
	'redirect_uri'   => $redirectUri,
	'code_challenge' => $pkce->getCodeChallenge()
];

$authorizeUri = 'https://login.daisycon.com/oauth/authorize?' . http_build_query($params);

header('Location: ' . $authorizeUri);




// Authorized redirect URI code
session_start();
$response = httpPost(
	'https://login.daisycon.com/oauth/access-token',
	[
		'grant_type'    => 'authorization_code',
		'redirect_uri'  => $redirectUri,
		'client_id'     => $clientId,
		'client_secret' => $clientSecret,
		'code'          => $_GET['code'],
		'code_verifier' => $_SESSION['code_verifier'],
	]
);
var_dump($response);
