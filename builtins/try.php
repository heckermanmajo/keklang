<?php

Interpreter::$functions["try"] = function (
  array $args,
  array &$env
) {

  $code = $args[0];
  $catch = $args[1];
  
  try {
    return Interpreter::eval($code, $env);
  }catch (KekError $kekError) {
    $name_of_error = $catch->children[0]->word;
    $env[$name_of_error] = new Instance(
      Interpreter::$records["KekError"],
      ["message" => $kekError->message,
       "codeLine" => $kekError->codeLine,
       "code"     => $kekError->codeString,
       "trace" => $kekError->trace]
    );
     // this is too fra back ...
    $val = Interpreter::eval($catch->children[1], $env);
    Interpreter::$error_trace = [];
  }
  
};