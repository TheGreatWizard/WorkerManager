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