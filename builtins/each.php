<?php


Interpreter::$functions["each"] = function (
  array $args,
  array &$env
) {
  // each k i list/dict do
  $key_name = $args[0]->word;  # todo: make it accept a string
  assert(count($args[0]->children) == 0);
  assert($args[0]->type == "name");
  $value_name = $args[1]->word;
  assert(count($args[1]->children) == 0);
  assert($args[1]->type == "name");
  $list_or_dict = Interpreter::eval($args[2], $env);
  assert(
    is_array($list_or_dict),
    print_r($list_or_dict, true));
  $do = $args[3];
  assert($do->word == "do");
  foreach ($list_or_dict as $k => $v) {
    $env[$key_name] = $k;
    $env[$value_name] = $v;
    Interpreter::eval($do, $env);
  }
  unset($env[$key_name]);
  unset($env[$value_name]);
  return null;
};