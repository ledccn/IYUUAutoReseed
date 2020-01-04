<?php
$request_method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';
$data_values = $request_method === 'POST' ? $_POST : $_GET;
$test = isset($data_values['test']) ? $data_values['test'] : '';
$key = isset($data_values['key']) ? $data_values['key'] : '';

if ($test === 'put_file_handle') {
    $tmp_filename = tempnam('/tmp', 'php-curl-class.');
    file_put_contents($tmp_filename, file_get_contents('php://input'));
    echo mime_content_type($tmp_filename);
    unlink($tmp_filename);
    exit;
}

header('Content-Type: text/plain');

$data_mapping = array(
    'cookie' => '_COOKIE',
    'delete' => '_GET',
    'post' => '_POST',
    'put' => '_GET',
    'server' => '_SERVER',
);

if(isset($data_mapping[$test])) {
    $data = ${$data_mapping[$test]};
    $value = isset($data[$key]) ? $data[$key] : '';
echo $value;
} else {
    echo "Error.";
}
