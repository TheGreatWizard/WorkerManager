<?php
/**
 * Created by PhpStorm.
 * User: sguya
 * Date: 7/15/2017
 * Time: 4:32 PM
 */

namespace Startup;

//use Startup\Debugger;

class Locker
{
    private $root = __DIR__ . '/';

    public function lock(string $key)
    {
        $path = $this->root . $key;
        Debugger::debug("LOCK " . $path);
        return !file_exists($path) && @mkdir($path);
    }

    public function unlock(string $key)
    {
        $path = $this->root . $key;
        Debugger::debug("UNLOCK " . $path);
        rmdir($path);
    }

    public function waitUntilLocked(string $key)
    {
        $path = $this->root . $key;
        Debugger::debug("START WAITING " . $key);
        $start = time();
        while (!@mkdir($path)) {
            usleep(rand(5000, 20000));
            if ((time() - $start) > 60 * 2) {
                Debugger::debug("Lock timeout 2min !!");
                throw new \Exception("Lock '{$key}' timeout 2min");
            }
        }
        Debugger::debug("STOP WAITING, LOCK " . $key);
    }

}