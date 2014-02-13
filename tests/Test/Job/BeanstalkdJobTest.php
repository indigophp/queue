<?php

namespace Indigo\Queue\Test\Job;

use Indigo\Queue\Job\BeanstalkdJob;
use Indigo\Queue\Connector\BeanstalkdConnector;
use Pheanstalk_Pheanstalk as Pheanstalk;

class BeanstalkdJobTest extends JobTest
{
    public function setUp()
    {
        $host = isset($GLOBALS['beanstalkd_host']) ? $GLOBALS['beanstalkd_host'] : 'localhost';
        $port = isset($GLOBALS['beanstalkd_port']) ? $GLOBALS['beanstalkd_port'] : 11300;

        $pheanstalk = new Pheanstalk($host, $port);

        $this->connector = new BeanstalkdConnector($pheanstalk);

        if (!$this->connector->isConnected()) {
            $this->markTestSkipped(
              'Beanstald connection not available.'
            );
        }
    }

    /**
     * @dataProvider payloadProvider
     */
    public function testJob($payload, $return)
    {
        $this->connector->push('test', $payload);

        $job = $this->connector->pop('test');

        if ($job instanceof BeanstalkdJob) {
            $this->assertEquals(1, $job->attempts());
            $this->assertInstanceOf(
                'Pheanstalk_Job',
                $job->getPheanstalkJob()
            );

            $payload = $job->getPayload();
        } else {
            $this->assertNull($job);
        }

        return $job;
    }
}
