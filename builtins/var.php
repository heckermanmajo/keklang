<?php

Interpreter::$functions["var"] = function (
  array $args,
  array &$env
) {
  assert(count($args) == 2);
  if (is_string($args[0])) {
    $var_name = $args[0];
  } elseif ($args[0] instanceof AstNode) {
    if (count($args[0]->children) == 0) {
      if ($args[0]->type == "name") {
        if (isset($env[$args[0]->word])) {
          throw new Exception("varname is taken: " . $args[0]->word);
        }
      }
      $var_name = $args[0]->word;
    } else {
      $var_name = Interpreter::eval($args[0], $env);
      assert(is_string($var_name));
      if (isset($env[$var_name])) {
        throw new Exception("varname is taken: " . $args[0]->word);
      }
    }
  } else {
    // error
    throw new Exception("varname is not a string");
  }
  $value = Interpreter::eval($args[1], $env);
  print_r($var_name);
  echo "\n";
  $env[$var_name] = $value;
  return null;
};