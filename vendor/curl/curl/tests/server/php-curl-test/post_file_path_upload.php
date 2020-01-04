<?php

$request_method = isset($_SERVER['REQUEST_METHOD']) ?
						$_SERVER['REQUEST_METHOD'] : '';

$data_values = $request_method === 'POST' ? $_POST : $_GET;

$key = isset($data_values['key']) ? $data_values['key'] : '';

$response = array();

$response['request_method'] = $request_method;
$response['key'] = $key;

if(isset($_FILES[$key])) {
	$response['mime_content_type'] = mime_content_type($_FILES[$key]['tmp_name']);
} else {
	$response['mime_content_type'] = 'ERROR';
}

echo json_encode($response);