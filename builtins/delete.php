<?php


Interpreter::$functions["delete"] = function (array $args, array &$env){
  Interpreter::assert(count($args) == 1,
                      "delete: expected 1 argument, got " . count($args));
  $name = Interpreter::resolveToAName($args[0], $env);
  if (array_key_exists($name, $env)) {
    unset($env[$name]);
  } else {
    if (array_key_exists($name, Interpreter::$functions)) {
      unset(Interpreter::$functions[$name]);
    } else {
      Interpreter::err("delete: no such variable or function: " . $name);
    }
  }
  return null;
};