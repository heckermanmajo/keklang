<?php

Interpreter::$functions["type"] = function (
  array $args,
  array &$env
) {
  # todo: make typename also work with string and multiple nodes
  if (count($args[0]->children) != 0){
    $typename = Interpreter::eval($args[0]->children[0], $env);
  }else{
    if (array_key_exists($args[0]->word, $env)){
      $typename = $env[$args[0]->word];
    }else {
      $typename = $args[0]->word;
    }
  }
  $fields = [];
  foreach ($args as $key => $value) {
    if ($key == 0) continue;
    $field_name = $value->word;
    // static eval the type -> should return a string
    $fields[$field_name] = Interpreter::eval($value->children[0], $env);
  }
  unset($i);
  $record = new Record();
  $record->name = $typename;
  $record->fields = $fields;
  // assert that the type name starts with a capital letter
  assert($typename[0] == strtoupper($typename[0]));
  // assert that the type name is not already used as a type
  assert(!isset(Interpreter::$records[$typename]));
  Interpreter::$records[$typename] = $record;
  // now add the type name as function, so we can get the string
  // if we want to type hint it or pass it to new
  Interpreter::$functions[$typename] = static function () use (
    $typename
  ) {
    return $typename;
  };
  return null;
};