<?php
// Check kukua

namespace Workers;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Startup\Debugger;

class Manager
{
    const DEFAULT_WORKER = __DIR__ . "/../Background/worker.php";
    const DEFAULT_BOOTSTRAP = __DIR__ . "/../../bootstrap.php";
    const PID = "PID.txt";

    public $channel;
    public $connection;
    public $queue_name;
    public $rabbit_host;
    public $rabbit_port;


    public function __destruct()
    {
        if (!is_null($this->channel)) {
            $this->channel->close();
        }
        if (!is_null($this->connection)) {
            $this->connection->close();
        }
    }

    public function __construct($workerPath = null, $bootstrapPath = null)
    {
        $this->queue_name = "tube";
        $this->rabbit_host = "localhost";
        $this->rabbit_port = 5672;

        $this->workerPath = isset($workerPath) ? $workerPath : self::DEFAULT_WORKER;
        $this->bootstrapPath = isset($bootstrapPath) ? $bootstrapPath : self::DEFAULT_BOOTSTRAP;
    }

    public function start()
    {
        Debugger::debug("------------ start ----------------");

        $onCreate = true;
        // is pid/txt exists
        if (file_exists(self::PID)) {
            Debugger::debug("pid exits");
            $pid = file_get_contents("PID.txt");
            Debugger::debug("pid=$pid");

            Debugger::debug("PHP_OS ==" . PHP_OS);
            if (PHP_OS == "WINNT") {
                exec('tasklist /NH /FI "PID eq ' . $pid . '"', $out);
                if (strpos($out[count($out) - 1], "php.exe") !== false) {
                    Debugger::debug("no need to create new process");
                    $onCreate = false;
                } else {
                    Debugger::debug("the process $pid not found:" . json_encode($out));

                }
            } elseif (PHP_OS == "Linux") {
                exec("ps $pid | grep $pid -o", $out);
                if (strlen($out[0]) > 0) {
                    Debugger::debug("no need to create new process");
                    $onCreate = false;
                }
            } else {
                throw new \Exception("background process not implemented for " . PHP_OS . " OS");
            }
        }

        // is task exits
        if ($onCreate) {
            if (PHP_OS == "WINNT") {
                //cmd /C > C:\wamp64\www\TTS\FOW\output.txt 2>&1
                exec('wmic process call create "php ' .
                    $this->workerPath . ' ' . $this->bootstrapPath . '" | find "ProcessId"', $out);
                $pid = intval(preg_replace("/[^0-9]/", "", $out[count($out) - 1]));
            } elseif (PHP_OS == "Linux") {
                $cmd = "php " . $this->workerPath . ' ' . $this->bootstrapPath;
                exec(sprintf("%s > /dev/null 2>&1 & echo $!", $cmd), $out);
                $pid = intval($out[0]);
            } else {
                throw new \Exception("background process not implemented for " . PHP_OS . " OS");
            }

            Debugger::debug("new process create with pid=$pid");
            file_put_contents(self::PID, strval($pid));
        }

        // RabbitMQ --------------------------------------------------------------------
        $this->connection = new AMQPStreamConnection($this->rabbit_host, $this->rabbit_port, 'guest', 'guest');
        $this->channel = $this->connection->channel();
        $this->channel->queue_declare($this->queue_name, false, false, false, false);
    }

    public function stop()
    {
        Debugger::debug("------------ stop ----------------");
        if (!file_exists(self::PID)) {
            Debugger::debug("no pid file");
            return;
        }
        $pid = file_get_contents(self::PID);
        if (PHP_OS == "WINNT") {
            exec('taskkill /pid ' . $pid);
        } elseif (PHP_OS == "Linux") {
            exec(sprintf("kill -9 %s", $pid));
        } else {
            throw new \Exception("background process not implemented for " . PHP_OS . " OS");
        }
        Debugger::debug("process killed pid=$pid");
        unlink("PID.txt");
    }

    public function stopAll()
    {
        if (PHP_OS == "WINNT") {
            exec('taskkill /FI "SESSION eq 1" /FI "IMAGENAME eq php.exe');
        } elseif (PHP_OS == "Linux") {
            throw new \Exception("stopAll() not implemented for " . PHP_OS . " OS");
        } else {
            throw new \Exception("background process not implemented for " . PHP_OS . " OS");
        }
    }

    public function add(Work $w)
    {
        $val_json = serialize($w);
        $msg = new AMQPMessage($val_json, array('delivery_mode' => 2));
        $this->channel->basic_publish($msg, '', $this->queue_name);
        Debugger::debug("MANAGER, sending to queue named :{$this->queue_name} the content:$val_json");
    }

}