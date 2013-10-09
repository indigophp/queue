<?php

namespace Phresque\Job;


use Pheanstalk_Job;
use Pheanstalk_Pheanstalk as Pheanstalk;

class BeanstalkdJob extends AbstractJob
{
    public function __construct(Pheanstalk $connector, Pheanstalk_Job $job)
    {
        $this->job = $job;
        $this->connector = $connector;
    }

    public function execute()
    {
        $payload = $this->getPayload();
        $this->runJob($payload);
    }

    public function delete()
    {
        $this->connector->delete($this->job);
    }

    public function bury()
    {
        $this->connector->bury($this->job);
    }

    public function release($delay = 0, $priority = Pheanstalk::DEFAULT_PRIORITY)
    {
        $this->connector->release($this->job, $priority, $delay);
    }

    public function attempts()
    {
        $stats = $this->connector->statsJob($this->job);

        return (int) $stats->reserves;
    }

    public function getPayload()
    {
        return json_decode($this->job->getData(), true);
    }

    public function __call($method, $params)
    {
        switch (true) {
            case is_callable(array($this->job, $method)):
                return call_user_func_array(array($this->job, $method), $params);
                break;
            case is_callable(array($this->connector, $method)):
                return call_user_func_array(array($this->connector, $method), $params);
                break;
            default:
                # code...
                break;
        }
    }
}
