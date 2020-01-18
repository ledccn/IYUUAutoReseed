<?php
/**
 * Created by PhpStorm.
 * User: Rhilip
 * Date: 1/17/2020
 * Time: 2020
 */

namespace IYUU\Client;

interface AbstractClientInterface
{

    /**
     * 查询Bittorrent客户端状态
     *
     * @return string
     */
    public function status();
}
