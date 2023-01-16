<?php

// returns a dict of all the functions in the file
Interpreter::$functions["env"] = function(array $args, array &$env): array {
  return $env;
};