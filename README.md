# WorkerManager
Object oriented Job queue manager &amp; worker based on ReachPHP and RabbitMQ

#Installation with composer
```
   "require": {
        "the-great-wizard/worker-manager": "*",
    },
```
# Usage

1. create your worker by implementing the `IWork` Interface 
```
<?php
  
  namespace Workers;
  
  
  class WrReport implements IWork
  {
      public $msg;
  
      public function __construct($k)
      {
          $this->msg = "K = $k";
      }
  
      public function run()
      {
          file_put_contents("output_log.txt", date("Y-m-d H:i:s") . ": REPORT : {$this->msg}\n", FILE_APPEND);
      }
  } 
  
  ```
  
  2. 
  
  Create Manager instance, start a new process, and add your worker to the queue:
  
  ```
        $m = new Manager();
        $m->start();
        $w = new WrReport(1234);
        $m->add($w);
  ```