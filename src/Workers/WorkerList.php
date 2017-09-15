<?php

namespace Workers;


use Startup\Debugger;

class WorkerList implements IWork
{

    public $workers = [];

    public function addWorker(IWork $worker)
    {
        $this->workers[] = $worker;
    }

    function run()
    {
        foreach ($this->workers as $worker) {
            $worker->run();
        }
    }
}