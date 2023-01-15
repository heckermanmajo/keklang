<?php


Interpreter::$functions["for"] = function (
  $args,
  $env
) {
  /**
   *  for i 0 10 1 # 4 values
   */
  $name = $args[0]->word; # todo: make it accept a string
  $start_value = Interpreter::eval($args[1], $env);
  assert(is_int($start_value));
  $end_value = Interpreter::eval($args[2], $env);
  assert(is_int($end_value));
  $step = Interpreter::eval($args[3], $env);
  assert(is_int($step));
  $do = $args[4];
  // add name to env
  #$env[$name] = $start_value;
  for ($i = $start_value; $i < $end_value; $i += $step) {
    $env[$name] = $i;
    Interpreter::eval($do, $env);
  }
  // remove name from env
  unset($env[$name]);
  return null;
};