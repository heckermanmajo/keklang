<?php

Interpreter::$functions["fn"] = function (
  array $args,
  array &$env
) {
  
  // if fn only has 2 children it is a simple function alias
  if (count($args) == 2) {
    $new_name = Interpreter::resolveToAName($args[0], $env);
    $old_name = Interpreter::resolveToAName($args[1], $env);
    Interpreter::assert(
      isset(Interpreter::$functions[$old_name]),
      "fn: function '$old_name' does not exist"
    );
    Interpreter::$functions[$new_name] = Interpreter::$functions[$old_name];
    return null;
  }
  
  $func_name = Interpreter::resolveToAName($args[0], $env);
  // check that function is alphanumeric -> bit is allowed to contain ::
  
  Interpreter::assert(
    !isset(Interpreter::$functions[$func_name]),
    "fn: function '$func_name' already exists"
  );
  
  $function_args = []; // args to check against
  $do_node = null;
  $return_type = null;
  $arguments = array_slice($args, 1); // remove the function name node
  
  // add this as first argument
  if(str_contains($func_name, "::")){
    $function_args["this"] = explode("::", $func_name);
  }
  
  foreach ($arguments as $key => $c) {
    if (count($c->children) == 0) {
      Interpreter::assert(
        $return_type == null, "fn: return type already defined");
      
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
    array &$env
  ) use
  (
    $do_node,
    $function_args,
    $return_type
  ) {
    $local_env = array();
    
    if(count($args) != count($function_args)) {
      throw new KekError("wrong number of arguments: " . count($args) . " != " . count($function_args));
    }
    
    $i = 0;
    foreach ($function_args as $key => $type_name) {
      $given_argument = $args[$i];
      // we don't want to execute the "this" argument, because we
      // executed it before we pass it to the function
      // this happens, so we can determine the function name+
      // look into the eval function for more details
      if ($type_name != "AstNode" and $key != "this") {
        $given_argument = Interpreter::eval($given_argument, $env);
        // todo: do type check here
      }
      $local_env[$key] = $given_argument;
      $i++;
    }
    
    $ret = null;
    foreach ($do_node->children as $c) {
      #if ($c)
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
      #default:
        #assert($ret instanceof $return_type);
    }
    #assert($ret instanceof $return_type);
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