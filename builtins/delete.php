<?php


Interpreter::$functions["delete"] = function (array $args, array &$env): null {
  assert(count($args) == 1);
  # todo: make it accept code as well
  $name = Interpreter::eval($args[0], $env);
  assert(is_string($name));
  if (array_key_exists($name, $env)) {
    unset($env[$name]);
  } else {
    if (array_key_exists($name, Interpreter::$functions)) {
      unset(Interpreter::$functions[$name]);
    } else {
      throw new KekError("Variable or function '$name' does not exist");
    }
  }
  return null;
};