<?php

Interpreter::$functions["tlog"] = function (
  array $args,
  array &$env
){
  foreach ($args as $i => $a) {
    $args[$i] = Interpreter::eval($a, $env);
  };
  assert(is_string($args[0]), print_r($args[0], true));
  assert(count($args) == 1);
  Interpreter::$tlogs[] = $args[0];
  return null;
};