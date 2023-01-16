<?php


Interpreter::$functions["writeFile"] = function (
  array $args,
  array &$env
) {
  assert(count($args) == 2);
  $path = Interpreter::eval($args[0], $env);
  $contents = Interpreter::eval($args[1], $env);
  assert(is_string($path));
  assert(is_string($contents));
  $file = fopen($path, "w");
  fwrite($file, $contents);
  fclose($file);
  return null;
};
