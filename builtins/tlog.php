<?php

Interpreter::$functions["tlog"] = function (
  array $args,
  array &$env
){
  Interpreter::assert(
    count($args) == 1,
    "tlog: expected 1 argument, got " . count($args)
  );
  $value = Interpreter::eval($args[0], $env);
  Interpreter::assert(
    is_string($value),
    "tlog: expected String, got " . gettype($value)
  );
  Interpreter::$tlogs[] = $value;
  return null;
};