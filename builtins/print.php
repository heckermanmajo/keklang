<?php
Interpreter::$functions["print"] = function (
  array $args,
  array $env
): null {
  foreach ($args as $i => $a) {
    $args[$i] = Interpreter::eval($a, $env);
    echo str_replace(">n", "\n", $args[0]);
  };
  return null;
};