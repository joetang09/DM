<?php
/**
 *
 * @date   2016-02-29 19:57
 *
 * @author sergey<joetang91@gmail.com>
 *
 */

namespace Core;

/**
 * Core
 */
class System 
{

    private static $RUNTIME_ARGV = array();

    private static $ESSENTIAL_EXTENSION = array('pcntl', 'posix');

    private static $SYSTEM_CONFIG = array();

    private static $DEAMONIZE = false;

    private static $STDOUTFILE = '/dev/null';

    private static $RUN_TYPE = 'normal';

    private static function parserArgv()
    {
        self::$RUNTIME_ARGV = $_SERVER;
        if (class_exists('\Config\SystemConfig'))
        {
            self::$SYSTEM_CONFIG = get_class_vars('\Config\SystemConfig');
        }
        global $argv;
        foreach ($argv as $val)
        {
            $val = trim($val);
            if ($val == '-d')
            {
                self::$DEAMONIZE = true;
            }
            else if ($val == 'status')
            {
                self::$RUN_TYPE = 'status';
            }
        }

    }

    public static function getRuntimeArgc($name, $defualt = null)
    {
        return isset(self::$RUNTIME_ARGV[$name]) ? self::$RUNTIME_ARGV[$name] : $defualt;
    }

    public static function getSystemConfig($name, $defualt = null)
    {
        return isset(self::$SYSTEM_CONFIG[$name]) ? self::$SYSTEM_CONFIG[$name] : $default;
    }

    private static function installSystemHandler()
    {
        SystemConfig::setExceptionHandler(array('\Core\ExceptionHandler', 'onException'));
        SystemConfig::setErrorHandler(array('\Core\ErrorHandler', 'onError'));
    }

    private static function initRuntimeEnv()
    {

        SystemConfig::setTimeLimit(0);
        SystemConfig::errorReporting(E_ALL);
        SystemConfig::setTimeZone('Asia/Chongqing');
        SystemConfig::iniSetting('memory_limit', '256M');
    }

    private static function checkDependent()
    {
        self::checkVersion();
        self::checkPHPMode();
        foreach (self::$ESSENTIAL_EXTENSION as $extension)
        {
            self::checkExtension($extension);
        }
    }

    private static function checkVersion()
    {
        if (version_compare("5.4", PHP_VERSION, ">")) {
            throw new SDMException("PHP 5.4 or greater is required");
        }

    }

    private static function checkExtension($extension)
    {
        if (!extension_loaded($extension))
        {
            throw new SDMException("Extension lost : " . $extension);
        }
    }

    private static function checkPHPMode()
    {
        if (php_sapi_name() != 'cli')
        {
            throw new SDMException("PHP Mode Error! Only support Cli Mode ");
        }
    }

    public static function sigHandler($signo)
    {
        var_dump($signo);
        exit;
    }

    private static function initBase()
    {
        
        Logger::bakLogger();
    }

    private static function keepPid()
    {
        file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'dm.pid', getmypid());
    }

    public static function autoStart()
    {
        Logger::$loggerBasePath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Log';
        self::parserArgv();

        switch (self::$RUN_TYPE) {
            case 'normal':

                self::installSystemHandler();
                self::checkDependent();
                self::initRuntimeEnv();
                self::initBase();
                self::deamonize();
                self::keepPid();
                self::resetStd();

                // declare(ticks=1);
                // pcntl_signal(SIGTERM, array('\Core\System', 'sigHandler'));
                // pcntl_signal(SIGHUP,  array('\Core\System', 'sigHandler'));
                // pcntl_signal(SIGUSR1, array('\Core\System', 'sigHandler'));
                // pcntl_signal(SIGKILL, array('\Core\System', 'sigHandler'));
                // while(true){
                //     // posix_kill(posix_getpid(), SIGUSR1);
                // }

                WorkerManager::getInstance()->handleWorker();
                self::end();
                break;
            case 'status' :
                WorkerManager::showRuntimeStatus();
                break;
            case 'stop' :
                break;
            default:
                UI::console("Unkown Command : " . self::$RUN_TYPE);
                break;
        }
        
    }

    private static function resetStd()
    {
        if(!self::$DEAMONIZE)
        {
            return;
        }
        global $STDOUT, $STDERR;
        self::$STDOUTFILE = __DIR__ . '/../system.log';

        $handle = fopen(self::$STDOUTFILE, "a");
        if($handle) 
        {
            unset($handle);
            @fclose(STDOUT);
            @fclose(STDERR);
            $STDOUT = fopen(self::$STDOUTFILE, "a");
            $STDERR = fopen(self::$STDOUTFILE, "a");
        }
        else
        {
            throw new SDMException('can not open STDOUTFILE ' . self::$STDOUTFILE);
        }
    }

    private static function deamonize()
    {
        if (!self::$DEAMONIZE)
        {
            UI::console('run as DEBUG');
            return;
        }
        umask(0);
        $pid = pcntl_fork();
        if (-1 === $pid)
        {
            throw new SDMException('fork fail');
        }
        else if ($pid > 0)
        {
            exit(0);
        }
        if (-1 === posix_setsid())
        {
            throw new SDMException("setsid fail");
        }
        $pid = pcntl_fork();
        if (-1 === $pid)
        {
            throw new SDMException("fork fail");
        }
        else if (0 !== $pid)
        {
            exit(0);
        }
    }

    public static function end()
    {
        exit(0);
    }

}