<?php

use Indigo\Queue\Connector\BeanstalkdConnector;
use Indigo\Queue\Worker;

$connector = new BeanstalkdConnector('localhost');

$worker = new Worker('test', $connector);

// Jobs will receive this logger
$worker->setLogger(new Psr\Log\NullLogger);

// Pull ONE job from the queue and execute it.
// Optional parameter: timeout wait for a job for a certain time
$worker->work();

// Listen for jobs from queue
// Optional parameter: interval sleep for a certain time between empty pulls
// Optional parameter: timeout wait for a job for a certain time
$worker->listen();
