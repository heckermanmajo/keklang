<?php

Interpreter::$functions["expect"] = function (
  array $args,
  array $env
): null {
  // pops the last tlog and compares it to the expected value
  foreach ($args as $i => $a) {
    $args[$i] = Interpreter::eval($a, $env);
  };
  assert(is_string($args[0]));
  assert(count($args) == 1);
  $expected = $args[0];
  $actual = array_pop(Interpreter::$tlogs);
  if ($expected != $actual) {
    throw new Exception("Expected $expected, got $actual");
  }
  return null;
};