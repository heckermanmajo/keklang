<?php


Interpreter::$functions["set"] = function (
  array $args,
  array $env
): null {
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
    }
  } else {
    // error
    throw new Exception("varname is not a string");
  }
  
  if (str_contains($var_name, ".")) {
    $parts = explode(".", $var_name);
    $obj = $env[$parts[0]];
    $last_part = $parts[count($parts) - 1];
    $other_parts = array_slice($parts, 1, count($parts) - 2);
    foreach ($other_parts as $key => $value) {
      if ($key == 0) continue;
      $obj = $obj->fields[$value];
    }
    $instance = $obj;
    $new_value = Interpreter::eval($args[1]->children[1], $env);
    assert($new_value instanceof $instance->fields[$last_part]);
    $instance->fields[$last_part] = $new_value;
  } else {
    $new_value = Interpreter::eval($args[1]->children[1], $env);
    assert($new_value instanceof $env[$var_name],
           "new_value: " . json_encode($new_value) . ", env[$var_name]: " . json_encode($env[$var_name])
    );
    // this means we are not allowed to change globals, except if the global is an instance
    $env[$var_name] = $new_value;
  }
  return null;
};
