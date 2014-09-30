<?php

/*
 * This file is part of the Indigo Queue package.
 *
 * (c) Indigo Development Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Indigo\Queue\Adapter;

use Indigo\Queue\Adapter;
use Indigo\Queue\Manager;
use Indigo\Queue\Message;
use Indigo\Queue\Exception\QueueEmptyException;
use Pheanstalk\Job as PheanstalkJob;
use Pheanstalk\PheanstalkInterface;
use Pheanstalk\Exception\ServerException;

/**
 * Beanstalkd Adapter
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
class BeanstalkdAdapter extends AbstractAdapter
{
    /**
     * {@inheritdoc}
     */
    protected $options = [
        'delay'    => PheanstalkInterface::DEFAULT_DELAY,
        'timeout'  => PheanstalkInterface::DEFAULT_TTR,
        'priority' => PheanstalkInterface::DEFAULT_PRIORITY,
    ];

    /**
     * Pheanstalk object
     *
     * @var PheanstalkInterface
     */
    protected $pheanstalk;

    /**
     * Creates a new BeanstalkdAdapter
     *
     * @param PheanstalkInterface $pheanstalk
     */
    public function __construct(PheanstalkInterface $pheanstalk)
    {
        $this->pheanstalk = $pheanstalk;

        parent::__construct();
    }

    /**
     * Returns the Pheanstalk object
     *
     * @return PheanstalkInterface
     */
    public function getPheanstalk()
    {
        return $this->pheanstalk;
    }

    /**
     * Sets the Pheanstalk object
     *
     * @param PheanstalkInterface $pheanstalk
     *
     * @return BeanstalkdAdapter
     */
    public function setPheanstalk(PheanstalkInterface $pheanstalk)
    {
        $this->pheanstalk = $pheanstalk;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected()
    {
        return $this->pheanstalk->getConnection()->isServiceListening();
    }

    /**
     * {@inheritdoc}
     */
    public function push($queue, Message $message)
    {
        $options = $this->options + $message->getOptions();

        return $this->pheanstalk->putInTube(
            $queue,
            json_encode($message->createPayload()),
            $options['priority'],
            $options['delay'],
            $options['timeout']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function pop($queue, $timeout = 0)
    {
        $job = $this->pheanstalk->reserveFromTube($queue, $timeout);

        if ($job instanceof PheanstalkJob) {
            return new $this->managerClass($queue, $job, $this);
        }

        throw new QueueEmptyException($queue);
    }

    /**
     * {@inheritdoc}
     */
    public function count($queue)
    {
        $stats = $this->pheanstalk->statsTube($queue);

        return $stats['current-jobs-ready'];
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Manager $manager)
    {
        $this->pheanstalk->delete($manager->getPheanstalkJob());

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear($queue)
    {
        $this->doClear($queue, 'ready');
        $this->doClear($queue, 'buried');
        $this->doClear($queue, 'delayed');

        return true;
    }

    /**
     * Clears a specific state
     *
     * @param string $queue
     * @param string $state
     *
     * @return boolean
     *
     * @codeCoverageIgnore
     */
    protected function doClear($queue, $state)
    {
        try {
            while ($item = $this->pheanstalk->{'peek'.$state}($queue)) {
                $this->pheanstalk->delete($item);
            }
        } catch (ServerException $e) {
        }

        return true;
    }

    /**
     * Bury the job
     *
     * @param Manager $manager
     *
     * @return boolean Always true
     */
    public function bury(Manager $manager)
    {
        $this->pheanstalk->bury($manager->getPheanstalkJob());

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @param integer|null $priority
     */
    public function release(Manager $manager, $delay = 0, $priority = PheanstalkInterface::DEFAULT_PRIORITY)
    {
        $this->pheanstalk->release($manager->getPheanstalkJob(), $priority, $delay);

        return true;
    }
}
