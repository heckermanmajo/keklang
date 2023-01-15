<?php

Interpreter::$functions["try"] = function (
  array $args,
  array $env
) {

  $code = $args[0];
  $catch = $args[1];
  
  try {
    return Interpreter::eval($code, $env);
  }catch (KekError $kekError) {
    $closure = Interpreter::eval($catch, $env);
    return $closure($kekError);
  }
  
};