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

$queue_name = "tube";
$rabbit_host = "localhost";
$rabbit_port = 5672;

$connection = new AMQPStreamConnection($rabbit_host, $rabbit_port, 'guest', 'guest');
$channel = $connection->channel();
$channel->queue_declare($queue_name, false, false, false, false);

$callback = function ($msg) use ($queue_name, $channel) {
    $cmd = null;
    chdir(__DIR__);
    Debugger::debug("WORKER CALLBACK[" . getmypid() . "], started running: {$msg->body}");
    try {
        $w = unserialize($msg->body);
    } catch (\Exception $e) {
        Debugger::debug("WORKER CALLBACK[" . getmypid() . "] ERROR, unserialize: {$e->getMessage()}");
        return;
    }
    try {
        $cmd = $w->run();
        Debugger::debug("WORKER CALLBACK[" . getmypid() . "], success: {$msg->body}");
    } catch (\Exception $e) {
        Debugger::debug("WORKER CALLBACK[" . getmypid() . "] ERROR, {$e->getMessage()}");
    }

    if ($cmd === "republish") {
        $msg = new AMQPMessage($msg->body, array('delivery_mode' => 2));
        $channel->basic_publish($msg, '', $queue_name);
    }

};

$channel->basic_qos(null, 1, null);
$channel->basic_consume($queue_name, '', false, true, false, false, $callback);

Debugger::debug("WORKER PROCESS[" . getmypid() . "], started...");

while (count($channel->callbacks)) {
    $channel->wait();
}
$channel->close();
$connection->close();

Debugger::debug("WORKER PROCESS[" . getmypid() . "], stopped...");

