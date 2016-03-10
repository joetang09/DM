<?php 
/**
 *
 * @date   2016-01-13 21:05
 *
 * @author sergey<joetang91@gmail.com>
 *
 */

namespace Core;

/**
 * AutoLoader.
 */
class Autoloader
{
    public static $appInitPath;

    public static function loadByNamespace($name)
    {

        $classFile = self::$appInitPath . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR ,$name) . '.php';
        if(is_file($classFile))
        {
            require_once($classFile);
            if(class_exists($name, false))
            {
                return true;
            }
        }
        return false;
    }
}

Autoloader::$appInitPath = __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR;

spl_autoload_register('\Core\Autoloader::loadByNamespace');