<?php


Interpreter::$functions["typeExists"] = function(array $args, array &$env): bool {
  assert(count($args) == 1);
  $type = Interpreter :: eval($args [0], $env);
  assert(is_string($type));
  return array_key_exists($type, Interpreter::$records);
};