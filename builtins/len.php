<?php


Interpreter::$functions["len"] = function (
  array $args,
  array &$env
) {
  Interpreter::assert(
    count($args) == 1,
    "len: expected 1 argument, got " . count($args)
  );
  $list_or_dict = Interpreter::eval($args[0], $env);
  if (is_array($list_or_dict)) {
    return count($list_or_dict);
  } else {
    Interpreter::assert(
      $list_or_dict instanceof Instance,
      "len: expected List or Dict, got " . gettype($list_or_dict)
    );
    return count($list_or_dict->fields);
  }
};