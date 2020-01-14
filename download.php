<?php
require_once __DIR__ . '/app/init.php';
global $argv;
if(count($argv) < 2){
	echo "--执行下载命令时，缺少站点参数，请查阅“常见问题”，获取站点参数！！\n\n";
	exit(1);
}
$start_file = $argv[0];
$command  = strtolower(trim($argv[1]));
$command2 = isset($argv[2]) ? $argv[2] : '';
if(is_file(APP_PATH.DS.'Protocols'.DS.$command.'.php')){
	switch ($command) {
		case 'start':
			break;
		case 'status':
			break;
		case 'stop':
			break;
		case 'restart':
			break;
		case 'reload':
			break;
		default :
			$command::run();
			break;
	}
}else{
	echo '解码文件：'.APP_PATH.DS.'Protocols'.DS.$command.'.php'." 不存在 \n\n";
	exit(1);
}