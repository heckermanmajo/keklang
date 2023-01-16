<?php


Interpreter::$functions["join"] = function (
  array $args,
  array &$env
) {
  $sep = Interpreter::eval($args[0], $env);
  $list = Interpreter :: eval($args[1], $env);
  return implode($sep, $list);
};