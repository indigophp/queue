<?php
/*
 * This file is part of the Indigo Queue package.
 *
 * (c) Indigo Development Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Indigo\Queue\Job;

use Indigo\Queue\Connector\ConnectorInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * Abstract Job class
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
abstract class AbstractJob implements JobInterface, LoggerAwareInterface
{
    /**
     * Connector object
     *
     * @var ConnectorInterface
     */
    protected $connector;

    /**
     * Payload
     *
     * @var array
     */
    protected $payload = array();

    /**
     * Queue name
     *
     * @var string
     */
    protected $queue;

    /**
     * Logger instance
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Job object
     *
     * @var object
     */
    protected $job;

    /**
     * Execute callback name
     *
     * @var string
     */
    protected $execute = 'execute';

    /**
     * Failure callback name
     *
     * @var string
     */
    protected $failure = 'failure';

    /**
     * Config values
     *
     * @var array
     */
    protected $config = array(
        'retry'  => 0,
        'delay'  => 0,
        'delete' => false
    );

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        // Get payload here, so we can work with the same data
        $payload = $this->getPayload();

        // Resolve job and delete on error
        try {
            $this->resolve($payload);
        } catch (Exception $e) {
            $this->connector->delete($this);

            return false;
        }

        try {
            return $this->callExecute($payload);
        } catch (Exception $e) {
            return $this->callFailure($e, $payload);
        }
    }

    /**
     * Resolve the job
     *
     * @param  array   $payload Job payload
     * @return boolean
     */
    public function resolve(array $payload)
    {
        $job = $this->parseJob($payload['job']);

        list($job, $execute, $failure) = $job;

        $this->job = $this->resolveClass($job, $payload['data']);
        $this->execute = $this->resolveCallback($execute, true);
        $this->failure = $this->resolveCallback($failure);

        if (isset($job->config)) {
            $this->config = array_merge($this->config, $job->config);
        }
    }

    /**
     * Parse job string
     *
     * @param  string $job Job@execute:failure
     * @return array
     */
    protected function parseJob($job)
    {
        $job = preg_split('/[:@]/', $job);

        // Make sure we have default values
        return $job + array($this->job, $this->execute, $this->failure);
    }

    /**
     * Resolve class
     *
     * @param  string $class Class name
     * @param  array  $data  Data
     * @return object
     */
    protected function resolveClass($class, $data)
    {
        if (!class_exists($class)) {
            $this->logException('error', 'Job ' . $class . ' is not found.');
        }

        return new $class($this, $sata);
    }

    /**
     * Resolve callback from string
     *
     * @param  string  $callback
     * @param  boolean $exception
     * @return mixed   Callable if callable, false otherwise
     */
    protected function resolveCallback($callback, $exception = false)
    {
        $callback = array($this->job, $callback);

        if (is_callable($callback)) {
            return $callback;
        }

        if ($exception) {
            $this->logException(
                'error',
                'Method ' . $callback[1] . ' is not found in job ' . get_class($callback[0]) . '.'
            );
        }

        return false;
    }

    /**
     * Run execute callback
     *
     * @param  array $payload Job payload
     * @return mixed
     */
    protected function callExecute(array $payload)
    {
        // Here comes the funny part: execute the job
        $execute = call_user_func($this->execute, $this, $payload['data']);

        $this->log('debug', 'Job ' . $payload['job'] . ' finished');

        // Try to delete the job if auto delete is enabled
        $this->autoDelete();

        return $execute;
    }

    /**
     * Run failure callback
     * This should only be run if callExecute throws an exception
     *
     * @param  Exception $e
     * @param  array     $payload Job payload
     * @return mixed
     */
    protected function callFailure(Exception $e, array $payload)
    {
        if (!$this->failure) {
            $this->log(
                'debug',
                'Failure callback ' . $this->failure .
                ' is not found in job ' . get_class($this->job) . '.'
            );

            $failure = false;
        } else {
            $failure = call_user_func($this->failure, $this, $e, $payload['data']);
        }

        if ($failure === false) {
            $this->failureCallback();
        }
    }

    /**
     * Failure callback is not present or returned false
     *
     * @return boolean
     */
    protected function failureCallback()
    {
        return $this->autoRetry() or $this->autoDelete();
    }

    /**
     * Try to retry the job
     *
     * @return boolean
     */
    protected function autoRetry()
    {
        if ($this->attempts() <= $this->config['retry']) {
            return $this->connector->release($this, $this->config['delay']);
        }
    }

    /**
     * Try to delete the job
     *
     * @return boolean
     */
    protected function autoDelete()
    {
        return $this->config['delete'] === true and $this->connector->delete($this);
    }

    /**
     * Get connector
     *
     * @return ConnectorInterface
     */
    public function getConnector()
    {
        return $this->connector;
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
    public function setPayload(array $payload)
    {
        $this->payload = $payload;

        return $this;
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
    public function setQueue($queue)
    {
        $this->queue = $queue;

        return $this;
    }

    /**
     * Get logger
     *
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Sets a logger instance on the object
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Always include payload as a context in logger
     *
     * @param string $level   Log level
     * @param string $message
     */
    protected function log($level, $message)
    {
        return $this->logger->log($level, $message, $this->getPayload());
    }

    /**
     * Log a message and throw Exception
     *
     * @param  string    $level   Log level
     * @param  string    $message
     * @throws Exception
     */
    protected function logException($level, $message)
    {
        $this->log($level, $message);
        throw new Exception($message);
    }
}
