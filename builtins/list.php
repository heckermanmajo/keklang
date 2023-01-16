<?php


Interpreter::$functions["list"] = function (
  array $args,
  array &$env
) {
  
  $values = [];
  foreach ($args as $i => $a) {
    $values[] = Interpreter:: eval($a, $env);
  }
  
  return $values;
  
};