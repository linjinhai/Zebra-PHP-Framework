<?php
/**
 * Created by PhpStorm.
 * User: huyanping
 * Date: 14-7-23
 * Time: 上午9:42
 */

/**
 *
 * 自定义错误处理函数，将错误重定向到文件
 * example: set_error_handler('errorHandler');
 * 需要预先定义ERROR_LOG_PATH常量
 *
 * @param $errno 错误代码
 * @param $errstr 错误信息
 * @param $errfile 发生错误的文件
 * @param $errline 发生错误的行数
 * @param $errcontext
 */
namespace Zebra\Utils;

function errorHandler($errno, $errstr, $errfile, $errline, $errcontext)
{
    $errorMessage = 'Into ' . __FUNCTION__ . '() at line ' . __LINE__ .
        "---ERRNO---" . \print_r($errno, true) .
        "---ERRSTR---" . \print_r($errstr, true) .
        "---ERRFILE---" . \print_r($errfile, true) .
        "---ERRLINE---" . \print_r($errline, true) .
        "---ERRCONTEXT---" . \print_r($errcontext, true) .
        "---Backtrace of error_handler()---" . \print_r(\debug_backtrace(), true);

    $errorMessage = \str_replace(array("\r\n", "\n"), '', $errorMessage);
    $log_file_name = ERROR_LOG_PATH . \date("Ymd") . "-error.log";
    \file_put_contents($log_file_name, $errorMessage . PHP_EOL, FILE_APPEND);
}

/**
 * 类似SQL ORDER BY 的多为数组排序函数
 * example: $sorted = array_orderby($data, 'volume', SORT_DESC, 'edition', SORT_ASC);
 *
 * @return mixed
 */
function arrayOrderby()
{
    $args = \func_get_args();
    $data = \array_shift($args);
    foreach ($args as $n => $field) {
        if (\is_string($field)) {
            $tmp = array();
            foreach ($data as $key => $row)
                $tmp[$key] = $row[$field];
            $args[$n] = $tmp;
        }
    }
    $args[] = & $data;
    \call_user_func_array('array_multisort', $args);
    return \array_pop($args);
}


/**
 * 获取客户端真实IP
 *
 * @param int $type
 * @return string
 */
function getIp($type = 0)
{
    if (!empty($_SERVER["HTTP_CLIENT_IP"])) $cip = $_SERVER["HTTP_CLIENT_IP"];
    else if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) $cip = $_SERVER["HTTP_X_FORWARDED_FOR"];
    else if (!empty($_SERVER["REMOTE_ADDR"])) $cip = $_SERVER["REMOTE_ADDR"];
    else $cip = "";
    \preg_match("/[\d\.]{7,15}/", $cip, $cips);
    $cip = $cips[0] ? $cips[0] : 'unknown';
    unset($cips);
    if ($type == 1) $cip = \myip2long($cip);
    return $cip;
}

/**
 * 获取服务器端真实IP
 *
 * @return string
 */
function getServerIp()
{
    static $serverip = NULL;

    if ($serverip !== NULL) {
        return $serverip;
    }
    if (isset($_SERVER)) {
        if (isset($_SERVER['SERVER_ADDR'])) {
            $serverip = $_SERVER['SERVER_ADDR'];
        } else {
            $serverip = '0.0.0.0';
        }
    } else {
        $serverip = \getenv('SERVER_ADDR');
    }
    return $serverip;
}

/**
 * 检测字符串是否以$test结尾
 *
 * @param $string
 * @param $test
 * @return bool
 */
function endsWith($string, $test)
{
    $strlen = \strlen($string);
    $testlen = \strlen($test);
    if ($testlen > $strlen) return false;
    return \substr_compare($string, $test, $strlen - $testlen, $testlen, true) === 0;
}


/**
 * 从数组中获取指定的值
 *
 * @param  $array array
 * @param  $key string
 * @return mixed 返回值
 */
function getArrayValue(&$array, $key, $defaultValue = null)
{
    return \array_key_exists($key, $array) ? $array[$key] : $defaultValue;
}

/**
 * 删除目录（即使目录不为空）
 *
 * @param $path
 * @return bool
 */
function deleteTree($path)
{
    if (empty($path)) return false;
    debugFile($path, 'deltree.txt');
    if (!\is_dir($path)) {
        if (\is_file($path)) unlink($path);
    } else {
        $dh = \opendir($path);
        while ($file = \readdir($dh)) {
            if ($file != '.' && $file != '..') {
                deltree($path . $file);
            }
        }
        \closedir($dh);
        \rmdir($path);
    }

}


/**
 * 扫描目录
 *
 * @param $dir
 * @return array
 */
function readDir($dir)
{
    $ret = array('dirs' => array(), 'files' => array());
    if ($handle = \opendir($dir)) {
        while (false !== ($file = \readdir($handle))) {
            if ($file != '.' && $file !== '..') {
                $cur_path = $dir . DIRECTORY_SEPARATOR . $file;
                if (\is_dir($cur_path)) {
                    $ret['dirs'][$cur_path] = \read_dir($cur_path);
                } else {
                    $ret['files'][] = $cur_path;
                }
            }
        }
        \closedir($handle);
    }
    return $ret;
}


/**
 * 获取可读的大小名字
 *
 * @param $size
 * @return string
 */
function getRealSize($size)
{
    $kb = 1024; // Kilobyte
    $mb = 1024 * $kb; // Megabyte
    $gb = 1024 * $mb; // Gigabyte
    $tb = 1024 * $gb; // Terabyte

    if ($size < $kb) return $size . " B";
    if ($size < $mb) return \round($size / $kb, 2) . " KB";
    if ($size < $gb) return \round($size / $mb, 2) . " MB";
    if ($size < $tb) return \round($size / $gb, 2) . " GB";

    return \round($size / $tb, 2) . " TB";

}


/**
 * 检查PHP文件语法是否正确
 * @param $php_file
 * @return bool
 */
function checkPhpSyntax($php_file){
    $command = PHP_COMMAND_PATH . ' -l ' . $php_file;
    exec($command, $output, $status);
    if($status===0){
        return true;
    }
    return false;
}


/**
 * 递归建立多层目录
 * @param $path
 */
function mkFolder($path){
    if(!is_readable($path)){
        MkFolder( dirname($path) );
        if(!is_file($path)) mkdir($path,0755);
    }
}
