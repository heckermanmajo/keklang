<?php

Interpreter::$functions["add"] = function (
  array $args,
  array &$env
): int {
  foreach ($args as $i => $a) {
    $args[$i] = Interpreter::eval($a, $env);
    Interpreter::assert(is_int($args[$i]), "add: expected Int, got " . gettype($args[$i]));
  };
  return array_sum($args);
};