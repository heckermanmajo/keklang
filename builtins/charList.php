<?php

Interpreter::$functions["charList"] = function (
  array $args,
  array &$env
): array{
  Interpreter::assert(count($args) == 1, "charList: expected 1 argument, got " . count($args));
  $str = Interpreter::eval($args[0], $env);
  if(is_string($str) and strlen($str) > 0){
    $ret = str_split($str);
    if ($ret){
      array_pop(Interpreter::$error_trace);
      return $ret;
    }else{
      Interpreter::assert(false, "charList: failed to split string");
    }
  }
  Interpreter::err("charList: expected String, got " . gettype($str));
};