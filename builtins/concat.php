<?php

Interpreter::$functions[] = function (
  array $args,
  array $env
): string {
  assert(count($args) == 2);
  $one = Interpreter::eval($args[0], $env);
  $two = Interpreter::eval($args[1], $env);
  assert(is_string($one));
  assert(is_string($two));
  return $one . $two;
};