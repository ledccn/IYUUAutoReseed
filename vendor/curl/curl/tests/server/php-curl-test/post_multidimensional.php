<?php

$http_raw_post_data = file_get_contents('php://input');
echo $http_raw_post_data;