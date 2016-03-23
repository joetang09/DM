<?php 
/**
 *
 * @date   2016-03-14 13:54
 *
 * @author sergey<joetang91@gmail.com>
 *
 */

namespace Core;

/**
 * Sig Handler.
 */
class SigHandler 
{

    public static function sigHandler($signo)
    {
        var_dump($signo);
        exit;
    }
    
}