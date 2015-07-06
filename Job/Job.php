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

    abstract public function run(&$args);
}