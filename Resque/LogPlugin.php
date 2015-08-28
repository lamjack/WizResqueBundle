<?php
/**
 * Author: jack<linjue@wilead.com>
 * Date: 15/8/28
 */
namespace Wiz\ResqueBundle\Resque;

use Monolog\Logger;

/**
 * Class LogPlugin
 * @package Wiz\ResqueBundle\Resque
 */
class LogPlugin
{
    /**
     * @var bool - adds logging for beforeFork, afterFork and beforePerform
     */
    private static $_isVVerbose = false;

    /**
     * @var int - Log an error when the queueu goes over this limit
     */
    private static $_queueSizeLimit = 20;

    /**
     * @var Logger
     */
    private static $_logger = null;

    /**
     * @var array
     */
    private static $_messages = [
        'queueSize' => '[QUEUE_SIZE_ALERT] Queue size of "%s" is greater than %s (queue size: %s)',
        'afterEnqueue' => 'job %s was added to queue "%s" (queue size: %s)',
        'beforeFirstFork' => 'new worker started "%s" listening on queues: "%s"',
        'beforeFork' => 'new fork about to be created for job "%s"',
        'afterFork' => 'new fork successfully created "%s"',
        'beforePerform' => 'job preparing for execution "%s"',
        'afterPerform' => 'job successfully performed "%s" (queue size: %s)',
        'onFailure' => 'job "%s" threw an exception: "%s"',
    ];

    /**
     * Don't allow to initiate class
     * @throws \Exception
     */
    private function __construct()
    {
        throw new \Exception('instances not allowed');
    }

    /**
     *
     * @param Logger $logger
     */
    public static function setLogger(Logger $logger)
    {
        self::$_logger = $logger;
    }

    /**
     *
     * @return Logger $logger
     */
    public static function _getLogger()
    {
        return self::$_logger;
    }

    /**
     * initiates the logger and sets listeners
     *
     * @param array $params
     *
     * @throws \Exception
     */
    public static function init(array $params = [])
    {
        if (isset($params['logger'])) {
            self::setLogger($params['logger']);
        } else {
            throw new \Exception('Monolog\Logger must be set');
        }
        if (isset($params['vverbose'])) {
            self::$_isVVerbose = $params['vverbose'];
        }
        if (isset($params['queueSizeLimit']) && (intval($params['queueSizeLimit']) > 0)) {
            self::$_queueSizeLimit = $params['queueSizeLimit'];
        }
        \Resque_Event::listen('afterEnqueue', ['Wiz\ResqueBundle\Resque\LogPlugin', 'afterEnqueue']);
        \Resque_Event::listen('beforeFirstFork', ['Wiz\ResqueBundle\Resque\LogPlugin', 'beforeFirstFork']);
        \Resque_Event::listen('beforeFork', ['Wiz\ResqueBundle\Resque\LogPlugin', 'beforeFork']);
        \Resque_Event::listen('afterFork', ['Wiz\ResqueBundle\Resque\LogPlugin', 'afterFork']);
        \Resque_Event::listen('beforePerform', ['Wiz\ResqueBundle\Resque\LogPlugin', 'beforePerform']);
        \Resque_Event::listen('afterPerform', ['Wiz\ResqueBundle\Resque\LogPlugin', 'afterPerform']);
        \Resque_Event::listen('onFailure', ['Wiz\ResqueBundle\Resque\LogPlugin', 'onFailure']);
    }

    /**
     * Get queue size
     *
     * @param string $queueName
     *
     * @return int
     */
    private static function _getQueueSize($queueName)
    {
        return \Resque::size($queueName);
    }

    /**
     * logs an error when the queue size exceeds limit
     *
     * @param string $queueName
     */
    private static function _checkQueueSize($queueName)
    {
        $queueSize = self::_getQueueSize($queueName);
        if ((int)$queueSize > (int)self::$_queueSizeLimit) {
            self::_getLogger()->addInfo(sprintf(self::$_messages['queueSize'], $queueName, self::$_queueSizeLimit, $queueSize));
        }
    }

    /**
     * Resque plugin event
     *
     * @param string $className
     * @param array $args
     * @param string $queueName
     */
    public static function afterEnqueue($className, $args, $queueName)
    {
        $queueSize = self::_getQueueSize($queueName);
        self::_getLogger()->addInfo(sprintf(self::$_messages['afterEnqueue'], $className, $queueName, $queueSize));
        self::_checkQueueSize($queueName);
    }

    /**
     * Resque plugin event
     *
     * @param \Resque_Worker $worker
     */
    public static function beforeFirstFork(\Resque_Worker $worker)
    {
        self::_getLogger()->addInfo(sprintf(self::$_messages['beforeFirstFork'], $worker, implode(', ', $worker->queues(false))));
    }

    /**
     * Resque plugin event
     *
     * @param \Resque_Job $job
     */
    public static function beforeFork(\Resque_Job $job)
    {
        if (self::$_isVVerbose) {
            self::_getLogger()->addInfo(sprintf(self::$_messages['beforeFork'], $job));
        }
    }

    /**
     * Resque plugin event
     *
     * @param \Resque_job $job
     */
    public static function afterFork(\Resque_Job $job)
    {
        if (self::$_isVVerbose) {
            self::_getLogger()->addInfo(sprintf(self::$_messages['afterFork'], $job));
        }
    }

    /**
     * Resque plugin event
     *
     * @param \Resque_Job $job
     */
    public static function beforePerform(\Resque_Job $job)
    {
        if (self::$_isVVerbose) {
            self::_getLogger()->addInfo(sprintf(self::$_messages['beforePerform'], $job));
        }
    }

    /**
     * Resque plugin event
     *
     * @param \Resque_Job $job
     */
    public static function afterPerform(\Resque_Job $job)
    {
        $queueSize = self::_getQueueSize($job->queue);
        self::_getLogger()->addInfo(sprintf(self::$_messages['afterPerform'], $job, $queueSize));
    }

    /**
     * Resque plugin event
     *
     * @param \Exception $e
     * @param \Resque_Job $job
     */
    public static function onFailure(\Exception $e, \Resque_Job $job)
    {
        self::_getLogger()->addError(sprintf(self::$_messages['onFailure'], $job, $e->getMessage()));
    }
}