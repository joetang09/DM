<?php 
/**
 *
 * @date   2016-03-01 10:14
 *
 * @author sergey<joetang91@gmail.com>
 *
 */

namespace Core;

/**
 * Error Handler.
 */
class ErrorHandler 
{

    public static function onError($errno, $errstr, $errfile, $errline)
    {

        UI::console(date('Y-m-d H:i:s') . " error $errno \n at $errfile : $errline with $errstr ");
    }



}