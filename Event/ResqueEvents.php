<?php
/**
 * ResqueEvents.php
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
namespace Wiz\ResqueBundle\Event;

/**
 * Class ResqueEvents
 * @package Wiz\ResqueBundle\Event
 */
abstract class ResqueEvents
{
    /**
     * @var string
     */
    const BEFORE_WORKER_START_RUN = 'resque.before_worker_start_run';

    /**
     * @var string
     */
    const BEFORE_WORKER_STOP_RUN = 'resque.before_worker_stop_run';
}