<?php


Interpreter::$functions["newKekError"] = function (
  array $args,
  array $env
): KekError {
  assert(count($args) == 1);
  $message = Interpreter::eval($args[0], $env);
  assert(is_string($message));
  # todo: code line and code string
  throw new KekError($message, 0,"" );
};