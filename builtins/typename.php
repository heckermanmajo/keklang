<?php


Interpreter::$functions["typename"] = function (
  array $args,
  array &$env
): string {
  assert(count($args) == 1);
  $arg = Interpreter::eval ($args[0], $env);
if (is_int($arg)) {
    return "Int";
  } else if (is_float($arg)) {
    return "Float";
  } else if (is_string($arg)) {
    return "Str";
  } else if (is_bool($arg)) {
    return "Bool";
  } else if (is_array($arg)) {
    return "List";
  } else if (is_object($arg)) {
    return get_class($arg);
  } else if (is_null($arg)) {
    return "Null";
  } else {
    throw new KekError("typename: unknown type");
  }
};