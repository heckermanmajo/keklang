<?php


Interpreter::$functions["is"] = function (
  array $args,
  array &$env
): bool {
  assert(count($args) == 2);
  $a = Interpreter::eval ($args[0], $env);
  $b = Interpreter::eval ($args [1], $env);
  return $a === $b;
};