<?php
require_once __DIR__ . '/init.php';
use IYUU\AutoReseed;

AutoReseed::init();
$hashArray = AutoReseed::get();
if (AutoReseed::$move != null) {
    echo "种子移动完毕，请重新编辑配置，再尝试辅种！ \n\n";
    exit;
}
AutoReseed::call($hashArray);
AutoReseed::wechatMessage();
