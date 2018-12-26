<?php

use Illuminate\Container\Container;

if (!session_id()) {
    session_start();
}

require __DIR__.'/Psr4AutoloaderClass.php';

$psr4 = new Psr4AutoloaderClass();

$psr4->addNameSpace('App', __DIR__.'/../app');
$psr4->addNameSpace('Core', __DIR__.'/../core');
$psr4->register();


$application = new \Core\ApplicationWeb([]);
$application->run();