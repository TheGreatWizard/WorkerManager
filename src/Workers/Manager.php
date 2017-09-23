<?php
// Check kukua

namespace Workers;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Startup\Debugger;

class Manager
{
    const DEFAULT_RUNNER = __DIR__ . "/../Background/runner.php";
    const DEFAULT_BOOTSTRAP = __DIR__ . "/../../bootstrap.php";
    const DEFAULT_QUEUE_NAME = "tube";
    const DEFAULT_RABBIT_HOST = "localhost";
    const DEFAULT_RABBIT_PORT = 5672;
    public $pid;

    public $channel;
    public $connection;
    public $queueName;
    public $rabbitHost;
    public $rabbitPort;
    public $bootstrapPath;


    public function __destruct()
    {
        if (!is_null($this->channel)) {
            $this->channel->close();
        }
        if (!is_null($this->connection)) {
            $this->connection->close();
        }
    }

    //public function __construct($workerPath = null, $bootstrapPath = null)

    public function __construct($args = [])
    {
        $this->workerPath = $args['workerPath'] ?? self::DEFAULT_RUNNER;
        $this->bootstrapPath = $args['bootstrapPath'] ??  self::DEFAULT_BOOTSTRAP;
        $this->queueName = $args['queueName'] ?? self::DEFAULT_QUEUE_NAME;
        $this->rabbitHost = $args['rabbitHost'] ?? self::DEFAULT_RABBIT_HOST;
        $this->rabbitPort = $args['rabbitPort'] ?? self::DEFAULT_RABBIT_PORT;
    }

    public function pid($name = "")
    {
        return "PID_{$this->queueName}_{$name}.txt";
    }

    private function isProcessExists($pid)
    {
        if (PHP_OS == "WINNT") {
            exec('tasklist /NH /FI "PID eq ' . $pid . '"', $out);
            if (strpos($out[count($out) - 1], "php.exe") !== false) {
                return true;
            } else {
                return false;
            }
        } elseif (PHP_OS == "Linux") {
            exec("ps $pid | grep $pid -o", $out);
            if (strlen($out[0]) > 0) {
                return true;
            } else {
                return false;
            }
        } else {
            throw new \Exception("background process not implemented for " . PHP_OS . " OS");
        }
    }


    public function start($runnerName = "")
    {
        Debugger::debug("------------ start ----------------");

        $onCreate = true;
        // is pid/txt exists
        if (file_exists($this->pid($runnerName))) {
            Debugger::debug("pid exits");
            $pid = file_get_contents($this->pid($runnerName));
            Debugger::debug("pid=$pid");
            $onCreate = !$this->isProcessExists($pid);
        }

        // is task exits
        if ($onCreate) {
            $argv = implode(" ", [$this->workerPath, $this->bootstrapPath, $this->queueName, $this->rabbitHost, $this->rabbitPort]);
            if (PHP_OS == "WINNT") {
                exec('wmic process call create "php ' . $argv . '" | find "ProcessId"', $out);
                $pid = intval(preg_replace("/[^0-9]/", "", $out[count($out) - 1]));
            } elseif (PHP_OS == "Linux") {
                $cmd = "php " . $argv;
                exec(sprintf("%s > /dev/null 2>&1 & echo $!", $cmd), $out);
                $pid = intval($out[0]);
            } else {
                throw new \Exception("background process not implemented for " . PHP_OS . " OS");
            }

            Debugger::debug("new process create with pid=$pid");
            file_put_contents($this->pid($runnerName), strval($pid));
        }

        // RabbitMQ --------------------------------------------------------------------
        $this->connection = new AMQPStreamConnection($this->rabbitHost, $this->rabbitPort, 'guest', 'guest');
        $this->channel = $this->connection->channel();
        $this->channel->queue_declare($this->queueName, false, false, false, false);

    }

    public function stop($runnerName = "")
    {
        Debugger::debug("------------ stop ----------------");
        if (!file_exists($this->pid($runnerName))) {
            Debugger::debug("no pid file");
            return;
        }
        $pid = file_get_contents($this->pid($runnerName));
        if (PHP_OS == "WINNT") {
            exec('taskkill /pid ' . $pid);
        } elseif (PHP_OS == "Linux") {
            exec(sprintf("kill -9 %s", $pid));
        } else {
            throw new \Exception("background process not implemented for " . PHP_OS . " OS");
        }
        Debugger::debug("process killed pid=$pid");
        unlink($this->pid($runnerName));

    }

    public function stopAll()
    {
        if (PHP_OS == "WINNT") {
            exec('taskkill /FI "SESSION eq 1" /FI "IMAGENAME eq php.exe');
        } elseif (PHP_OS == "Linux") {
            exec("ps -ef | grep \"runner.php\" | grep -v \"grep\"", $out);

            foreach ($out as $str) {
                $parts = explode(" ", $str);
                foreach ($parts as $part) {
                    if (preg_match("/^[1-9][0-9]*$/", $part)) {
                        $pid = strval($part);
                        exec(sprintf("kill -9 %s", $pid));
                        break;
                    }
                }
            }

        } else {
            throw new \Exception("background process not implemented for " . PHP_OS . " OS");
        }

        array_map('unlink', glob($this->pid("*")));
    }

    public function getRunners()
    {
        $runners = [];
        $pidFiles = glob($this->pid("*"));
        foreach ($pidFiles as $pidFile) {
            $pid = file_get_contents($pidFile);
            if ($this->isProcessExists($pid)) {
                $runner = str_replace(".txt", "", explode("_", $pidFile)[2]);
                $runners[] = $runner;
            } else {
                unlink($pidFile);
            }

        }
        return $runners;
    }


    public function add(IWork $w)
    {
        $val_json = serialize($w);
        $msg = new AMQPMessage($val_json, array('delivery_mode' => 2));
        $this->channel->basic_publish($msg, '', $this->queueName);
        Debugger::debug("MANAGER, sending to queue :{$this->queueName} the work:$val_json");
    }

    public function deleteQueue()
    {
        if (!is_null($this->channel)) {
            $this->channel->queue_delete($this->queueName);
        }
    }
}