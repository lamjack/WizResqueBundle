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
    /**
     * @var string Queue name
     */
    public $queue = 'default';

    /**
     * @var array The job args
     */
    public $args = array();

    /**
     * Set up environment for this job
     */
    public function setUp()
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
     * Remove environment for this job
     */
    public function tearDown()
    {
    }

    abstract public function run($args);
}