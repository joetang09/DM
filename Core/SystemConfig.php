<?php 

/**
 *
 * @date   2016-02-29 20:25
 *
 * @author sergey<joetang91@gmail.com>
 *
 */

namespace Core;

/**
 * System Config.
 */
class SystemConfig 
{

    private static $SETTING_HISTORY = array();

    public static function setErrorHandler($callable)
    {
        $oldValue = set_error_handler($callable);
        self::buildHistory($oldValue, func_get_args());
        return $oldValue;
    }

    public static function setExceptionHandler($callable)
    {
        $oldValue = set_exception_handler($callable);
        self::buildHistory($oldValue, func_get_args());
        return $oldValue;
    }

    public static function setTimeLimit($seconds)
    {
        $oldValue = set_time_limit($seconds);
        self::buildHistory($oldValue, func_get_args());
        return $oldValue;
    }

    public static function errorReporting($eLevel)
    {
        $oldValue = error_reporting($eLevel);
        self::buildHistory($oldValue, func_get_args());
        return $oldValue;
    }

    public static function setTimeZone($newTimeZone)
    {
        $oldValue = date_default_timezone_set($newTimeZone);
        self::buildHistory($oldValue, func_get_args());
        return $oldValue;
    }

    public static function iniSetting($key, $value)
    {
        $oldValue = ini_set($key, $value);
        self::buildHistory($oldValue, func_get_args());
        return $oldValue;
    }

    public static function autoloadRegiste($callable)
    {
        if (spl_autoload_register($callable))
        {
            self::buildHistory('-', func_get_args());
            return true;
        }
        return false;
    }

    public static function setShutdownFunc($callable)
    {
        register_shutdown_function($callable);
        self::buildHistory('', $callable);
    }

    private static function buildHistory($oldValue, $newValue)
    {
        $backtrace = debug_backtrace();
        array_shift($backtrace);
        self::$SETTING_HISTORY[] = array(
            'time_stamp' => microtime(true),
            'function' => current($backtrace),
            'oldValue' => $oldValue,
            'newValue' => $newValue,
        );
    }


}