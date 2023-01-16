<?php


Interpreter::$functions["set"] = function (
  array $args,
  array &$env
): null {
  assert(count($args) == 2);
  if (is_string($args[0])) {
    $var_name = $args[0];
  } elseif ($args[0] instanceof AstNode) {
    if (count($args[0]->children) == 0) {
      $var_name = $args[0]->word;
    } else {
      $var_name = Interpreter::eval($args[0], $env);
      assert(is_string($var_name));
    }
  } else {
    // error
    throw new Exception("varname is not a string");
  }
  $instance_to_set = null;
  if(str_contains($var_name, ".")){
    
    $var_name_parts = explode(".", $var_name);
    foreach ($var_name_parts as $key => $part) {
      if ($key == count($var_name_parts) - 1) {
        break;
      }
      if($instance_to_set != null){
        $instance_to_set = $instance_to_set->fields[$part];
      }else {
        $instance_to_set = $env[$part];
      }
    }
    $var_name = $var_name_parts[count($var_name_parts) - 1];
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
    $new_value = Interpreter::eval($args[1], $env);
    assert($new_value instanceof $instance->fields[$last_part]);
    $instance->fields[$last_part] = $new_value;
  } else {
    $new_value = Interpreter::eval($args[1], $env);
    assert($new_value instanceof $env[$var_name],
           "new_value: " . json_encode($new_value) . ", env[$var_name]: " . json_encode($env[$var_name])
    );
    
    // todo: check that var exists before setting it
    if($instance_to_set != null){
      $instance_to_set->fields[$var_name] = $new_value;
    } else {
      // this means we are not allowed to change globals, except if the global is an instance
      $env[$var_name] = $new_value;
    }
  }
  return null;
};
