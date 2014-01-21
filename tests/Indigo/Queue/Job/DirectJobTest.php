<?php

namespace Indigo\Queue\Job;

class DirectJobTest extends JobTest
{
    public function setUp()
    {
        $this->job = new DirectJob(array(
            'job' => 'Test',
            'data' => array(),
        ));
    }

    public function testJob()
    {
        $job = new DirectJob(array());

        $this->assertNull($job->delete());
        $this->assertNull($job->bury());
        $this->assertNull($job->release());
        $this->assertEquals(1, $job->attempts());
    }
}