<?php


Interpreter::$functions["each"] = function (
  array $args,
  array &$env
) {
  // each k i list/dict do
  $key_name = Interpreter::resolveToAName($args[0], $env);
  $value_name = Interpreter::resolveToAName($args[1], $env);
  $list_or_dict = Interpreter::eval($args[2], $env);
  Interpreter::assert(
    is_array($list_or_dict),
    "each: expected List or Dict, got " . gettype($list_or_dict)
  );

  $do = $args[3];
  Interpreter::assert($do->word == "do",
    "each: expected 'do' as 4th argument, got " . $do->word
  );
  foreach ($list_or_dict as $k => $v) {
    $env[$key_name] = $k;
    $env[$value_name] = $v;
    Interpreter::eval($do, $env);
  }
  unset($env[$key_name]);
  unset($env[$value_name]);
  return null;
};