<?php

/*
 * This file is part of the Indigo Queue package.
 *
 * (c) Indigo Development Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Indigo\Queue\Manager;

use Indigo\Queue\Connector\ConnectorInterface;
use Indigo\Queue\Exception\JobNotFoundException;
use Psr\Log\NullLogger;

/**
 * Abstract Job class
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 *
 * @codeCoverageIgnore
 */
abstract class AbstractManager implements ManagerInterface
{
    use \Psr\Log\LoggerAwareTrait;

    /**
     * Connector object
     *
     * @var ConnectorInterface
     */
    protected $connector;

    /**
     * Payload
     *
     * @var []
     */
    protected $payload = [];

    /**
     * Queue name
     *
     * @var string
     */
    protected $queue;

    /**
     * Config values
     *
     * @var []
     */
    protected $config = [
        'retry'  => 0,
        'delay'  => 0,
        'delete' => false,
    ];

    /**
     * Creates a new connector
     *
     * @param string             $queue
     * @param ConnectorInterface $connector
     */
    public function __construct($queue, ConnectorInterface $connector)
    {
        $this->queue = $queue;
        $this->connector = $connector;

        $this->setLogger(new NullLogger);
    }

    /**
     * {@inheritdoc}
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * {@inheritdoc}
     */
    public function getConnector()
    {
        return $this->connector;
    }


    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        // Get payload here, so we can work with the same data
        $payload = $this->getPayload();

        // Resolve job and delete on error
        try {
            $job = $this->resolve($payload['job']);
        } catch (JobNotFoundException $e) {
            $this->connector->delete($this);

            return false;
        }

        try {
            // Here comes the funny part: execute the job
            $execute = $job->execute($this);

            $this->log('debug', 'Job ' . $payload['job'] . ' finished');

            // Try to delete the job if auto delete is enabled
            $this->autoDelete();

            return $execute;
        } catch (\Exception $e) {
            $failure = $job->fail($this, $e);

            if ($failure === false) {
                $this->failureCallback();
            }
        }
    }

    /**
     * Resolves the job class
     *
     * @param string $class
     *
     * @return mixed
     *
     * @codeCoverageIgnore
     */
    protected function resolve($class)
    {
        if (class_exists($class) === false) {
            $message = 'Job ' . $class . ' is not found.';

            throw new JobNotFoundException($message);
            $this->log('error', $message);
        }

        $job = new $class;

        if (isset($job->config)) {
            $this->config = array_merge($this->config, $job->config);
        }

        return $job;
    }

    /**
     * Failure callback is not present or returned false
     *
     * @return boolean
     *
     * @codeCoverageIgnore
     */
    protected function failureCallback()
    {
        return $this->autoRetry() or $this->autoDelete();
    }

    /**
     * Tries to retry the job
     *
     * @return boolean
     *
     * @codeCoverageIgnore
     */
    protected function autoRetry()
    {
        if ($this->attempts() <= $this->config['retry']) {
            return $this->connector->release($this, $this->config['delay']);
        }
    }

    /**
     * Tries to delete the job
     *
     * @return boolean
     *
     * @codeCoverageIgnore
     */
    protected function autoDelete()
    {
        return $this->config['delete'] === true and $this->connector->delete($this);
    }

    /**
     * Always include payload as a context in logger
     *
     * @param string $level
     * @param string $message
     *
     * @codeCoverageIgnore
     */
    protected function log($level, $message)
    {
        return $this->logger->log($level, $message, $this->getPayload());
    }
}