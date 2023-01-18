<?php


Interpreter::$functions["Int::tos"] = function (
  array $args,
  array &$env
){
  Interpreter::assert(
    count($args) == 1,
    "Int::tos: expected 1 argument, got " . count($args)
  );
  
  Interpreter::assert(
    is_int($args[0]),
    "Int::tos: expected Int, got " . gettype($args[0])
  );
  
  // args0 -> "this", so already evaluated
  return strval($args[0]);
};