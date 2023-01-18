<?php


Interpreter::$functions["itos"] = function (
  array $args,
  array &$env
): string {
  Interpreter::assert(
    count($args) == 1,
    "itos: expected 1 argument, got " . count($args)
  );
  $arg = Interpreter::eval($args[0], $env);
  
  Interpreter::assert(
    is_int($arg),
    "itos: expected Int, got " . gettype($arg)
  );
  
  return strval($arg);
};