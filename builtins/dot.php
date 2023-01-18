<?php


Interpreter::$functions["."] = function (
  array $args,
  array &$env
) {
  Interpreter::assert(
    count($args) == 2,
    "dot: expected 2 arguments, got " . count($args)
  );
  
  $instance = Interpreter::resolveToInstance(
    $args[0],
    $env
  );
  
  $field_name = Interpreter::resolveToAName(
    $args[1],
    $env
  );
  
  Interpreter::assert(
    array_key_exists($field_name, $instance->fields),
    "dot: no such field: " . $field_name
  );
  
  return $instance->fields[$field_name];
};