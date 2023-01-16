<?php


Interpreter::$functions["not"] = function (
  array $args,
  array &$env
): bool {
  assert(count($args) == 1);
  return !Interpreter :: eval($args [0], $env);
};