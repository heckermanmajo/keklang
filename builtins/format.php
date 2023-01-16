<?php


Interpreter::$functions["format"] = function (array $args, array &$env){
  $format_string = Interpreter::eval($args[0], $env);
  $format_args = [];
  foreach ($args as $key => $value) {
    if ($key == 0) continue;
    $format_args[] = Interpreter::eval($value, $env);
  }
  return sprintf($format_string, ...$format_args);
};