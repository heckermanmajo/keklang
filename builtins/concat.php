<?php

Interpreter::$functions["concat"] = function (
  array $args,
  array &$env
): string {
  $ret = "";
  foreach ($args as $arg) {
    $ret .= Interpreter::eval($arg, $env);
  }
  return $ret;
};