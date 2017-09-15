<?php

use PHPUnit\Framework\TestCase;
use Workers\Manager;
use Test\TestObj;

class ManagerTest extends TestCase
{

    /**
     * @var Manager
     */
    public $m;

    public function catch_fatal_error()
    {
        // Getting Last Error
        $last_error = error_get_last();
        if (!is_null($last_error)) {
            file_put_contents("ManagerTest_error_log.txt", date("Y-m-d H:i:s") . ":ManagerTest, ERROR : " . json_encode($last_error) . "\n", FILE_APPEND);
        }
    }

    public function setUp()
    {
        register_shutdown_function([$this, 'catch_fatal_error']);
        $this->path = __DIR__ . '/../../../src/Background';
        $this->fn = $this->path . '/output_log.txt';
        if (file_exists($this->fn)) {
            unlink($this->fn);
        }


    }

    public function tearDown()
    {
        $m = new Manager();
        $m->stopAll();
    }

    public function testWorkerReport()
    {
        $m = new Manager();
        $m->start();
        $w1 = new \Workers\WrReport(210);
        $w2 = new \Workers\WrReport(220);
        $m->add($w1);
        $m->add($w2);
        $c = 0;
        while ((!file_exists($this->fn)) && ($c < 20)) {
            time_nanosleep(1, 2000);
            $c++;
        }
        $this->assertTrue($c < 20, "took more then 20 seconds, to perform work");
        $content = file_get_contents($this->fn);
        $this->assertTrue(strpos($content, "K = 220") !== false);

        $m->stop();
    }

    public function testTwoQueues()
    {
        $m1 = new Manager(["queueName" => "one"]);
        $m1->start();

        $m2 = new Manager(["queueName" => "two"]);
        $m2->start();
        for ($k = 0; $k < 10; $k++) {
            $w1 = new \Workers\WrReport(300 + $k);
            $w2 = new \Workers\WrReport(400 + $k);
            $m1->add($w1);
            $m2->add($w2);
        }


        sleep(1);
        $c = 0;
        while ((!file_exists($this->fn)) && ($c < 10)) {
            time_nanosleep(1, 2000);
            $c++;
        }
        $this->assertTrue($c < 10, "took more then 10 seconds, to perform work");
        $content = file_get_contents($this->fn);

        $this->assertEquals(20, substr_count($content, "\n"));
        $m1->stop();
        $m2->stop();
    }

    public function testTwoRunners()
    {
        $m1 = new Manager();
        $m1->start("kuzia");
        $m1->start("gugu");
        for ($k = 0; $k < 10; $k++) {
            $w1 = new \Workers\WrReport(300 + $k);
            $w2 = new \Workers\WrReport(400 + $k);
            $m1->add($w1);
            $m1->add($w2);
        }


        sleep(1);
        $c = 0;
        while ((!file_exists($this->fn)) && ($c < 10)) {
            time_nanosleep(1, 2000);
            $c++;
        }
        $this->assertTrue($c < 10, "took more then 10 seconds, to perform work");
        $content = file_get_contents($this->fn);

        $this->assertEquals(20, substr_count($content, "\n"));
        $m1->stop("kuzia");
        $m1->stop("gugu");
    }

    public function testManyRunners()
    {
        $m1 = new Manager();
        $m1->start("kuzia");
        $m1->start("gugu");
        $m1->start("gaga");

        $this->assertEquals(["kuzia", "gugu", "gaga"], $m1->getRunners(),
            "canonicalize = true", $delta = 0.0, $maxDepth = 10, $canonicalize = true);
        sleep(1);
        $m1->stopAll();
        $this->assertEquals([], $m1->getRunners());
    }


    public function testWorkerList()
    {
        $m1 = new Manager();
        $m1->start("kuzia");
        $m1->start("gugu");
        for ($k = 0; $k < 10; $k++) {
            $w1 = new \Workers\WrReport(300 + $k);
            $w2 = new \Workers\WrReport(400 + $k);
            $wList = new \Workers\WorkerList();
            $wList->addWorker($w1);
            $wList->addWorker($w2);
            $m1->add($wList);
        }


        sleep(1);
        $c = 0;
        while ((!file_exists($this->fn)) && ($c < 10)) {
            time_nanosleep(1, 2000);
            $c++;
        }
        $this->assertTrue($c < 10, "took more then 10 seconds, to perform work");
        $content = file_get_contents($this->fn);

        $this->assertEquals(20, substr_count($content, "\n"));
        $m1->stop("kuzia");
        $m1->stop("gugu");
    }

    public function testRepublish()
    {
        if (file_exists($this->path . '/state.txt')) {
            unlink($this->path . '/state.txt');
        }
        $m = new Manager();
        $m->start();
        $arr = range(1, 10);
        shuffle($arr);
        foreach ($arr as $k) {
            $w = new Workers\WrRepublisher($k);
            $m->add($w);
        }

        sleep(5);
        $c = 0;
        while ((!file_exists($this->fn)) && ($c < 10)) {
            time_nanosleep(1, 2000);
            $c++;
        }
        $this->assertTrue($c < 10, "took more then 10 seconds, to perform work");
        $content = file_get_contents($this->path . '/state.txt');

        $this->assertEquals(10, $content);
        $m->stop();

    }
}