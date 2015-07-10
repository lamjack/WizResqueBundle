<?php
/**
 * Author: jack<linjue@wilead.com>
 * Date: 15/7/6
 */

namespace Wiz\ResqueBundle\Service;

use Psr\Log\NullLogger;
use Wiz\ResqueBundle\Job\ContainerAwareJob;
use Wiz\ResqueBundle\Job\Job;
use Wiz\ResqueBundle\Model\Queue;
use Wiz\ResqueBundle\Model\Worker;

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
        \Resque_Redis::prefix($prefix);
    }

    /**
     * 入队
     *
     * @param Job $job
     * @param bool $track_status
     *
     * @return null|\Resque_Job_Status
     */
    public function enqueue(Job $job, $track_status = false)
    {
        if ($job instanceof ContainerAwareJob) {
            $job->setKernelOptions($this->kernelOptions);
        }

        $result = \Resque::enqueue($job->queue, get_class($job), $job->args, $track_status);

        if ($track_status) {
            return new \Resque_Job_Status($result);
        }

        return null;
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
        /** @var \Redis $redis */
        $redis = \Resque::redis();
        $size = $redis->lLen('queue:' . $queue);
        $redis->del('queue:' . $queue);
        return $size;
    }
}