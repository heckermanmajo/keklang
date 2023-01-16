<?php


Interpreter::$functions["new"] = function (
  array $args,
  array &$env
) {
  // todo: make it accept a string
  if (count($args[0]->children) != 0){
    $typename = Interpreter::eval($args[0], $env);
  }else{
    if (array_key_exists($args[0]->word, $env)){
      $typename = $env[$args[0]->word];
    }else {
      $typename = $args[0]->word;
    }
  }
  $fields = [];
  foreach ($args as $key => $name_arg) {
    if ($key == 0) {
      continue;
    }
    assert($name_arg->type == "named_param", $name_arg);
    $value = Interpreter::eval($name_arg->children[0], $env);
    $name = str_replace(":", "", $name_arg->word);
    $fields[$name] = $value;
  }
  $type = Interpreter::$records[$typename];
  $instance = new Instance(
    $type,
    $fields
  );
  return $instance;
  
};