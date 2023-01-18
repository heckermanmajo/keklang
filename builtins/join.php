<?php

Interpreter::$functions["join"] = function (
  array $args,
  array &$env
) {
  Interpreter::assert(
    count($args) == 2,
    "join: expected 2 arguments, got " . count($args)
  );
  $sep = Interpreter::eval($args[0], $env);
  Interpreter::assert(
    is_string($sep),
    "join: expected String, got " . gettype($sep)
  );
  $list = Interpreter :: eval($args[1], $env);
  Interpreter::assert(
    is_array($list),
    "join: expected List, got " . gettype($list)
  );
  return implode($sep, $list);
};