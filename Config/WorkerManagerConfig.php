<?php 
/**
 *
 * @date   2016-03-04 17:07
 *
 * @author sergey<joetang91@gmail.com>
 *
 */

namespace Config;

/**
 * WorkerManagerConfig
 */
class WorkerManagerConfig 
{
    public static $loop = true;
    public static $loopStopTimeInterval = 180;

    public static $needReleaseResourceInterface = array(
        array('\Core\Db', 'clearPool'),
    );
}