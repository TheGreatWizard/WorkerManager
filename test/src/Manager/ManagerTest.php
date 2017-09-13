<?php
/**
 * Created by PhpStorm.
 * User: sguya
 * Date: 9/2/2017
 * Time: 12:39 PM
 */


use HighDal\HighDal;
use HighDal\Identifier;
use PHPUnit\Framework\TestCase;
use Workers\Manager;
use Test\TestObj;

class ManagerTest extends TestCase
{

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
        $this->fn = 'C:\wamp64\www\TTS\FOW\src\Background\output_log.txt';
        if (file_exists($this->fn)) {
            unlink($this->fn);
        }
    }

    public function testStop()
    {
        $m = new Manager();
        //$m->stopAll();
        $m->start();
        $m->start();
        unset($m);
    }

    public function testWorkerReport()
    {
        $m = new Manager();
        $m->stop();
        $m->start();
        $w1 = new \Workers\WrReport(11);
        $w2 = new \Workers\WrReport(102);
        $m->add($w1);
        $m->add($w2);
        $c = 0;
        while ((!file_exists($this->fn)) && ($c < 20)) {
            time_nanosleep(1, 2000);
            $c++;
        }
        echo $c . "\n";
        echo file_get_contents($this->fn);
    }


    public function testWorkerMySql1()
    {

        $m = new Manager();
        $m->start();
        $m->stop();
        $m->start();
        $w1 = new \Workers\WrTestMySql(123);
        $m->add($w1);


        $c = 0;
        while ((!file_exists($this->fn)) && ($c < 10)) {
            time_nanosleep(1, 2000);
            $c++;
        }
        echo $c . "\n";
        echo file_get_contents($this->fn);
        $m->stop();
    }

    public function testWorkerMySql2()
    {
        $t = new TestObj(100);
        $hd = new HighDal();
        $hd->save($t);
        $i = new Identifier($t);
        $t2 = $hd->load($i);
        assert($t->k == $t2->k);


        $m = new Manager();
        $m->start();
        $m->stop();
        $m->start();
        $w1 = new \Workers\WrTestMySql(123);
        $m->add($w1);


        $c = 0;
        while ((!file_exists($this->fn)) && ($c < 10)) {
            time_nanosleep(1, 2000);
            $c++;
        }
        echo $c . "\n";
        echo file_get_contents($this->fn);
        $m->stop();
    }


    public function testWorkerMySql3()
    {
        $t = new TestObj(100);
        $hd = new HighDal();
        $hd->save($t);
        $i = new Identifier($t);
        $t2 = $hd->load($i);
        $this->assertEquals($t->k, $t2->k);


        $m = new Manager();
        $m->start();
        $m->stop();
        $m->start();
        $w1 = new \Workers\WrTestMySql(123);
        $m->add($w1);


        $c = 0;
        while ((!file_exists($this->fn)) && ($c < 10)) {
            time_nanosleep(1, 2000);
            $c++;
        }
        echo $c . "\n";
        echo file_get_contents($this->fn);
        $m->stop();

        $t = new TestObj(101);
        $hd = new HighDal();
        $hd->save($t);
        $i = new Identifier($t);
        $t2 = $hd->load($i);
        $this->assertEquals($t->k, $t2->k);
    }


    public function testWorkerMySql4()
    {

        $m = new Manager();
        $m->start();
        $m->stop();
        $m->start();
        $w11 = new \Workers\WrTestMySql(123);
        $w12 = new \Workers\WrTestMySql(124);
        $m->add($w11);
        $m->add($w12);


        $c = 0;
        while ((!file_exists($this->fn)) && ($c < 10)) {
            sleep(1);
            $c++;
        }
        sleep(1);
        echo $c . "\n";
        echo file_get_contents($this->fn);
        $m->stop();


    }
}