<?php 
/**
 *
 * @date   2016-01-13 20:43
 *
 * @author sergey<joetang91@gmail.com>
 *
 */

namespace Core;

/**
 * Logger
 */
class Logger 
{


    public static $loggerBasePath;

    private $name;

    private $logPath;

    private static $loggerPool = array();

    private function __construct($name)
    {
        $this->name = $name;
        if (!is_dir(self::$loggerBasePath . DIRECTORY_SEPARATOR . $this->name))
        {
            mkdir(self::$loggerBasePath . DIRECTORY_SEPARATOR . $this->name, 0755, true);
        }
        $this->logPath = self::$loggerBasePath . DIRECTORY_SEPARATOR . $this->name . DIRECTORY_SEPARATOR;

    }

    public function error($msg, $type = '')
    {
        $this->writeLog($this->genFileName('error', $type), $msg);
    }

    public function info($msg, $type = '')
    {
        $this->writeLog($this->genFileName('info', $type), $msg);
    }

    public function log($msg, $type = '')
    {
        $this->writeLog($this->genFileName('log', $type), $msg);
    }

    private function genFileName($category, $type)
    {
        return $category . ($type ? '_' . $type : '');
    }

    public function dataAppend($data, $type)
    {
        file_put_contents($this->logPath . $this->genFileName('data', $type) . '.data', $data . "\n", FILE_APPEND);
    }

    public function dataCover($data, $type)
    {
        file_put_contents($this->logPath . $this->genFileName('data', $type) . '.data', $data);
    }

    public function dataReadAll($type)
    {
        $fileName = $this->logPath . $this->genFileName('data', $type) . '.data';
        if (file_exists($fileName))
        {
            return file_get_contents($fileName);
        }
        return null;
    }

    public function dataClear($type)
    {
        $fileName = $this->logPath . $this->genFileName('data', $type) . '.data';
        if (file_exists($fileName))
        {
            unlink($fileName);
        }
    }


    private function writeLog($name, $msg)
    {
        if (is_array($msg) || is_object($msg))
        {
            $msg = serialize($msg);
        }
        file_put_contents($this->logPath . $name . '.log', $msg . "\n", FILE_APPEND);
    }


    public function __destruct()
    {
        self::$loggerPool[$this->name] = null;
        unset(self::$loggerPool[$this->name]);
    }

    public static function factory($name)
    {
        if (!isset(self::$loggerPool[$name]))
        {
            self::$loggerPool[$name] = new Logger($name);
        }
        return self::$loggerPool[$name];
    }

    public static function bakLogger()
    {
        if (is_dir(self::$loggerBasePath))
        {
            rename(self::$loggerBasePath , __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Log_' . date('Y-m-d-H-i-s'));
        }
    }

    public static function release($name)
    {
        if (self::$loggerPool[$name])
        {
            self::$loggerPool[$name]->__destruct();
        }
    }

}

