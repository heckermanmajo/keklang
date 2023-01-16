<?php

Interpreter::$functions["include"] = function(
  array $args,
  array &$env
){
  $path = Interpreter::eval($args[0], $env);
  $code = file_get_contents($path);
  if(count($args) == 2){
    $local = Interpreter::eval($args[1], $env);
  }else{
    $local = false;
  }
  $pplines = preProcessLines($code);
  $nodes = makeAstNodes($pplines);
  foreach ($nodes as $node) {
    #print_r($node);
    if (!$local) {
      // set to Interpreter::$globals, so parse does not put all the parsed
      // names etc. into the function scope where parse is called in
      Interpreter::parse($node, Interpreter::$globals);
    } else {
      Interpreter::parse($node, $env);
    }
  }
  
};