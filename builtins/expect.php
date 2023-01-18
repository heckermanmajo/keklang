<?php

Interpreter::$functions["expect"] = function (
  array $args,
  array &$env
){
  Interpreter::assert(count($args) == 1,
  "expect: expected 1 argument, got " . count($args)
  );
  $value = Interpreter::eval($args[0], $env);
  Interpreter::assert(is_string($value), "expect: expected String, got " . gettype($value));
  $expected = $value;
  // get the value from the start of the array
  $actual = array_pop(Interpreter::$tlogs);
  if ($expected != $actual) {
    Interpreter::err("expect: expected '$expected', got '$actual'");
  }
  return null;
};