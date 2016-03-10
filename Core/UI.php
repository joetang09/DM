<?php 
/**
 *
 * @date   2016-03-01 12:59
 *
 * @author sergey<joetang91@gmail.com>
 *
 */

namespace Core;

/**
 * UI.
 */
class UI 
{

    private $templateContainer = array();

    private static $instance = null;

    public static function getInstance()
    {
        if (self::$instance == null)
        {
            self::$instance = new UI();
        }
        return self::$instance;
    }

    private function __construct()
    {
    }

    private function templateFactory(array $rule)
    {
        $ruleKey = json_encode($rule);
        if ($this->templateContainer[$ruleKey])
        {
            return $this->templateContainer[$ruleKey];
        }
    }

    public static function console($message)
    {
        echo $message . "\n";
    }

    public static function repeat($char, $len)
    {
        $showChar = '';
        while ($len -- > 0)
        {
            $showChar .= $char;
        }
        self::console($showChar);
    }


    public static function warningWindow($message)
    {

    }

    
}