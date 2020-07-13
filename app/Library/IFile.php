<?php
namespace IYUU\Library;

/**
 * @class IFile
 * @brief IFile 文件处理类
 */
class IFile
{
    private $resource = null; //文件资源句柄
    /**
     * @brief 构造函数，打开资源流，并独占锁定
     * @param String $fileName 文件路径名
     * @param String $mode     操作方式，默认为读操作，可供选择的项为：r,r+,w+,w+,a,a+
     * @note $mod，'r'  只读方式打开，将文件指针指向文件头
     *             'r+' 读写方式打开，将文件指针指向文件头
     * 			   'w'  写入方式打开，将文件指针指向文件头并将文件大小截为零。如果文件不存在则尝试创建之。
     * 			   'w+' 读写方式打开，将文件指针指向文件头并将文件大小截为零。如果文件不存在则尝试创建之。
     * 			   'a'  写入方式打开，将文件指针指向文件末尾。如果文件不存在则尝试创建之。
     * 			   'a+' 读写方式打开，将文件指针指向文件末尾。如果文件不存在则尝试创建之。
     */
    public function __construct($fileName, $mode='r')
    {
        $dirName  = dirname($fileName);
        $baseName = basename($fileName);

        //检查并创建文件夹
        self::mkdir($dirName);

        $this->resource = fopen($fileName, $mode.'b');
        if ($this->resource) {
            flock($this->resource, LOCK_EX);
        }
    }

    /**
     * @brief 获取文件内容
     * @return String 文件内容
     */
    public function read()
    {
        $content = null;
        while (!feof($this->resource)) {
            $content.= fread($this->resource, 1024);
        }
        return $content;
    }

    /**
     * @brief 文件写入操作
     * @param  String $content 要写入的文件内容
     * @return Int or false    写入的字符数; false:写入失败;
     */
    public function write($content)
    {
        $worldsnum = fwrite($this->resource, $content);
        $this->save();
        return is_bool($worldsnum) ? false : $worldsnum;
    }

    /**
     * @brief  清空目录下的所有文件
     * @return bool false:失败; true:成功;
     */
    public static function clearDir($dir)
    {
        if ($dir[0] != '.' && is_dir($dir) && is_writable($dir)) {
            $dirRes = opendir($dir);
            while (false !== ($fileName = readdir($dirRes))) {
                if ($fileName[0] !== '.') {
                    $fullpath = $dir.'/'.$fileName;
                    if (is_file($fullpath)) {
                        self::unlink($fullpath);
                    } else {
                        self::clearDir($fullpath);
                        rmdir($fullpath);
                    }
                }
            }
            closedir($dirRes);
            return true;
        } else {
            return false;
        }
    }

    /**
     * @brief 获取文件信息
     * @param String $fileName 文件路径
     * @return array or null   array:文件信息; null:文件不存在;
     */
    public static function getInfo($fileName)
    {
        if (is_file($fileName)) {
            return stat($fileName);
        } else {
            return null;
        }
    }

    /**
     * @brief  创建文件夹
     * @param String $path  路径
     * @param int    $chmod 文件夹权限
     * @note  $chmod 参数不能是字符串(加引号)，否则linux会出现权限问题
     */
    public static function mkdir($path, $chmod=0777)
    {
        return is_dir($path) or (self::mkdir(dirname($path), $chmod) and mkdir($path, $chmod));
    }

    /**
     * @brief 复制文件
     * @param String $from 源文件路径
     * @param String $to   目标文件路径
     * @param String $mod  操作模式，c:复制(默认); x:剪切(删除$from文件)
     * @return bool  操作结果 true:成功; false:失败;
     */
    public static function copy($from, $to, $mode = 'c')
    {
        $dir = dirname($to);

        //创建目录
        self::mkdir($dir);

        copy($from, $to);

        if (is_file($to)) {
            if ($mode == 'x') {
                self::unlink($from);
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * @brief 删除文件
     * @param String $fileName 文件路径
     * @return bool  操作结果 false:删除失败;
     */
    public static function unlink($fileName)
    {
        if (is_file($fileName) && is_writable($fileName)) {
            return unlink($fileName);
        } else {
            return false;
        }
    }

    /**
     * @brief  删除$dir文件夹 或者 其下所有文件
     * @param  String $dir       文件路径
     * @param  bool   $recursive 是否强制删除，如果强制删除则递归删除该目录下的全部文件，默认为false
     * @return bool true:删除成功; false:删除失败;
     */
    public static function rmdir($dir, $recursive = false)
    {
        if (is_dir($dir) && is_writable($dir)) {
            //强制删除
            if ($recursive == true) {
                self::clearDir($dir);
                return self::rmdir($dir, false);
            }

            //非强制删除
            else {
                if (rmdir($dir)) {
                    return true;
                } else {
                    return false;
                }
            }
        }
    }

    /**
     * @brief 获取文件类型
     * @param  String $fileName  文件名
     * @return String $filetype  文件类型
     * @note 如果文件不存在，返回false,如果文件后缀名不在识别列表之内，返回NULL，对于docx及elsx格式文档识别在会出现识别为ZIP格式的错误，这是office2007的bug目前尚未修复，请谨慎使用
     */
    public static function getFileType($fileName)
    {
        $filetype = null;
        if (!is_file($fileName)) {
            return false;
        }

        $fileRes = fopen($fileName, "rb");
        if (!$fileRes) {
            return false;
        }
        $bin= fread($fileRes, 2);
        fclose($fileRes);

        if ($bin != null) {
            $strInfo  = unpack("C2chars", $bin);
            $typeCode = intval($strInfo['chars1'].$strInfo['chars2']);
            $typelist = self::getTypeList();
            foreach ($typelist as $val) {
                if (strtolower($val[0]) == strtolower($typeCode)) {
                    if ($val[0] == 8075) {
                        return array('zip','docx','xlsx');
                    } else {
                        return $val[1];
                    }
                }
            }
        }
        return $filetype;
    }

    /**
     * @brief 获取文件类型映射关系
     * @return array 文件类型映射关系数组
     */
    public static function getTypeList()
    {
        return array(
            array('255216','jpg'),
            array('13780','png'),
            array('7173','gif'),
            array('6677','bmp'),
            array('6063','xml'),
            array('60104','html'),
            array('208207','xls/doc'),
            array('8075','zip'),
            array('8075','docx'),
            array('8075','xlsx'),
            array("8297","rar"),
        );
    }

    /**
     * @brief 获取文件大小
     * @param  String $fileName 文件名
     * @return Int    文件大小的字节数，如果文件无效则返回 NULL
     */
    public static function getFileSize($fileName)
    {
        return is_file($fileName) ? filesize($fileName):null;
    }

    /**
     * @brief 检测文件夹是否为空
     * @param String $dir 路径地址
     * @return bool true:$dir为空目录; false:$dir为非空目录;
     */
    public static function isEmptyDir($dir)
    {
        if (is_dir($dir)) {
            $isEmpty = true;
            $dirRes  = opendir($dir);
            while (false !== ($fileName = readdir($dirRes))) {
                if ($fileName!='.' && $fileName!='..') {
                    $isEmpty = false;
                    break;
                }
            }
            closedir($dirRes);
            return $isEmpty;
        }
    }

    /**
     * @brief 释放文件锁定
     */
    public function save()
    {
        flock($this->resource, LOCK_UN);
    }

    /**
     * @brief  获取文件扩展名
     * @param  String $fileName  文件名
     * @return String 文件后缀名
     */
    public static function getFileSuffix($fileName)
    {
        $fileInfoArray = pathinfo($fileName);
        return $fileInfoArray['extension'];
    }

    /**
     * @brief 析构函数，释放文件连接句柄
     */
    public function __destruct()
    {
        if (is_resource($this->resource)) {
            fclose($this->resource);
        }
    }
}
