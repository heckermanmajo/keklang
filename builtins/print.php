<?php
Interpreter::$functions["print"] = function (
  array $args,
  array &$env
){
  foreach ($args as $i => $a) {
    $args[$i] = Interpreter::eval($a, $env);
    echo str_replace(">n", "\n", $args[$i]);
  };
  return null;
};