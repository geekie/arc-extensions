<?php

abstract class BaseGeekieTestEngine extends ArcanistUnitTestEngine {
  
  abstract public function run();
  
  final protected function resolveFuture($future) {
    $console = PhutilConsole::getConsole();
    
    $future->setRaiseExceptionOnStart(false)->start();
    
    do {
      list($stdout, $stderr) = $future->read();
      $console->writeOut('%s', $stdout);
      $console->writeErr('%s', $stderr);
    } while (!$future->isReady());
    
    return $future->resolve();
  }
  
}
