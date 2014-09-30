<?php

/*
 * This file is part of the Indigo Queue package.
 *
 * (c) Indigo Development Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Indigo\Queue\Adapter\AbstractAdapter;
use Indigo\Queue\Message;
use Indigo\Queue\Manager;

/**
 * Dummy Adapter
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
class DummyAdapter extends AbstractAdapter
{
    /**
     * {@inheritdoc}
     */
    protected $managerClass = 'Fake\\Class';

    /**
     * {@inheritdoc}
     */
    public function isConnected()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function push($queue, Message $message)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function pop($queue, $timeout = 0)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function count($queue)
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Manager $manager)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear($queue)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function release(Manager $manager, $delay = 0)
    {
        return true;
    }
}