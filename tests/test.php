<?php

$loader = require_once __DIR__ . "/../vendor/autoload.php";
$loader->add('Queue\\', __DIR__);

use Phresque\Worker;
use Phresque\Queue\BeanstalkdQueue;

$queue = new BeanstalkdQueue('default', array('host' => '127.0.0.1'));

$worker = new Worker($queue, 'beanstalkd');
// $worker->setQueue($queue);
// $worker->resolveQueue('beanstalkd', 'default', array('host' => '127.0.0.1'));
// var_dump($worker->getQueue()); exit;