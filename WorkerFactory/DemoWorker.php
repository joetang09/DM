<?php 
/**
 *
 * @date   2016-01-14 10:17
 *
 * @author sergey<joetang91@gmail.com>
 *
 */

namespace WorkerFactory;


/**
 * Demo Worker.
 */
class DemoWorker extends \Core\AbsWorker 
{
    private $count = 0;

    public function fetchRawMaterial($start, $length)
    {
        $result = array();
        if ($this->count ++ > 2)
        {
            return $result;
        }
        for ($i = 1; $i < $length + 1; $i ++)
        {
            $result[$start + $i] = $start + $i;
        }

        return $result;
    }

    public function dealOne($cursor, $data)
    {
        // var_dump($cursor, $data);
        // echo "00000000  ====>  " . $cursor . "\n";
    }


}