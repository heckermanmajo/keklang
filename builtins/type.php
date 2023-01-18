<?php

Interpreter::$functions["type"] = function (
  array $args,
  array &$env
) {
  $typename = Interpreter::resolveToAName($args[0], $env);
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
  Interpreter::assert($typename[0] == strtoupper($typename[0]),
    "type: type name must start with a capital letter: " . $typename
  );
  // assert that the type name is not already used as a type
  Interpreter::assert(!isset(Interpreter::$records[$typename]),
    "type: type name already used: " . $typename
  );
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