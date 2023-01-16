<?php


Interpreter::$functions["sub"] = function (
  array $args,
  array &$env
) {
  $a = Interpreter::eval($args[0], $env);
  $b = Interpreter::eval($args[1], $env);
  return $a - $b;
};