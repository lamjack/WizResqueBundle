<?php
/**
 * Author: jack<linjue@wilead.com>
 * Date: 15/7/8
 */

namespace Wiz\ResqueBundle\Model;

/**
 * Class Worker
 * @package Wiz\ResqueBundle\Model
 */
final class Worker
{
    /**
     * @var \Resque_Worker
     */
    private $worker;

    function __construct(\Resque_Worker $worker)
    {
        $this->worker = $worker;
    }

    public function getId()
    {
        return (string)$this->worker;
    }

    /**
     * Quit Worker
     *
     * @return bool
     */
    public function stop()
    {
        $parts = explode(':', $this->getId());
        $status = posix_kill($parts[1], SIGQUIT);
        return $status;
    }

    /**
     * Kill worker
     *
     * @return bool
     */
    public function kill()
    {
        $parts = explode(':', $this->getId());
        if (null === $this->getCurrentJob())
            return posix_kill($parts[1], SIGKILL);
        return false;
    }

    /**
     * @return array
     */
    public function getQueues()
    {
        return $this->worker->queues();
    }

    /**
     * @return int
     */
    public function getProcessedCount()
    {
        return $this->worker->getStat('processed');
    }

    /**
     * @return int
     */
    public function getFailedCount()
    {
        return $this->worker->getStat('failed');
    }

    /**
     * @return \DateTime|null
     */
    public function getCurrentJobStartedAt()
    {
        $job = $this->worker->job();
        if (!$job) {
            return null;
        }
        return new \DateTime($job['run_at']);
    }

    /**
     * @return null|object
     * @throws \Resque_Exception
     */
    public function getCurrentJob()
    {
        $job = $this->worker->job();
        if (!$job) {
            return null;
        }
        $job = new \Resque_Job($job['queue'], $job['payload']);
        return $job->getInstance();
    }
}