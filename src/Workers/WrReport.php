<?php
/**
 * Created by PhpStorm.
 * User: sguya
 * Date: 8/12/2017
 * Time: 6:49 PM
 */

namespace Workers;


class WrReport extends Work
{
    public $msg;
    public $k;




    public function __construct($k)
    {
        $this->k = $k;
        $this->msg = "K = $k";
    }

    public function run()
    {
        file_put_contents("output_log.txt", date("Y-m-d H:i:s") . ": REPORT : {$this->msg}\n", FILE_APPEND);
    }
}