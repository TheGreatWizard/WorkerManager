<?php

if (isset($argv[1])) {
    if (file_exists($argv[1])) {
        $bootstrap = $argv[1];
    }
}


chdir(__DIR__);
$bootstrap = isset($bootstrap) ? $bootstrap : __DIR__ . '/../../bootstrap.php';

require $bootstrap;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Startup\Debugger;
use \Workers\Manager;

$queueName = $argv[2] ?? Manager::DEFAULT_QUEUE_NAME;
$rabbitHost = $argv[3] ?? Manager::DEFAULT_RABBIT_HOST;
$rabbitPort = $argv[4] ?? Manager::DEFAULT_RABBIT_PORT;

$connection = new AMQPStreamConnection($rabbitHost, $rabbitPort, 'guest', 'guest');
$channel = $connection->channel();
$channel->queue_declare($queueName, false, false, false, false);

$callback = function ($msg) use ($queueName, $channel) {
    $cmd = null;
    chdir(__DIR__);
    Debugger::debug("RUNNER CALLBACK[" . getmypid() . "], started running: {$msg->body}");
    try {
        $w = unserialize($msg->body);
    } catch (\Exception $e) {
        Debugger::debug("RUNNER CALLBACK[" . getmypid() . "] ERROR, unserialize: {$e->getMessage()}");
        return;
    }
    try {
        $cmd = $w->run();
        Debugger::debug("RUNNER CALLBACK[" . getmypid() . "], success: {$msg->body}");
    } catch (\Exception $e) {
        Debugger::debug("RUNNER CALLBACK[" . getmypid() . "] ERROR, {$e->getMessage()}");
    }

    if ($cmd === "republish") {
        $msg = new AMQPMessage($msg->body, array('delivery_mode' => 2));
        $channel->basic_publish($msg, '', $queueName);
    }

};

$channel->basic_qos(null, 1, null);
$channel->basic_consume($queueName, '', false, true, false, false, $callback);

Debugger::debug("RUNNER PROCESS[" . getmypid() . "], started...");

while (count($channel->callbacks)) {
    $channel->wait();
}
$channel->close();
$connection->close();

Debugger::debug("RUNNER PROCESS[" . getmypid() . "], stopped...");

