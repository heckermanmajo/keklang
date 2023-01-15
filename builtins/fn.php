<?php

Interpreter::$functions["fn"] = function (
  array $args,
  array $env
) {
  // todo: argument
  // todo: check return type -> at first at runtime
  // assert that the function name is not already used
  if (is_string($args[0])) {
    $func_name = $args[0];
  } elseif ($args[0] instanceof AstNode) {
    if (count($args[0]->children) == 0) {
      if ($args[0]->type == "name") {
        if (isset($env[$args[0]->word])) {
          throw new Exception("varname is taken: " . $args[0]->word);
        }
      }
      $func_name = $args[0]->word;
    } else {
      $func_name = Interpreter::eval($args[0], $env);
      assert(is_string($func_name));
      if (isset($env[$func_name]) or isset(Interpreter::$functions[$func_name])) {
        throw new Exception("func name is taken: " . $args[0]->word);
      }
    }
  } else {
    // error
    throw new Exception("varname is not a string");
  }
  
  // check that function is alphanumeric -> bit is allowd to contain ::
  
  $function_args = []; // args to check against
  $do_node = null;
  $return_type = null;
  $arguments = array_slice($args, 1); // remove the function name node
  foreach ($arguments as $key => $c) {
    if (count($c->children) == 0) {
      // todo: assert that this is a type
      $return_type = $c;
      continue;
    }
    if ($c->word == "do") {
      $do_node = $c;
      assert($key == count($arguments) - 1);
      break;
    }
    # todo: do we want to allow default values?
    # todo: do we want to allow dynamic naming of arguments?
    $name = $c->word;
    $type = $c->children[0]->word;
    $function_args[$name] = $type;
  }
  
  $function = static function (
    array $args,
    array $env
  ) use
  (
    $do_node,
    $function_args,
    $return_type
  ) {
    $local_env = array();
    
    $i = 0;
    foreach ($function_args as $key => $type_name) {
      $given_argument = $args[$i];
      if ($type_name != "AstNode") {
        $given_argument = Interpreter::eval($given_argument, $env);
        // todo: do type check here
      }
      $local_env[$key] = $given_argument;
      $i++;
    }
    
    $ret = null;
    foreach ($do_node->children as $c) {
      if ($c)
        $ret = Interpreter::eval($c, $local_env);
    }
    // todo: make type checking better ...
    switch (get_class($return_type)) {
      case "int":
        assert(is_int($ret));
        break;
      case "float":
        assert(is_float($ret));
        break;
      case "string":
        assert(is_string($ret));
        break;
      case "bool":
        assert(is_bool($ret));
        break;
      case "null":
        assert(is_null($ret));
        break;
      default:
        assert($ret instanceof $return_type);
    }
    assert($ret instanceof $return_type);
    return $ret;
  };
  // if the name is "_", this is an anonymous function
  // just return it
  if ($func_name == "_") {
    return $function;
  }
  Interpreter::$functions[$func_name] = $function;
  return null;
};