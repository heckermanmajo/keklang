<?php

Interpreter::$functions["call"] = function (
  array $args,
  array &$env
): mixed { // type any, needs if case to determine what to di with it
  assert(count($args) == 1);
  $func_or_func_name = Interpreter::eval($args[0], $env);
  assert(
    is_string($func_or_func_name)
    or is_callable($func_or_func_name)
  );
  if (is_string($func_or_func_name)) {
    $func_name = $func_or_func_name;
    if (isset($env[$func_name])) {
      $func = $env[$func_name];
    } else {
      assert(isset(Interpreter::$functions[$func_name]));
      $func = Interpreter::$functions[$func_name];
    }
  } else {
    $func = $func_or_func_name;
  }
  if (is_callable($func)) {
    if(isset($args[1] )) {
      if (count($args[1]->children) != 0) {
        return $func($args[1]->children, $env);
      }
      # expect an list node
    }
    return $func([], $env);
  } else {
    throw new Exception("not callable");
  }
};