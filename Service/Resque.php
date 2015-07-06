<?php
/**
 * Author: jack<linjue@wilead.com>
 * Date: 15/7/6
 */

namespace Wiz\ResqueBundle\Service;

use Wiz\ResqueBundle\Job\ContainerAwareJob;
use Wiz\ResqueBundle\Job\Job;

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
}