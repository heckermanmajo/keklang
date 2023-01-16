<?php

Interpreter::$functions["add"] = function (
  array $args,
  array &$env
): int {
  foreach ($args as $i => $a) {
    $args[$i] = Interpreter::eval($a, $env);
  };
  return array_sum($args);
};