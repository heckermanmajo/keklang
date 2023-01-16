<?php

Interpreter::$functions["expect"] = function (
  array $args,
  array &$env
){
  // pops the last tlog and compares it to the expected value
  foreach ($args as $i => $a) {
    $args[$i] = Interpreter::eval($a, $env);
  };
  assert(is_string($args[0]));
  assert(count($args) == 1);
  $expected = $args[0];
  // get the value from the start of the array
  $actual = array_pop(Interpreter::$tlogs);
  if ($expected != $actual) {
    print_r(Interpreter::$tlogs);
    print_r(Interpreter::$globals);
    print_r($env);
    throw new Exception("Expected $expected, got $actual");
  }
  return null;
};