<?php

Interpreter::$functions["include"] = function(
  array $args,
  array &$env
){
  // expect 2 arguments
  Interpreter::assert(count($args) == 2,
    "include: expected 2 arguments, got " . count($args)
  );
  
  $path = Interpreter::eval($args[0], $env);
  Interpreter::assert(is_string($path),
    "include: expected String, got " . gettype($path)
  );
  
  $code = file_get_contents($path);
  
  if(count($args) == 2){
    $local = Interpreter::eval($args[1], $env);
  }else{
    $local = false;
  }
  
  $pplines = preProcessLines($code);
  $nodes = makeAstNodes($pplines);
  foreach ($nodes as $node) {
    # todo: how to do trace here?
    if (!$local) {
      // set to Interpreter::$globals, so parse does not put all the parsed
      // names etc. into the function scope where parse is called in
      Interpreter::parse($node, Interpreter::$globals);
    } else {
      Interpreter::parse($node, $env);
    }
    
  }
  
};