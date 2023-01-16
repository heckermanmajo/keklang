<?php

Interpreter::$functions["charList"] = function (
  array $args,
  array &$env
): array{
  $str = Interpreter::eval($args[0], $env);
  if(is_string($str) and strlen($str) > 0){
    $ret = str_split($str);
    if ($ret){
      return $ret;
    }
  }
  print_r($args[0]);
  throw new KekError("charList: expected string, got " . $str);
};