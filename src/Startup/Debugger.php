<?php
/**
 * Created by PhpStorm.
 * User: sguya
 * Date: 7/11/2017
 * Time: 9:50 PM
 */

namespace Startup;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;


 class Debugger
{
    static $log = null;
    static function debug($my_var){
        if (!DEBUG) return;
        if (self::$log===null) {
            self::$log = new Logger('name');
            self::$log->pushHandler(new StreamHandler(__DIR__ . '/../../log.txt', Logger::DEBUG));
        }
        self::$log->log(100,var_export($my_var, true));
    }
}