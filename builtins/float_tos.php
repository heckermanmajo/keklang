<?php


Interpreter::$functions["Float::tos"] = function (
  array $args,
  array &$env
){
  Interpreter::assert(
    count($args) == 1,
    "Float::tos: expected 1 argument, got " . count($args)
  );
  // args0 -> "this", so already evaluated
  $value = $args[0];
  Interpreter::assert(
    is_float($value),
    "Float::tos: expected Float, got " . gettype($value)
  );
  return strval($args[0]);
};