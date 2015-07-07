<?php
/**
 * Author: jack<linjue@wilead.com>
 * Date: 15/7/7
 */

namespace Wiz\ResqueBundle\Model;

/**
 * Class Queue
 * @package Wiz\ResqueBundle\Model
 */
class Queue
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name
     */
    function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return \Resque::size($this->name);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param int $start
     * @param int $stop
     *
     * @return array
     * @throws \Resque_Exception
     */
    public function getJobs($start = 0, $stop = -1)
    {
        /** @var \Redis $redis */
        $redis = \Resque::redis();
        $jobs = $redis->lRange('queue:' . $this->name, $start, $stop);
        $result = array();
        foreach ($jobs as $job) {
            $job = new \Resque_Job($this->name, json_decode($job, true));
            $result[] = $job->getInstance();
        }
        return $result;
    }
}