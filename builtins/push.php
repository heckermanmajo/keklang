<?php

Interpreter::$functions["push"] = function (array $args, array &$env){
  # $list = Interpreter::eval($args[0], $env);
  #$list = $env[$args[0]->word];
  $item = Interpreter::eval($args[1], $env);
  $env[$args[0]->word][] = $item;
};