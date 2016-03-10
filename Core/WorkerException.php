<?php
/**
 *
 * @date   2016-03-02 14:21
 *
 * @author sergey<joetang91@gmail.com>
 *
 */

namespace Core;

/**
 * Worker Exception.
 */
class WorkerException extends \Exception
{

    const WORKER_EXCEPTION_DEADLY = 1;

    const WORKER_EXCEPTION_RELAY   = 2;

    const WORKER_EXCEPTION_INGONRE = 3;


}