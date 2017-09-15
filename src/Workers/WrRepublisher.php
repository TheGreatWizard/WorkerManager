<?php

namespace Workers;


class WrRepublisher implements IWork
{
    public $k;

    public function __construct($k)
    {
        $this->k = $k;
    }

    public function run()
    {
        if (file_exists("state.txt")) {
            $state = file_get_contents("state.txt");
        } else {
            $state = 0;
        }
        if (($state + 1) == $this->k) {
            file_put_contents("output_log.txt", date("Y-m-d H:i:s") . ": REPORT : {$this->k}\n", FILE_APPEND);

            file_put_contents("state.txt", $this->k);
        } else {
            file_put_contents("output_log.txt", date("Y-m-d H:i:s") . ": REPUBLISH : {$this->k}\n", FILE_APPEND);
            return "republish";
        }
    }
}