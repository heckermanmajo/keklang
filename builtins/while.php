<?php

Interpreter::$functions["while"] = function (
  array $args,
  array $env
) {
  // first get a condition
  // then cases or do blocks  -> case or do
  $condition = $args[0];
  $do = $args[1];
  assert($do->word == "do");
  while (Interpreter::eval($condition, $env)) {
    Interpreter::eval($do, $env);
  }
  return null;
};