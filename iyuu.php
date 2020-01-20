<?php
require_once __DIR__ . '/init.php';
use IYUU\AutoReseed;

#echo "IYUUAutoReseed自动辅种脚本，目前支持以下站点：".PHP_EOL;
#ShowTableSites();
echo <<<EOF
gitee 源码仓库：https://gitee.com/ledc/IYUUAutoReseed
github源码仓库：https://github.com/ledccn/IYUUAutoReseed
教程：https://gitee.com/ledc/IYUUAutoReseed/tree/master/wiki
QQ群：859882209 【IYUU自动辅种交流】
EOF;
echo PHP_EOL.PHP_EOL;

AutoReseed::init();
$hashArray = AutoReseed::get();
if (AutoReseed::$move != null) {
    echo "种子移动完毕，请重新编辑配置，再尝试辅种！ \n\n";
    exit;
}
AutoReseed::call($hashArray);
AutoReseed::wechatMessage();
