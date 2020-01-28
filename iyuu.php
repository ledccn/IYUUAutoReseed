<?php
require_once __DIR__ . '/init.php';
use IYUU\AutoReseed;
echo microtime(true).' IYUU自动辅种正在初始化...'.PHP_EOL;
AutoReseed::init();
$hashArray = AutoReseed::get();
AutoReseed::call($hashArray);
exit(0);