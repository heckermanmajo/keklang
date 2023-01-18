<?php


Interpreter::$functions["format"] = function (
  array $args,
  array &$env
) {
  $format_string = Interpreter::eval($args[0], $env);
  Interpreter::assert(
    is_string($format_string),
    "format: expected String, got " . gettype($format_string));
  $format_args = [];
  foreach ($args as $key => $value) {
    if ($key == 0) continue;
    $format_args[] = Interpreter::eval($value, $env);
  }
  $code = sprintf($format_string, ...$format_args);
  $code = str_replace("\\\"", "\"", $code);
  return $code;
};