<?php

namespace Test\Functional;

use Indigo\Queue\Connector\RabbitConnector;
use Indigo\Queue\Job;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPRuntimeException;

/**
 * Tests for RabbitConnector
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 *
 * @coversDefaultClass Indigo\Queue\Connector\RabbitConnector
 * @group              Queue
 * @group              Connector
 * @group              Rabbit
 */
class RabbitConnectorTest extends AbstractMQConnectorTest
{
    public function _before()
    {
        $host = $GLOBALS['rabbit_host'];
        $port = $GLOBALS['rabbit_port'];
        $user = $GLOBALS['rabbit_user'];
        $pass = $GLOBALS['rabbit_pass'];
        $vhost = $GLOBALS['rabbit_vhost'];

        try {
            $amqp = new AMQPStreamConnection($host, $port, $user, $pass, $vhost);
        } catch (AMQPRuntimeException $e) {
        }

        $this->connector = new RabbitConnector($amqp);

        if ($this->connector->isConnected() === false) {
            $this->markTestSkipped(
                'RabbitMQ connection not available.'
            );
        }
    }

    /**
     * Makes sure the queue is empty
     */
    public function clear()
    {
        if ($this->connector->count('test') > 0) {
            $this->connector->clear('test');
        }
    }

    /**
     * @covers ::isConnected
     */
    public function testConnected()
    {
        $this->assertTrue($this->connector->isConnected());
    }

    /**
     * @covers       ::push
     * @dataProvider jobProvider
     */
    public function testPush(Job $job)
    {
        $this->assertNull($this->connector->push('test', $job));
    }

    /**
     * @covers       ::delayed
     * @dataProvider jobProvider
     */
    public function testDelayed(Job $job)
    {
        $this->assertNull($this->connector->delayed('test', 1, $job));
    }

    /**
     * @covers                   ::pop
     * @covers                   Indigo\Queue\Exception\QueueEmptyException
     * @expectedException        Indigo\Queue\Exception\QueueEmptyException
     * @expectedExceptionMessage Queue test is empty.
     */
    public function testEmptyPop()
    {
        $this->connector->pop('test');
    }

    /**
     * @covers ::count
     */
    public function testCount()
    {
        $this->clear();

        $jobs = $this->pushJobs();

        $this->assertEquals(count($jobs), $this->connector->count('test'));
    }

    /**
     * @covers ::clear
     */
    public function testClear()
    {
        $this->clear();

        $jobs = $this->pushJobs();

        $this->assertEquals(count($jobs), $this->connector->count('test'));
        $this->assertTrue($this->connector->clear('test'));
        $this->assertEquals(0, $this->connector->count('test'));
    }

    /**
     * @covers       ::release
     * @dataProvider jobProvider
     */
    public function testReleaseDelayed(Job $job)
    {
        $this->connector->push('test', $job);

        $manager = $this->connector->pop('test');

        $this->assertTrue($this->connector->release($manager, 1));
    }

    /**
     * @covers ::getChannel
     * @covers ::regenerateChannel
     */
    public function testChannel()
    {
        $expected = $this->connector->getChannel();

        $this->assertInstanceOf('PhpAmqpLib\\Channel\\AMQPChannel', $expected);

        $actual = $this->connector->regenerateChannel();

        $this->assertInstanceOf('PhpAmqpLib\\Channel\\AMQPChannel', $actual);

        $this->assertEquals($expected, $actual);
    }
}
