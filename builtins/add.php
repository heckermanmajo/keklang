<?php

Interpreter::$functions["add"] = function (
  array $args,
  array $env
): int {
  foreach ($args as $i => $a) {
    $args[$i] = Interpreter::eval($a, $env);
  };
  assert(count($args) == 2);
  return $args[0] + $args[1];
};