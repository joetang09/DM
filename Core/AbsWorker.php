<?php 
/**
 *
 * @date   2016-03-01 14:28
 *
 * @author sergey<joetang91@gmail.com>
 *
 */

namespace Core;


/**
 * Abs Worker.
 */
abstract class AbsWorker 
{

    private $envParams;
    private $workerLogger;

    private $name;
    private $gStartCursor;
    private $gEndCursor;
    private $dealNumberTimes;

    private $crtStartCursor;
    private $dealCursor = 0;

    private $runSort;

    private $ignorePool = array();

    private $delayPool = array();


    public function __construct($name, $envParams)
    {
        $this->name = $name;
        $this->envParams = $envParams;
        $this->workerLogger = Logger::factory($this->name);
        $this->checkenvParams();
        $this->recoverRuntimeData();
    }

    public function getName()
    {
        return $this->name;
    }

    private function checkenvParams()
    {
        $this->gEndCursor = isset($this->envParams['gEndCursor']) ? $this->envParams['gEndCursor'] : -1;
        $this->gStartCursor = isset($this->envParams['gStartCursor']) ? $this->envParams['gStartCursor'] : 0;
        $this->dealNumberTimes = isset($this->envParams['dealNumberTimes']) ? $this->envParams['dealNumberTimes'] : 1000;
        $this->runSort = isset($this->envParams['runSort']) ? $this->envParams['runSort'] : 0;
        $this->crtStartCursor = $this->gStartCursor;
        $this->dealCursor = $this->crtStartCursor;
    }

    public function recoverRuntimeData()
    {
        $runtimeData = $this->workerLogger->dataReadAll('runtime_data');
        if ($runtimeData)
        {
            $runtimeData = json_decode($runtimeData, true);
            $this->envParams = $runtimeData['envParams'];
            $this->gStartCursor = $runtimeData['gStartCursor'];
            $this->gEndCursor = $runtimeData['gEndCursor'];
            $this->dealNumberTimes = $runtimeData['dealNumberTimes'];
            $this->crtStartCursor = $runtimeData['crtStartCursor'];
            $this->dealCursor = $runtimeData['dealCursor'];
        }
    }

    public function getSort()
    {
        return $this->runSort;
    }

    public function run()
    {
        while (true)
        {
            if ($this->gEndCursor > 0 && $this->gEndCursor >= $this->dealCursor)
            {
                $this->workerLogger->log('deal over', 'deal');
                break;
            }
            $this->crtStartCursor = $this->dealCursor;
            try {
                $raw = $this->fetchRawMaterial($this->crtStartCursor, $this->dealNumberTimes);
                if (!is_array($raw))
                {
                    $this->workerLogger->log('data error', 'raw_material_load');
                    throw new WorkerException("raw material data error", WorkerException::WORKER_EXCEPTION_DEADLY);
                }
                else if (empty($raw))
                {
                    $this->workerLogger->log('deal over', 'deal');
                    UI::console($this->name . " => deal over ...");
                    break;
                }
                $this->workerLogger->log('start cursor : ' . $this->crtStartCursor . ' load sucess', 'raw_material_load');
            }
            catch (\Exception $e)
            {
                $this->workerLogger->log($e->getMessage(), 'exception');
                throw $e;
            }
            
            $preDealData = array();
            foreach ($raw as $c => $data)
            {
                if ($this->gEndCursor < 0 || $c < $this->gEndCursor)
                {
                    $preDealData[$c] = new WorkerMaterial($c, $data, 1);
                    $this->workerLogger->log('success, the cursor : ' . $c, 'worker_masterial_build');
                } else {
                    $this->workerLogger->log('over, the end cursor : ' . $c, 'worker_masterial_build');
                }

            }

            foreach ($preDealData as $c => $data)
            {
                $this->dealCursor = $c;
                $data->preDealing();
                $data->onDealing();
                try
                {
                    $this->dealOne($c, $data->getData());
                }
                catch (\Exception $e)
                {
                    UI::console(date('Y-m-d H:i:s') . "exception : \n{$e->getMessage()} with code {$e->getCode()} at {$e->getFile()} : {$e->getLine()} \n trace statck : \n {$e->getTraceAsString()}");
                    $data->onException($e->getMessage());
                    $this->workerLogger->log($e->getMessage(), 'deal_error');
                    if ($e instanceof WorkerException)
                    {
                        if ($e->getCode() == WorkerException::WORKER_EXCEPTION_DEADLY)
                        {
                            $data->failed($e->getMessage());
                            throw $e;
                        }
                        else if ($e->getCode() == WorkerException::WORKER_EXCEPTION_INGONRE)
                        {
                            $this->dealIgnore($data);
                            continue;
                        }
                        else if ($e->getCode() == WorkerException::WORKER_EXCEPTION_RELAY)
                        {
                            if ($data->couldRelay())
                            {
                                $this->dealRelay($data);
                            }
                            continue;
                        }
                        
                    }
                    throw new \Exception("Unkonw Exception : " . $e->getMessage());
                }
                $this->workerLogger->log($c, 'deal');
                $data->success();
                
                
            }
            $this->dumpWorkerRuntimeData();
        }
        
    }



    private function dealIgnore(WorkerMaterial $raw)
    {
        $this->ignorePool[] = $raw;
        if (count($this->ignorePool) > 100)
        {
            $this->dumpDealIgnore();
        }
    }

    private function dealRelay(WorkerMaterial $raw)
    {
        $this->delayPool[] = $raw;
        if (count($this->delayPool) > 100)
        {
            $this->dumpDealRelay();
        }
    }

    private function dumpDealIgnore()
    {
        foreach ($this->ignorePool as $k => $data)
        {
            $this->workerLogger->dataAppend(serialize($data), 'deal_ignore');
            unset($this->ignorePool[$k]);
        }
    }

    private function dumpDealRelay()
    {
        foreach ($this->delayPool as $k => $data)
        {
            $this->workerLogger->dataAppend(serialize($data), 'deal_relay');
            unset($this->delayPool[$k]);
        }
    }

    public function dumpWorkerRuntimeData()
    {
        $runtimeData = array();
        $runtimeData['envParams'] = $this->envParams;
        $runtimeData['gStartCursor'] = $this->gStartCursor;
        $runtimeData['gEndCursor'] = $this->gEndCursor;
        $runtimeData['dealNumberTimes'] = $this->dealNumberTimes;
        $runtimeData['crtStartCursor'] = $this->crtStartCursor;
        $runtimeData['dealCursor'] = $this->dealCursor;
        $this->workerLogger->dataCover(json_encode($runtimeData), 'runtime_data');
        $this->dumpDealIgnore();
        $this->dumpDealRelay();
    }

    public abstract function fetchRawMaterial($start, $length);

    public abstract function dealOne($cursor, $data);

    public function runBefore() {}

    public function runAfter() {}

}