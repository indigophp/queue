<?php

namespace Phresque;

use Phresque\Queue\QueueInterface;
use Phresque\Job\JobInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class Worker
{
    protected $queue;

    /**
     * Logger instance
     *
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct($queue, $driver = null, $connector = null)
    {
        if ($queue instanceof QueueInterface) {
            $this->setQueue($queue);
        } else {
            $this->resolveQueue($queue, $driver, $connector);
        }
    }

    public function getQueue()
    {
        return $this->queue;
    }

    public function setQueue(QueueInterface $queue)
    {
        $this->queue = $queue;
    }

    public function resolveQueue($queue, $driver, $connector = null)
    {
        $driver = 'Phresque\\Queue\\' . trim(ucfirst(strtolower($driver))) . 'Queue';
        $queue  = strtolower($queue);

        $driver = new $driver($queue, $connector);
        $this->setQueue($driver);
    }

    public function pop()
    {
        return $this->queue->pop();
    }

    public function listen($memory = 128)
    {
        while (true) {
            $job = $this->pop();

            if ((memory_get_usage() / 1024 / 1024) > $memory) {
                die;
            }
        }
    }

    public function work()
    {
        $job = $this->queue->pop();
        if ($job instanceof JobInterface) {
            $job->execute();
        }
    }

    /**
     * Sets a logger instance on the object
     *
     * @param LoggerInterface $logger
     * @return null
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}
