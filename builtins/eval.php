<?php

Interpreter::$functions["eval"] = function(array $args, array &$env): mixed {
  Interpreter::assert(count($args) == 1, "eval: expected 1 argument, got " . count($args));
  return Interpreter::eval($args[0], $env);
};