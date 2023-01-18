<?php

Interpreter::$functions["concat"] = function (
  array $args,
  array &$env
): string {
  $ret = "";
  foreach ($args as $arg) {
    $val = Interpreter::eval($arg, $env);
    Interpreter::assert(
      is_string($val),
      "concat: expected String, got " . gettype($val)
    );
    $ret .= $val;
  }
  return $ret;
};