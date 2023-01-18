<?php

Interpreter::$functions["var"] = function (
  array $args,
  array &$env
) {
  Interpreter::assert(
    count($args) == 2,
    "var: expected 2 argument, got " . count($args)
  );
  $name = Interpreter::resolveToAName($args[0], $env);
  $value = Interpreter::eval($args[1], $env);
  if (!array_key_exists($name, $env)) {
    $env[$name] = $value;
    return null;
  } else {
    Interpreter::err("var: variable already exists: " . $name);
  }
};