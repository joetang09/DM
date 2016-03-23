<?php 
/**
 *
 * @date   2016-03-01 14:03
 *
 * @author sergey<joetang91@gmail.com>
 *
 */

namespace Core;

/**
 * Worker Manager
 */
class WorkerManager 
{

    private static $instance = null;

    private $managerSetting = array();

    private $guessWorkerFileList = array();

    private $weedsWorkers = array();

    private $loop = false;

    private $loopStopTimeInterval = 5;

    private $gStartTime = 0;
    private $gEndTime = 0;

    private $lastRunWorkerTimeStamp = 0;
    private $lastLoopEndTimeStamp = 0;
    private $lastRuntimeTimeInterval = 0;

    private $crtLoopTimes = 0;

    private $superiorWorker = array();

    private $workerInstancesPool = array();

    private $logger;

    private $needReleaseResourceInterface = array();

    public static function getInstance()
    {
        if (self::$instance == null)
        {
            self::$instance = new WorkerManager();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->gStartTime = microtime(true);
        $this->logger = Logger::factory("WorkerManager");
    }

    private function checkself()
    {
        if (class_exists('\Config\WorkerManagerConfig'))
        {
            $this->managerSetting = get_class_vars('\Config\WorkerManagerConfig');
            $this->loop = isset($this->managerSetting['loop']) ? !! $this->managerSetting['loop'] : false;
            $this->loopStopTimeInterval = isset($this->managerSetting['loopStopTimeInterval']) ? $this->managerSetting['loopStopTimeInterval'] : 5;
            $this->needReleaseResourceInterface = isset($this->managerSetting['needReleaseResourceInterface']) ? $this->managerSetting['needReleaseResourceInterface'] : array();
        }
        $this->searchWorker();

    }

    public function handleWorker($worker = null)
    {
        $this->checkself();

        if ($worker != null)
        {
            if (!is_array($worker))
            {
                $worker = array($worker);
            }
        }
        else
        {
            $worker = array();
        }
        $this->buildWorker($worker);
        ksort($this->workerInstancesPool);
        $this->dumpStatus();
        do 
        {
            $this->lastRunWorkerTimeStamp = microtime(true);
            $this->crtLoopTimes ++;
            $this->dumpStatus();
            $this->run();
            $this->lastLoopEndTimeStamp = microtime(true);
            $this->lastRuntimeTimeInterval = $this->lastLoopEndTimeStamp - $this->lastRunWorkerTimeStamp;
            if ($this->loop && $this->lastRuntimeTimeInterval < $this->loopStopTimeInterval)
            {
                UI::console("Time Interval Less Than Condition of Time of Stop");
                break;
            }
            $this->dumpStatus();
        }
        while ($this->loop);
        $this->gEndTime = microtime(true);
        $this->dumpStatus();
    }

    private function dumpStatus()
    {
        $this->logger->dataCover(
            json_encode(
                array(
                    'managerSetting' => $this->managerSetting,
                    'guessWorkerFileList' => $this->guessWorkerFileList,
                    'weedsWorkers' => $this->weedsWorkers,
                    'loop' => $this->loop,
                    'loopStopTimeInterval' => $this->loopStopTimeInterval,
                    'gStartTime' => $this->gStartTime,
                    'gEndTime' => $this->gEndTime,
                    'lastRunWorkerTimeStamp' => $this->lastRunWorkerTimeStamp,
                    'lastLoopEndTimeStamp' => $this->lastLoopEndTimeStamp,
                    'lastRuntimeTimeInterval' => $this->lastRuntimeTimeInterval,
                    'crtLoopTimes' => $this->crtLoopTimes,
                    'superiorWorker' => $this->superiorWorker,
                )
            )
            , 'runtime_status');
    }

    private function run()
    {

        foreach ($this->workerInstancesPool as $worker)
        {
            
            $pids = array();
            foreach ($worker as $w)
            {
                $pid = pcntl_fork();
                if (-1 == $pid)
                {
                    throw new SDMException("fork failed");
                }
                else if ($pid)
                {
                    $pids[$w->getName()] = $pid;
                }
                else 
                {
                    foreach ($this->needReleaseResourceInterface as $interface)
                    {
                        call_user_func($interface);
                    }
                    $this->runWorkerAndEnd($w);
                }
            }
                
            $exception = false;

            foreach ($pids as $name => $pid)
            {
                pcntl_waitpid($pid, $status, WUNTRACED);
                if ($status != 0)
                {
                    UI::console($name . " exception exit with code : " . $status);
                    if ($status == 65280)
                    {
                        UI::console($name . " warnning . OOM ");
                    }
                    else
                    {
                        $exception = true;
                    }
                }
            }
            if ($exception)
            {
                throw new SDMException("child process exit with exception");
            }
                
        }

    }

    private function runWorkerAndEnd(AbsWorker $worker)
    {
        $this->runWorker($worker);
        System::end();
    }

    private function runWorker(AbsWorker $worker)
    {
        UI::console($worker->getName() . " => recover runtime data ... ");
        $worker->recoverRuntimeData();
        UI::console($worker->getName() . " => run before ... ");
        $worker->runBefore();
        UI::console($worker->getName() . " => running ... ");
        try
        {
            $worker->run();
        }
        catch (\Exception $e)
        {
            $worker->dumpWorkerRuntimeData();
            throw $e;
        }
        $worker->dumpWorkerRuntimeData();
        
        UI::console($worker->getName() . " => run after ... ");
        $worker->runAfter();
        UI::console($worker->getName() . " => run arrived ... ");
    }

    private function buildWorker(array $workerNames = array())
    {
        if (empty($workerNames))
        {
            foreach ($this->weedsWorkers as $workerClass)
            {
                $this->putWorker($workerClass);
            }
        }
        else 
        {
            foreach ($workerNames as $wn)
            {
                $this->putWorker($wn);
            }
        }
    }

    private function putWorker($workerClass)
    {
        
        if (!in_array($workerClass, $this->weedsWorkers))
        {
            throw new SDMException("worker not found : $workerClass");
        }
        $workerClassWithNs = '\WorkerFactory\\' . $workerClass;
        if (!class_exists($workerClassWithNs))
        {
            return ;
        }
        $setting = array();
        $tmpClassConfig = 'Config\\' . $workerClass . 'Config';
        if (class_exists($tmpClassConfig))
        {
            $setting = get_class_vars($tmpClassConfig);
        }
        
        $worker = new $workerClassWithNs($workerClass, $setting);
        if (! $worker instanceof AbsWorker)
        {
            return;
            throw new SDMException("worker build exception : $workerClassWithNs");
        }
        if ($worker->getSort() < 0)
        {
            throw new SDMException("worker sorting error : $workerClassWithNs");
        }
        if (!isset($this->workerInstancesPool[$worker->getSort()]))
        {
            $this->workerInstancesPool[$worker->getSort()] = array();
        }
        $this->workerInstancesPool[$worker->getSort()][] = $worker;
        $this->superiorWorker[] = $workerClass;
    }

    private function searchWorker()
    {
        $this->guessWorkerFileList = glob(__DIR__ . '/../WorkerFactory/*Worker.php');
        foreach ($this->guessWorkerFileList as $wfl)
        {
            $tmpClass = substr($wfl, strrpos($wfl, '/') + 1);
            $tmpClass = substr($tmpClass, 0, strlen($tmpClass) - 4);
            $this->weedsWorkers[] = $tmpClass;
            
        }
    }

    public static function showRuntimeStatus()
    {
        $status = Logger::factory('WorkerManager')->dataReadAll('runtime_status');
        if ($status)
        {
            $status = json_decode($status, true);
            UI::repeat('*', 50);
            UI::console('[Manager Setting]');
            foreach ($status['managerSetting'] as $k => $val)
            {
                UI::console($k . ' => ' . $val);
            }
            UI::console('[Guess Worker File List]');
            foreach ($status['guessWorkerFileList'] as $k => $val)
            {
                UI::console($val);
            }
            UI::console('[Weed Workers]');
            foreach ($status['weedsWorkers'] as $w)
            {
                UI::console($w);
            }
            UI::console(('[Superior Workers]'));
            foreach ($status['superiorWorker'] as $w)
            {
                UI::console($w);
            }
            UI::console('[Loop]');
            UI::console(var_export($status['loop']));
            UI::console("[loopStopTimeInterval] \n" . $status['loopStopTimeInterval']);
            UI::console("[gStartTime] \n" . $status['gStartTime']);
            UI::console("[gEndTime] \n" . $status['gEndTime']);
            UI::console("[lastRunWorkerTimeStamp] \n" . $status['lastRunWorkerTimeStamp']);
            UI::console("[lastLoopEndTimeStamp] \n" . $status['lastLoopEndTimeStamp']);
            UI::console("[lastRuntimeTimeInterval] \n" . $status['lastRuntimeTimeInterval']);
            UI::console("[crtLoopTimes] \n" . $status['crtLoopTimes']);
            UI::repeat('*', 50);
        }
        else
        {
            UI::console('DM not running ? Load Status File Failed.');
        }
    }


}