<?php
//----------------------------------
// 公共入口文件
//----------------------------------
// 定义目录
defined('ROOT_PATH') or define("ROOT_PATH",  dirname(__DIR__));
defined('APP_PATH') or define('APP_PATH', __DIR__);
define('DS', DIRECTORY_SEPARATOR);
define('TORRENT_PATH', APP_PATH.DS.'torrent'.DS);

// 严格开发模式
error_reporting( E_ALL );
#ini_set('display_errors', 1);

// 永不超时
ini_set('max_execution_time', 0);
set_time_limit(0);
// 内存限制，如果外面设置的内存比 /etc/php/php-cli.ini 大，就不要设置了
if (intval(ini_get("memory_limit")) < 1024)
{
    ini_set('memory_limit', '1024M');
}

if( PHP_SAPI != 'cli' )
{
    exit("You must run the CLI environment\n");
}

// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 系统配置
if( file_exists( APP_PATH."/config/config.php" ) )
{
    // 配置（全局变量）
    $configALL = require_once APP_PATH."/config/config.php";
}else{
    // 示例配置
    $configALL = require_once APP_PATH . '/config/config.sample.php';
}

require_once ROOT_PATH . '/vendor/autoload.php';
require_once APP_PATH . '/Class/File.php';		// 文件操作类
require_once APP_PATH . '/Class/Function.php';	// 函数
require_once APP_PATH . '/Class/Rpc.php';		// RPC操作类
require_once APP_PATH . '/Class/TransmissionRPC.class.php';	// transmission
require_once APP_PATH . '/Class/qBittorrent.php';			// qBittorrent
require_once APP_PATH . '/Class/uTorrent.php';				// uTorrent
require_once APP_PATH . '/Class/Bencode.php';	// Bencode编码Bittorrent种子操作类

function auto_load($classname) {
    set_include_path(APP_PATH.'/Protocols/');
    spl_autoload($classname);
}

spl_autoload_extensions('.php');
spl_autoload_register('auto_load');
