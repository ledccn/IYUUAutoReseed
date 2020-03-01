<?php

if (!isset($_SERVER['PHP_AUTH_USER'])) {
	header('WWW-Authenticate: Basic realm="My Realm"');
	header('HTTP/1.0 401 Unauthorized');
	echo 'canceled';
	exit;
}

header('Content-Type: application/json');
echo json_encode(array(
		'username' => $_SERVER['PHP_AUTH_USER'],
		'password' => $_SERVER['PHP_AUTH_PW'],
));