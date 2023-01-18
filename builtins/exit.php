<?php

Interpreter::$functions["exit"] = function (
  array $args,
  array &$env
): never {
  // zero arguments or one argument
  Interpreter::assert(count($args) <= 1, "exit: expected 0 or 1 argument, got " . count($args));
  if (count($args) == 0) {
    exit(0);
  } else {
    // expect int
    $code = Interpreter:: eval($args[0], $env);
    Interpreter::assert(is_int($code), "exit: expected Int, got " . gettype($code));
    exit($code);
  }
};