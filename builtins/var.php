<?php

Interpreter::$functions["var"] = function (
  array $args,
  array &$env
) {
  
  Interpreter::assert(
    count($args) == 2 or count($args) == 3,
    "var: expected 2 or 3 arguments, got " . count($args)
  );
  
  $name = Interpreter::resolveToAName($args[0], $env);
  
  if (array_key_exists($name, $env)) {
    Interpreter::err("var: variable already exists: " . $name);
  }
  
  if (count($args) == 2) {
    $value = Interpreter::eval($args[1], $env);
    $env[$name] = $value;
    $env[$name . "_type"] = Interpreter::getTypeStringOfValue($value);
    return null;
  }
  
  if (count($args) == 3) {
    $type = Interpreter::eval($args[1], $env);
    Interpreter::checkGivenStringIsType($type);
    $value = Interpreter::eval($args[2], $env);
    Interpreter::assert(
      Interpreter::getTypeStringOfValue($value) == $type,
      "var: expected " . $type . ", got " . Interpreter::getTypeStringOfValue($value)
    );
    $env[$name] = $value;
    $env[$name . "_type"] = $type;
    return null;
  }
};