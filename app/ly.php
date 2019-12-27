<?php
/**
 * 作用：添加聆音阅听专区30页小包，方便大家赚魔力
 * 使用方法：放入/app/ 目录下即可。
 */
require_once __DIR__ . '/init.php';
$start = 0;
$end = 30;
$url = 'live.php?inclbookmarked=0&incldead=1&spstate=0&&sort=5&type=asc&page={}';
while ($start <= $end) {
    soulvoice::run(str_replace('{}', $start, $url), false);
    $start++;
}