<?php
/**

IIIIIIIIIIYYYYYYY       YYYYYYYUUUUUUUU     UUUUUUUUUUUUUUUU     UUUUUUUU
I::::::::IY:::::Y       Y:::::YU::::::U     U::::::UU::::::U     U::::::U
I::::::::IY:::::Y       Y:::::YU::::::U     U::::::UU::::::U     U::::::U
II::::::IIY::::::Y     Y::::::YUU:::::U     U:::::UUUU:::::U     U:::::UU
  I::::I  YYY:::::Y   Y:::::YYY U:::::U     U:::::U  U:::::U     U:::::U
  I::::I     Y:::::Y Y:::::Y    U:::::D     D:::::U  U:::::D     D:::::U
  I::::I      Y:::::Y:::::Y     U:::::D     D:::::U  U:::::D     D:::::U
  I::::I       Y:::::::::Y      U:::::D     D:::::U  U:::::D     D:::::U
  I::::I        Y:::::::Y       U:::::D     D:::::U  U:::::D     D:::::U
  I::::I         Y:::::Y        U:::::D     D:::::U  U:::::D     D:::::U
  I::::I         Y:::::Y        U:::::D     D:::::U  U:::::D     D:::::U
  I::::I         Y:::::Y        U::::::U   U::::::U  U::::::U   U::::::U
II::::::II       Y:::::Y        U:::::::UUU:::::::U  U:::::::UUU:::::::U
I::::::::I    YYYY:::::YYYY      UU:::::::::::::UU    UU:::::::::::::UU
I::::::::I    Y:::::::::::Y        UU:::::::::UU        UU:::::::::UU
IIIIIIIIII    YYYYYYYYYYYYY          UUUUUUUUU            UUUUUUUUU

 */
// 定义目录
defined('ROOT_PATH') or define("ROOT_PATH", __DIR__);
define('DS', DIRECTORY_SEPARATOR);
defined('APP_PATH') or define('APP_PATH', ROOT_PATH.DS.'app'.DS);
define('TORRENT_PATH', ROOT_PATH.DS.'torrent'.DS);

// 严格开发模式
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
echo microtime(true).' 环境变量初始化完成！'.PHP_EOL;
// 系统配置
if (file_exists(ROOT_PATH."/config/config.php")) {
    // 配置（全局变量）
    $configALL = require_once ROOT_PATH . "/config/config.php";
} else {
    // 示例配置
    $configALL = require_once ROOT_PATH . '/config/config.sample.php';
    echo microtime(true).' 缺少config.php，已载入config.sample.php示例配置。'.PHP_EOL;
    echo microtime(true).' 请把配置文件改名为config.php，以免后续版本升级覆盖配置！！！'.PHP_EOL;
    $t = 30;
    do {
        echo microtime(true)." 请把配置文件改名为config.php，{$t}秒后继续...".PHP_EOL;
        sleep(1);
    } while (--$t > 0);
}
echo microtime(true).' 全局配置载入完成！'.PHP_EOL;
// 读取支持列表
if (is_file(ROOT_PATH . "/config/sites.json")) {
    $sitesJson = file_get_contents(ROOT_PATH . "/config/sites.json");
    $configALL['sitesALL'] = json_decode($sitesJson, true);
    echo microtime(true).' 支持站点JSON载入完成！'.PHP_EOL;
}
echo microtime(true).' 正在加载composer包管理器...'.PHP_EOL;
require_once ROOT_PATH . '/vendor/autoload.php';
echo microtime(true).' composer依赖载入完成！'.PHP_EOL;
echo microtime(true).' 当前脚本运行环境：'.PHP_OS.PHP_EOL;
global $argv;
$start_file = str_replace("\\", "/", trim($argv[0]));
if (substr($start_file, -8)==="init.php") {
    require_once __DIR__ . '/iyuu.php';
}
