<?php

Interpreter::$functions["call"] = function (
  array $args,
  array &$env
): mixed { // type any, needs if case to determine what to di with it
  Interpreter::assert(
    count($args) <= 2,
    "call: expected 1 or 2 arguments, got " . count($args)
  );
  $func_or_func_name = Interpreter::eval($args[0], $env);
  Interpreter::assert(
    is_string($func_or_func_name)
    or is_callable($func_or_func_name)
  , "call: first argument must be a string or a function");
  if (is_string($func_or_func_name)) {
    $func_name = $func_or_func_name;
    if (isset($env[$func_name])) {
      $func = $env[$func_name];
    } else {
      Interpreter::assert(
        isset(Interpreter::$functions[$func_name]),
        "call: function '$func_name' not found"
      );
      Interpreter::$error_trace[] = "In 'call': calling function '$func_name'";
      $func = Interpreter::$functions[$func_name];
    }
  } else {
    Interpreter::$error_trace[] = "In 'call': calling anonymous function";
    $func = $func_or_func_name;
  }
  if (is_callable($func)) {
    if(isset($args[1] )) {
      if (count($args[1]->children) != 0) {
        $ret =  $func($args[1]->children, $env);
        array_pop(Interpreter::$error_trace);
        return $ret;
      }
      # expect an list node
    }
    $ret = $func([], $env);
    array_pop(Interpreter::$error_trace);
    return $ret;
  } else {
    Interpreter::assert(
      is_array($func),
      "call: first argument must be a string or a function"
    );
  }
};