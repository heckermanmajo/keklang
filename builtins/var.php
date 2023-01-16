<?php

Interpreter::$functions["var"] = function (
  array $args,
  array &$env
) {
  #print "create var: " . $args[0]->word;
  assert(count($args) == 2);
  $name = $args[0]->word;
  $value = Interpreter::eval($args[1], $env);
  if (!array_key_exists($name, $env)) {
    $env[$name] = $value;
    return null;
  } else {
    print_r($env);
    throw new KekError("Variable $name does exist");
  }
};