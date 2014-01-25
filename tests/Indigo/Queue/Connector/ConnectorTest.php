<?php

namespace Indigo\Queue\Connector;

abstract class ConnectorTest extends \PHPUnit_Framework_TestCase
{
    protected $connector;

    public function tearDown()
    {
        \Mockery::close();
    }

    public function testConnection()
    {
        $this->assertTrue($this->connector->isConnected());
    }
}
