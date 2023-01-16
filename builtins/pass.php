<?php


Interpreter::$functions["stub"]= function(
  array $args,
  array &$env
){
  # pass
  return null;
};