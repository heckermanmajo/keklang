<?php


Interpreter::$functions["itos"] = function (
  array $args,
  array &$env
): string {
  assert(count($args) == 1);
  $arg = Interpreter::eval($args[0], $env);
  assert(is_int($arg));
  return (string)$arg;
};