<?php 

/**
 *
 * @date   2016-03-01 09:49
 *
 * @author sergey<joetang91@gmail.com>
 *
 */

namespace Core;

/**
 * Exception Handler.
 */
class ExceptionHandler 
{

    public static function onException($exception)
    {
        UI::console(date('Y-m-d H:i:s') . "exception : \n{$exception->getMessage()} with code {$exception->getCode()} at {$exception->getFile()} : {$exception->getLine()} \n trace statck : \n {$exception->getTraceAsString()}");

    }

}