<?php

Interpreter::$functions["lt"] = function (
  array $args,
  array $env
): bool {
  foreach ($args as $i => $a) {
    $args[$i] = Interpreter::eval($a, $env);
  };
  assert(count($args) == 2);
  return $args[0] < $args[1];
};