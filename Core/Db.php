<?php 
/**
 *
 * @date   2016-01-14 14:05
 *
 * @author sergey<joetang91@gmail.com>
 *
 */

namespace Core;

/**
 * DB.
 */
class Db
{

    protected static $instance = array();
    

    public static function instance($config_name)
    {
        if(!isset(\Config\DbConfig::$$config_name))
        {
            echo "\\Config\\Db::$config_name not set\n";
            throw new \Exception("\\Config\\Db::$config_name not set\n");
        }
        
        if(empty(self::$instance[$config_name]))
        {
            $config = \Config\DbConfig::$$config_name;
            self::$instance[$config_name] = new DbConnection($config['host'], $config['port'], $config['user'], $config['password'], $config['dbname']);
        }
        return self::$instance[$config_name];
    }

    public static function close($config_name)
    {
        if(isset(self::$instance[$config_name]))
        {
            self::$instance[$config_name]->closeConnection();
            self::$instance[$config_name] = null;
        }
    }
    

    public static function closeAll()
    {
        foreach(self::$instance as $connection)
        {
            $connection->closeConnection();
        }
        self::$instance = array();
    }

    public static function clearPool()
    {
        self::$instance = array();
    }
}