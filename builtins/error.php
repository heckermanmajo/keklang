<?php


Interpreter::$functions["error"] = function (
  array $args,
  array &$env
): KekError {
  Interpreter::assert(count($args) == 1,
    "error: expected 1 argument, got " . count($args)
  );
  $message = Interpreter::eval($args[0], $env);
  Interpreter::assert(is_string($message),
    "error: expected String, got " . gettype($message)
  );
  Interpreter::err($message);
};