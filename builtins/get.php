<?php

Interpreter::$functions["get"] = function (
  array $args,
  array &$env
) {
  $instance = Interpreter::eval($args[0], $env);
  $index = Interpreter::eval($args[1], $env);
  if (is_array($instance)) {
    return $instance[$index];
  }else{
    Interpreter::assert(
      !($instance instanceof Instance),
      "get: expected List or Dict, got " . gettype($instance)
    );
    return $instance->fields[$index];
  }
};