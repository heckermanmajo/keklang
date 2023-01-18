<?php


Interpreter::$functions["typeExists"] = function (
  array $args,
  array &$env
): bool {
  Interpreter::assert(
    count($args) == 1,
    "typeExists: expected 1 argument, got " . count($args)
  );
  $type = Interpreter:: eval($args [0], $env);
  Interpreter::assert (
    is_string($type),
    "typeExists: expected String, got " . gettype($type)
  );
  
  // check no spaces
  Interpreter::assert (
    !str_contains($type, " "),
    "typeExists: expected String with no spaces, got " . $type
  );
  
  return array_key_exists($type, Interpreter::$records);
};