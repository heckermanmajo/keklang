<?php


Interpreter::$functions["sys"] = function (
  array $args,
  array &$env
) {
  Interpreter::assert(
    count($args) == 1,
    "sys: expected 1 argument, got " . count($args)
  );
  $command = Interpreter::eval($args[0], $env);
  Interpreter::assert(is_string($command),
    "sys: expected String, got " . gettype($command));
  $output = shell_exec($command);
  return $output;
};