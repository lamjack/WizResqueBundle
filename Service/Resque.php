<?php
/**
 * Author: jack<linjue@wilead.com>
 * Date: 15/7/6
 */

namespace Wiz\ResqueBundle\Service;

use Monolog\Handler\RedisHandler;
use Monolog\Logger;
use Psr\Log\NullLogger;
use Wiz\ResqueBundle\Job\ContainerAwareJob;
use Wiz\ResqueBundle\Job\FailedJob;
use Wiz\ResqueBundle\Job\Job;
use Wiz\ResqueBundle\Model\Queue;
use Wiz\ResqueBundle\Model\Worker;
use Wiz\ResqueBundle\Resque\LogPlugin;

/**
 * Class Resque
 * @package Wiz\ResqueBundle\Service
 */
class Resque
{
    /**
     * @var array
     */
    private $redisConfiguration;

    /**
     * @var array
     */
    private $kernelOptions;

    /**
     * @var string
     */
    private $prefix = '';

    /**
     * Global retry storage
     *
     * @var array
     */
    private $globalRetryStorage = array();

    /**
     * Job retry storage
     *
     * @var array
     */
    private $jobRetryStorage = array();

    /**
     * @param array $kernelOptions
     */
    function __construct(array $kernelOptions)
    {
        $this->kernelOptions = $kernelOptions;
    }

    /**
     * @param $host
     * @param $port
     * @param $database
     */
    public function setBackend($host, $port, $database)
    {
        $this->redisConfiguration = array(
            'host' => $host,
            'port' => $port,
            'database' => $database
        );
        \Resque::setBackend($host . ':' . $port, $database);
    }

    /**
     * @param $prefix
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
        \Resque_Redis::prefix($this->prefix);
    }

    /**
     * @param array $globalRetryStorage
     */
    public function setGlobalRetryStorage(array $globalRetryStorage)
    {
        $this->globalRetryStorage = $globalRetryStorage;
    }

    /**
     * @param array $jobRetryStorage
     */
    public function setJobRetryStorage(array $jobRetryStorage)
    {
        $this->jobRetryStorage = $jobRetryStorage;
    }

    /**
     * 增加自定义日志插件
     *
     * @param Logger $logger
     * @param bool   $vverbose
     * @param int    $queueSizeLimit
     */
    public function addLogger(Logger $logger, $vverbose = false, $queueSizeLimit = 10)
    {
        \Wiz\ResqueBundle\Logger\Resque::init([
            'logger' => $logger,
            'vverbose' => $vverbose,
            'queueSizeLimit' => $queueSizeLimit
        ]);
    }

    /**
     * 入队
     *
     * @param Job  $job
     * @param bool $track_status
     * @param bool $get_token 是否返回token,并追踪状态
     *
     * @return null|\Resque_Job_Status
     */
    public function enqueue(Job $job, $track_status = false , $get_token = false)
    {
        $this->jobReady($job);

        $redis = new \Redis();
        $redis->pconnect($this->redisConfiguration['host'], $this->redisConfiguration['port']);
        $log = new Logger('workers');
        $logHandle = new RedisHandler($redis, $this->prefix . 'logs');
        $log->pushHandler($logHandle);
        LogPlugin::init([
            'logger' => $log,
            'vverbose' => true
        ]);

        $token = \Resque::enqueue($job->queue, get_class($job), $job->args, $track_status);

        if ($track_status) {
            if($get_token){
                // 如果返回token,则可以方便的通过token追踪job在队列中的执行状态
                return $token;
            }
            return new \Resque_Job_Status($token);
        }

        if($get_token){
            // 如果返回token,如果没开 track_status 即使有token也查不到job的状态,这点需要注意!
            return $token;
        }
        return null;
    }

    /**
     * 设置Job执行时间
     *
     * @param \Datetime|int $at
     * @param Job           $job
     */
    public function enqueueAt($at, Job $job)
    {
        $this->jobReady($job);
        \ResqueScheduler::enqueueAt($at, $job->queue, get_class($job), $job->args);
    }

    /**
     * 设置Job N秒后执行
     *
     * @param int $in 几秒后执行
     * @param Job $job
     */
    public function enqueueIn($in, Job $job)
    {
        $this->jobReady($job);
        \ResqueScheduler::enqueueIn($in, $job->queue, get_class($job), $job->args);
    }

    /**
     * @return array
     */
    public function getQueues()
    {
        return array_map(function ($queue) {
            return new Queue($queue);
        }, \Resque::queues());
    }

    /**
     * @param string $queue
     *
     * @return Queue
     */
    public function getQueue($queue)
    {
        return new Queue($queue);
    }

    /**
     * Get workers list
     *
     * @return array
     */
    public function getWorkers()
    {
        return array_map(function ($worker) {
            return new Worker($worker);
        }, \Resque_Worker::all());
    }

    /**
     * Get worker by id
     *
     * @param $id
     *
     * @return null|Worker
     */
    public function getWorker($id)
    {
        $worker = \Resque_Worker::find($id);
        if (!$worker) {
            return null;
        }
        return new Worker($worker);
    }

    /**
     *
     */
    public function pruneDeadWorkers()
    {
        $worker = new \Resque_Worker('temp');
        $worker->setLogger(new NullLogger());
        $worker->pruneDeadWorkers();
    }

    /**
     * @param $queue
     *
     * @return int
     */
    public function clearQueue($queue)
    {
        /** @var \Resque_Redis $redis */
        $redis = \Resque::redis();
        $size = $redis->llen('queue:' . $queue);
        $redis->del('queue:' . $queue);
        return $size;
    }

    /**
     * @return array
     */
    public function getFailedJobs()
    {
        $out = array();

        $jobs = call_user_func_array(
            array(\Resque::redis(), 'lrange'),
            array('failed', 0, -1)
        );

        foreach ($jobs as $job) {
            array_push($out, new FailedJob(json_decode($job, true)));
        }

        return $out;
    }

    /**
     * @return array
     */
    public function getDelayedJobDatetimes()
    {
        $out = array();

        $timestamps = call_user_func_array(
            array(\Resque::redis(), 'zrange'),
            array('delayed_queue_schedule', 0, -1));

        foreach ($timestamps as $timestamp) {
            $datetime = new \DateTime();
            $datetime->setTimestamp($timestamp);
            $out[] = array($datetime, call_user_func_array(
                array(\Resque::redis(), 'llen'),
                array('delayed:' . $timestamp)
            ));
        }

        return $out;
    }

    /**
     * @return mixed
     */
    public function getFirstDelayedJobDatetime()
    {
        $delayedDatetimes = $this->getDelayedJobDatetimes();
        if (count($delayedDatetimes) > 0)
            return $delayedDatetimes[0];
        else
            return null;
    }

    /**
     * @param $timestamp
     *
     * @return array
     */
    public function getJobsWithTimestamp($timestamp)
    {
        $out = array();

        $jobs = call_user_func_array(
            array(\Resque::redis(), 'lrange'),
            array('delayed:' . $timestamp, 0, -1)
        );

        foreach ($jobs as $job) {
            $out[] = json_decode($job, true);
        }

        return $out;
    }

    /**
     * @return int
     */
    public function getNumberOfDelayedJobs()
    {
        return \ResqueScheduler::getDelayedQueueScheduleSize();
    }

    /**
     * @param Job $job
     */
    protected function jobReady(Job $job)
    {
        if ($job instanceof ContainerAwareJob) {
            $job->setKernelOptions($this->kernelOptions);
        }

        $this->attachRetryStorage($job);
    }

    /**
     * @param Job $job
     */
    protected function attachRetryStorage(Job $job)
    {
        $class = get_class($job);

        // If has custom retry storage
        if (array_key_exists($class, $this->jobRetryStorage)) {
            $job->args[Job::RETRY_STORAGE] = $this->jobRetryStorage[$class];
        } else {
            if (count($this->globalRetryStorage)) {
                $job->args[Job::RETRY_STORAGE] = $this->globalRetryStorage;
            }
        }
    }
}