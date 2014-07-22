<?php

/*
 * This file is part of the Indigo Queue package.
 *
 * (c) Indigo Development Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Indigo\Queue\Connector;

use Indigo\Queue\Manager\ManagerInterface;

/**
 * Connector Inteface
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
interface ConnectorInterface
{
    /**
     * Checks whether connection is available
     *
     * @return boolean
     */
    public function isConnected();

    /**
    * Pushes a new job onto the queue
    *
    * @param string $queue
    * @param Job    $job
    *
    * @return mixed
    */
    public function push($queue, Job $job);

    /**
    * Pushes a new job onto the queue after a delay
    *
    * @param string  $queue
    * @param integer $delay
    * @param Job     $job
    *
    * @return mixed
    */
    public function delayed($queue, $delay, Job $job);

    /**
    * Pops the next job off of the queue
    *
    * @param string  $queue   Name of the queue
    * @param integer $timeout Wait timeout
    *
    * @return ManagerInterface
    *
    * @throws NoJobFoundException If no job can be returned
    */
    public function pop($queue, $timeout = 0);

    /**
     * Returns the count of jobs
     *
     * @return integer
     */
    public function count();

    /**
     * Deletes job from queue
     *
     * @param ManagerInterface $job Job to delete
     *
     * @return boolean Always true
     */
    public function delete(ManagerInterface $job);

    /**
     * Clears the queue
     *
     * @param string $queue
     *
     * @return boolean Always true
     */
    public function clear($queue);

    /**
     * Releases a job back to the queue
     *
     * @param ManagerInterface $job   Job to release
     * @param integer          $delay Delay the job with x seconds, 0 means no delay
     *
     * @return boolean Always true
     */
    public function release(ManagerInterface $job, $delay = 0);
}
