<?php
/**
 * 下载服务器抽象类
 * Created by PhpStorm
 * User: David <367013672@qq.com>
 * Date: 2020-1-11
 */
namespace IYUU\Client;

abstract class AbstractClient
{
    /**
     * 完整的下载服务器地址
     * @var string
     */
    protected $url = '';

    /**
     * 下载服务器用户名
     * @var string
     */
    protected $username = '';

    /**
     * 密码
     * @var string
     */
    protected $password = '';
    /**
     * 调试开关
     * @var bool
     */
    public $debug = false;

    /**
     * 公共方法：创建客户端实例
     * @param array $config
     * array(
     *  'type'  => '',
     *  'host'  => '',
     *  'endpoint'  =>  '',
     *  'username'  =>  '',
     *  'password'  =>  '',
     * )
     * @return mixed    客户端实例
     * @throws \IYUU\Client\ClientException
     */
    public static function create($config = [])
    {
        // 下载服务器类型
        $type = isset($config['type']) ? $config['type'] : '';
        $file = __DIR__ . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR . $type .'.php';
        if (!is_file($file)) {
            throw new ClientException($file.' 文件不存在');
        }
        $className = "IYUU\\Client\\" . $type . "\\" . $type;
        if (class_exists($className)) {
            echo $type." 客户端正在实例化！".PHP_EOL;
            return new $className($config);
        } else {
            throw new ClientException($className.' 客户端class不存在');
        }
    }

    /**
     * 初始化必须的参数
     * @descr 子类调用
     * @param array $config
     */
    protected function initialize($config = [])
    {
        $host   = isset($config['host']) ? $config['host'] : '';            // 地址端口
        $endpoint = isset($config['endpoint']) ? $config['endpoint'] : '';  // 接入点
        $username = isset($config['username']) ? $config['username'] : '';  // 用户名
        $password = isset($config['password']) ? $config['password'] : '';  // 密码
        $debug    = isset($config['debug']) ? $this->booleanParse($config['debug']) : false;    // 调试开关

        $this->url = rtrim($host, '/') . $endpoint;
        $this->username = $username;
        $this->password = $password;
        $this->debug = $debug;
    }

    /**
     * 对布尔型进行格式化
     * @param mixed $value 变量值
     * @return boolean/string 格式化后的变量
     */
    public function booleanParse($value)
    {
        $rs = $value;

        if (!is_bool($value)) {
            if (is_numeric($value)) {
                $rs = $value > 0 ? true : false;
            } elseif (is_string($value)) {
                $rs = in_array(strtolower($value), ['ok', 'true', 'success', 'on', 'yes', '(ok)', '(true)', '(success)', '(on)', '(yes)']) ? true : false;
            } else {
                $rs = $value ? true : false;
            }
        }

        return $rs;
    }

    /**
     * 查询Bittorrent客户端状态
     * @return string
     */
    abstract public function status();

    /**
     * 获取所有种子的列表
     * @param array $move
     * @return array(
     * 'hash'       => string json,
     * 'sha1'       => string,
     * 'hashString '=> array
     * )
     */
    abstract public function all(&$move = array());

    /**
     * 添加种子连接
     * @param string $torrent_url
     * @param string $save_path
     * @param array $extra_options
     */
    abstract public function add($torrent_url, $save_path = '', $extra_options = array());

    /**
     * 添加种子原数据
     * @param string $torrent_metainfo
     * @param string $save_path
     * @param array $extra_options
     */
    abstract public function add_metainfo($torrent_metainfo, $save_path = '', $extra_options = array());

    /**
     * 删除种子
     * @param $torrent
     * @param bool $deleteFiles
     */
    abstract public function delete($torrent, $deleteFiles = false);
}
