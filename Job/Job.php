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
     * Get args
     *
     * @param string $key
     *
     * @return mixed|null
     */
    public function getArg($key)
    {
        if (array_key_exists($key, $this->args)) {
            return $this->args[$key];
        }

        return null;
    }

    /**
     * Get args
     *
     * @return array
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * Set args
     *
     * @param array $args
     */
    public function setArgs(array $args)
    {
        $this->args = array_merge($this->args, $args);
    }

    /**
     * Set arg
     *
     * @param string $key
     * @param string $value
     */
    public function setArg($key, $value)
    {
        $this->args[$key] = $value;
    }

    /**
     * @param $args
     *
     * @return int 如果运行成功请返回0
     */
    abstract public function run($args);
}