<?php


Interpreter::$functions["typename"] = function (
  array $args,
  array &$env
): string {
  Interpreter::assert(
    count($args) == 1,
    "typename: expected 1 argument, got " . count($args)
  );
  $arg = Interpreter::eval($args[0], $env);
  return Interpreter::getTypeStringOfValue($arg);
};