<?php


Interpreter::$functions["new"] = function (
  array $args,
  array $env
) {
  // todo: make it accept a string
  $typename = $args[0]->word;
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