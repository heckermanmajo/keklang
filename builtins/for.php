<?php


Interpreter::$functions["for"] = function (
  $args,
  &$env
) {
  /**
   *  for i 0 10 1 # 4 values
   */
  $name = Interpreter::resolveToAName($args[0], $env);
  $start_value = Interpreter::eval($args[1], $env);
  Interpreter::assert(
    is_int($start_value),
    "for: expected Int, got " . gettype($start_value));
  
  $end_value = Interpreter::eval($args[2], $env);
  Interpreter::assert(is_int($end_value),
    "for: expected Int, got " . gettype($end_value));
  $step = Interpreter::eval($args[3], $env);
  Interpreter::assert(
    is_int($step),
    "for: expected Int, got " . gettype($step)
  );
  
  // check that the name is not already in the env
  Interpreter::assert(
    !array_key_exists($name, $env),
    "for: counter-name '$name' already exists in the environment"
  );
  
  $do = $args[4];
  // add name to env
  # $env[$name] = $start_value;
  if ($step > 0) {
    for ($i = $start_value; $i < $end_value; $i += $step) {
      $env[$name] = $i;
      Interpreter::eval($do, $env);
    }
  } else {
    for ($i = $start_value; $i > $end_value; $i += $step) {
      $env[$name] = $i;
      Interpreter::eval($do, $env);
    }
  }
  // remove name from env
  unset($env[$name]);
  return null;
};