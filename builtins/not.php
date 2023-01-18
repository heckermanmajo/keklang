<?php


Interpreter::$functions["not"] = function (
  array $args,
  array &$env
): bool {
  Interpreter::assert(
    count($args) == 1,
    "not: expected 1 argument, got " . count($args)
  );
  return !Interpreter:: eval($args [0], $env);
};