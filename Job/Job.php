<?php
/**
 * Author: jack<linjue@wilead.com>
 * Date: 15/7/6
 */

namespace Wiz\ResqueBundle\Job;

/**
 * Class Job
 * @package Wiz\ResqueBundle\Job
 */
abstract class Job
{
    const RETRY_STORAGE = '_retry_storage';
    const RETRY_ATTEMPT = '_retry_attempt';

    /**
     * @var string Queue name
     */
    public $queue;

    /**
     * @var array The job args
     */
    public $args = array();

    /**
     * Job constructor.
     *
     * @param string $queue
     */
    public function __construct($queue = 'default')
    {
        $this->queue = $queue;
    }

    /**
     * Set up environment for this job
     */
    public function setUp()
    {

    }

    /**
     * Remove environment for this job
     */
    public function tearDown()
    {
    }

    /**
     * Run job
     */
    public function perform()
    {
        $this->run($this->args);
    }


    /**
     * @param $args
     *
     * @return mixed
     */
    abstract public function run($args);
}