<?php
/**
 * Resque.php
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author    jack <linjue@wilead.com>
 * @copyright 2007-16/9/19 WIZ TECHNOLOGY
 * @link      http://wizmacau.com
 * @link      https://jacklam.it
 * @link      https://github.com/lamjack
 * @version
 */
namespace Wiz\ResqueBundle\Logger;

use Monolog\Logger;

/**
 * Class Resque
 * @package Wiz\ResqueBundle\Logger
 */
class Resque
{
    /**
     * @var Logger
     */
    private static $logger = null;

    /**
     * 是否记录beforeFork, afterFork和beforePerform三个事件
     *
     * @var bool
     */
    private static $isVVerbose = false;

    /**
     * 未处理任务上限,当超过这个数字时记录一个error
     *
     * @var int
     */
    private static $queueSizeLimit = 20;

    /**
     * @var array
     */
    private static $messages = [
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
     * 不允许实例化这个类
     *
     * @throws \Exception
     */
    private function __construct()
    {
        throw new \Exception('instances not allowed');
    }

    /**
     * @param Logger $logger
     */
    public static function setLogger(Logger $logger)
    {
        self::$logger = $logger;
    }

    /**
     * @return Logger $logger
     */
    public static function getLogger()
    {
        return self::$logger;
    }

    /**
     * 初始化并设置监听器
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
            self::$isVVerbose = $params['vverbose'];
        }

        if (isset($params['queueSizeLimit']) && (intval($params['queueSizeLimit']) > 0)) {
            self::$queueSizeLimit = (int)$params['queueSizeLimit'];
        }

        /**
         * @see https://github.com/chrisboulton/php-resque#events
         */
        \Resque_Event::listen('afterEnqueue', ['Wiz\ResqueBundle\Logger\Resque', 'afterEnqueue']);
        \Resque_Event::listen('beforeFirstFork', ['Wiz\ResqueBundle\Logger\Resque', 'beforeFirstFork']);
        \Resque_Event::listen('beforeFork', ['Wiz\ResqueBundle\Logger\Resque', 'beforeFork']);
        \Resque_Event::listen('afterFork', ['Wiz\ResqueBundle\Logger\Resque', 'afterFork']);
        \Resque_Event::listen('beforePerform', ['Wiz\ResqueBundle\Logger\Resque', 'beforePerform']);
        \Resque_Event::listen('afterPerform', ['Wiz\ResqueBundle\Logger\Resque', 'afterPerform']);
        \Resque_Event::listen('onFailure', ['Wiz\ResqueBundle\Logger\Resque', 'onFailure']);
    }

    /**
     * 获取队列长度
     *
     * @param string $queueName
     *
     * @return int
     */
    private static function getQueueSize($queueName)
    {
        return \Resque::size($queueName);
    }

    /**
     * 检测队列长度是否超过上限值,超过则记录
     *
     * @param string $queueName
     */
    private static function checkQueueSize($queueName)
    {
        $size = self::getQueueSize($queueName);
        if ((int)$size > (int)self::$queueSizeLimit) {
            self::getLogger()->addInfo(sprintf(self::$messages['queueSize'], $queueName, self::$queueSizeLimit, $size));
        }
    }

    /**
     * Called after a job has been queued using the Resque::enqueue method
     *
     * @param string $className
     * @param array  $args
     * @param string $queueName
     * @param string $id
     */
    public static function afterEnqueue($className, $args, $queueName, $id)
    {
        $size = self::getQueueSize($queueName);
        self::getLogger()->addInfo(sprintf(self::$messages['afterEnqueue'], $className, $queueName, $size));
        self::checkQueueSize($queueName);
    }

    /**
     * Called once, as a worker initializes
     *
     * @param \Resque_Worker $worker
     */
    public static function beforeFirstFork(\Resque_Worker $worker)
    {
        self::getLogger()->addInfo(sprintf(self::$messages['beforeFirstFork'], $worker, implode(', ', $worker->queues(false))));
    }

    /**
     * Called before php-resque forks to run a job
     *
     * @param \Resque_Job $job
     */
    public static function beforeFork(\Resque_Job $job)
    {
        if (self::$isVVerbose) {
            self::getLogger()->addInfo(sprintf(self::$messages['beforeFork'], $job));
        }
    }

    /**
     * Called after php-resque forks to run a job (but before the job is run)
     *
     * @param \Resque_Job $job
     */
    public static function afterFork(\Resque_Job $job)
    {
        if (self::$isVVerbose) {
            self::getLogger()->addInfo(sprintf(self::$messages['afterFork'], $job));
        }
    }

    /**
     * Called before the setUp and perform methods on a job are run
     *
     * @param \Resque_Job $job
     */
    public static function beforePerform(\Resque_Job $job)
    {
        if (self::$isVVerbose) {
            self::getLogger()->addInfo(sprintf(self::$messages['beforePerform'], $job));
        }
    }

    /**
     * Called after the perform and tearDown methods on a job are run
     *
     * @param \Resque_Job $job
     */
    public static function afterPerform(\Resque_Job $job)
    {
        $size = self::getQueueSize($job->queue);
        self::getLogger()->addInfo(sprintf(self::$messages['afterPerform'], $job, $size));
    }

    /**
     * Called whenever a job fails
     *
     * @param \Exception  $e
     * @param \Resque_Job $job
     */
    public static function onFailure(\Exception $e, \Resque_Job $job)
    {
        self::getLogger()->addInfo(sprintf(self::$messages['onFailure'], $job, $e->getMessage()));
    }
}