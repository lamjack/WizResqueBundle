<?php
/**
 * FailedJob.php
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author    jack <linjue@wilead.com>
 * @copyright 2007-15/12/15 WIZ TECHNOLOGY
 * @link      http://wizmacau.com
 * @link      http://jacklam.it
 * @link      https://github.com/lamjack
 * @version
 */

namespace Wiz\ResqueBundle\Job;

/**
 * Class FailedJob
 * @package Wiz\ResqueBundle\Job
 */
class FailedJob
{
    /**
     * @var array
     */
    protected $data;

    /**
     * FailedJob constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @return mixed
     */
    public function getFailedAt()
    {
        return $this->data['failed_at'];
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->data['payload']['class'];
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->data['payload']['id'];
    }

    /**
     * @return mixed
     */
    public function getQueueName()
    {
        return $this->data['queue'];
    }

    /**
     * @return mixed
     */
    public function getWorkerName()
    {
        return $this->data['worker'];
    }

    /**
     * @return mixed
     */
    public function getExceptionClass()
    {
        return $this->data['exception'];
    }

    /**
     * @return mixed
     */
    public function getError()
    {
        return $this->data['error'];
    }

    /**
     * @return mixed
     */
    public function getBacktrace()
    {
        return $this->data['backtrace'];
    }
}