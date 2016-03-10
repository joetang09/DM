<?php 
/**
 *
 * @date   2016-01-14 09:48
 *
 * @author sergey<joetang91@gmail.com>
 *
 */

namespace Core;

/**
 * Worker Material.
 */
class WorkerMaterial 
{

    const RUNTIME_STATUS_BUILED       = 1;
    const RUNTIME_STATUS_PRE_DEAL     = 2;
    const RUNTIME_STATUS_DEALING      = 3;
    const RUNTIME_STATUS_DEALED       = 4;
    const RUNTIME_STATUS_EXCEPTION    = 5;

    const DEAL_STATUS_SUCCESSED = 1;
    const DEAL_STATUS_FAILED    = 2;

    const EXCEPTION_HANDLE_FUNCTION_RELAY  = 1;
    const EXCEPTION_HANDLE_FUNCTION_IGNORE = 2;

    private $buildTimeStamp  = 0;
    private $dealTimeStamp   = 0;
    private $dealedTimeStamp = 0;

    private $runtimeStatus              = 0;

    private $dealStatus                 = 0;

    private $exceptionHandleFunction    = 0;

    private $failedReason;

    private $exceptionStack = array();

    private $cursor;
    private $data;

    private $retryNum = 0;

    private $curentRetryNum = 0;

    public function __construct($cursor, $data, $retryNum)
    {
        $this->cursor = $cursor;
        $this->data = $data;
        $this->buildTimeStamp = microtime(true);
        $this->runtimeStatus = self::RUNTIME_STATUS_BUILED;
        $this->retryNum = $retryNum;
    }

    public function getRuntimeStatus()
    {
        return $this->runtimeStatus;
    }

    public function getDealStatus()
    {
        return $this->dealStatus;
    }

    public function getFailedReason()
    {
        return $this->failedReason;
    }

    public function recentlyException()
    {
        return end($this->exceptionStack);
    }

    public function getCursor()
    {
        return $this->cursor;
    }

    public function getData()
    {
        return $this->data;
    }

    public function preDealing()
    {
        $this->runtimeStatus = self::RUNTIME_STATUS_PRE_DEAL;
    }

    public function onDealing()
    {
        $this->runtimeStatus = self::RUNTIME_STATUS_DEALING;
        $this->dealTimeStamp = time(true);
    }

    public function onDealed()
    {
        $this->runtimeStatus = self::RUNTIME_STATUS_DEALED;
        $this->dealedTimeStamp = microtime(true);
        $this->success();
    }

    public function success()
    {
        $this->dealStatus = self::DEAL_STATUS_SUCCESSED;
    }

    public function failed($reason = null)
    {
        $this->failedReason = $reason;
        $this->dealStatus = self::DEAL_STATUS_FAILED;
    }

    public function couldRelay()
    {
        return $this->retryNum > $this->curentRetryNum;
    }

    public function exceptionHandleFunction()
    {
        return $this->exceptionHandleFunction;
    }

    public function onException($message)
    {
        $this->runtimeStatus = self::RUNTIME_STATUS_EXCEPTION;
        $this->exceptionStack[] = $message;
        
        if ($this->retryNum >= $this->curentRetryNum ++ )
        {
            $this->exceptionHandleFunction = self::EXCEPTION_HANDLE_FUNCTION_RELAY;
        }
        else 
        {
            $this->exceptionHandleFunction = self::EXCEPTION_HANDLE_FUNCTION_IGNORE;
            $this->failed($message);
        }
    }

}