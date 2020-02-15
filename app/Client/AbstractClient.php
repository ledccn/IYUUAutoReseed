<?php
/**
 * Created by PhpStorm.
 * User: David <367013672@qq.com>
 * Date: 2020-2-14
 * Time: 21:31:49
 */

namespace IYUU\Client;

abstract class AbstractClient
{
    /**
     * 公共方法：创建客户端实例
     */
    public static function create($config = array())
    {
        $type = $config['type'];
        $host = $config['host'];
        $username = $config['username'];
        $password = $config['password'];

        $className = "IYUU\Client\\" . $type . "\\" . $type;
        if (class_exists($className)) {
            echo $type." 客户端正在实例化！".PHP_EOL;
            return new $className($host, $username, $password);
        } else {
            die($className.' 客户端不存在');
        }
    }

    /**
     * 查询Bittorrent客户端状态
     *
     * @return string
     */
    abstract public function status();

    /**
     * 获取种子列表
     * @return array(
            'hash'       => string json,
            'sha1'       => string,
            'hashString '=> array
        )
     */
    abstract public function getList(&$move = array());

    /**
     * 添加种子连接
     */
    abstract public function add($torrent_url, $save_path = '', $extra_options = array());

    /**
     * 添加种子原数据
     */
    abstract public function add_metainfo($torrent_url, $save_path = '', $extra_options = array());

    /**
     * 删除种子
     */
    abstract public function delete($hash, $deleteFiles = false);
}
