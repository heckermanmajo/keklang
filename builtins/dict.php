<?php

Interpreter::$functions["dict"] = function (
  array $args,
  array &$env
): array {
  $array = [];
  # todo: expect first pair to be the type or "_" to auto-detect
  foreach ($args as $arg) {
    $array[] = Interpreter::eval($arg, $env);
  }
  return $array;
};