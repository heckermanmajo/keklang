<?php


Interpreter::$functions["len"] = function (array $args, array &$env){
  print_r($args);
  $list_or_dict = Interpreter::eval($args[0], $env);
  if (is_array($list_or_dict)){
    return count($list_or_dict);
  }else{
    return count($list_or_dict->fields);
  }
};