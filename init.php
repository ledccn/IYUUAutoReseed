<?php
//----------------------------------
// 公共入口文件
//----------------------------------
// 定义目录
defined('ROOT_PATH') or define("ROOT_PATH", __DIR__);
define('DS', DIRECTORY_SEPARATOR);
define('TORRENT_PATH', ROOT_PATH.DS.'torrent'.DS);

// 严格开发模式
error_reporting(E_ALL);
#ini_set('display_errors', 1);

// 永不超时
ini_set('max_execution_time', 0);
set_time_limit(0);

// 内存限制，如果外面设置的内存比 /etc/php/php-cli.ini 大，就不要设置了
if (intval(ini_get("memory_limit")) < 1024) {
    ini_set('memory_limit', '1024M');
}

if (PHP_SAPI != 'cli') {
    exit("You must run the CLI environment\n");
}

// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 系统配置
if (file_exists(ROOT_PATH."/config/config.php")) {
    // 配置（全局变量）
    $configALL = require_once ROOT_PATH . "/config/config.php";
} else {
    // 示例配置
    $configALL = require_once ROOT_PATH . '/config/config.sample.php';
}
require_once ROOT_PATH . '/vendor/autoload.php';

global $argv;
$start_file = str_replace("\\","/",trim($argv[0]));
if( substr($start_file,-8)==="init.php" ){
    require_once __DIR__ . '/iyuu.php';
}
