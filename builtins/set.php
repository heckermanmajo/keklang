<?php


Interpreter::$functions["set"] = function (
  array $args,
  array &$env
) {
  Interpreter::assert(
    count($args) == 2,
    "set: expected 2 arguments, got " . count($args)
  );
  
  // set the last param to false since we don't want to create a new variable
  $var_name = Interpreter::resolveToAName(
    $args[0],
    $env,
    false
  );
  
  $new_value = Interpreter::eval($args[1], $env);
  $instance_to_set = null;
  
  if(str_contains($var_name, ".")){
    $instance_to_set = Interpreter::resolveToInstance(
      $args[0]->word,
      $env
    );
    $parts = explode(".", $var_name);
    $var_name = array_pop($parts);
  }
  

  // todo: check that var exists before setting it
  if ($instance_to_set != null) {
    Interpreter::assert(
      Interpreter::getTypeStringOfValue($instance_to_set->fields[$var_name]) == Interpreter::getTypeStringOfValue($new_value),
      "set: expected " . Interpreter::getTypeStringOfValue($instance_to_set->fields[$var_name]) . ", got " . Interpreter::getTypeStringOfValue($new_value)
    );
    $instance_to_set->fields[$var_name] = $new_value;
  } else {
    if(array_key_exists($var_name, $env)){
      $env[$var_name] = $new_value;
    }else if (array_key_exists($var_name, Interpreter::$globals)) {
      Interpreter::$globals[$var_name] = $new_value;
    } else {
      Interpreter::err("set: variable $var_name does not exist");
    }
  }
  
  return null;
};
