<?php


Interpreter::$functions["is"] = function (
  array $args,
  array &$env
): bool {
  Interpreter::assert(count($args) == 2,
    "is: expected 2 arguments, got " . count($args)
  );
  $a = Interpreter::eval ($args[0], $env);
  $b = Interpreter::eval ($args [1], $env);
  return $a === $b;
};