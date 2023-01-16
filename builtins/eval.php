<?php

Interpreter::$functions["eval"] = function(array $args, array &$env): mixed {
  return Interpreter::eval($args[0], $env);
};