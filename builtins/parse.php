<?php


Interpreter::$functions["parse"] = function (
  array $args,
  array &$env
): mixed {
  $code = Interpreter::eval($args[0], $env);
  $local = Interpreter::eval($args[1], $env);
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
  return null;
};