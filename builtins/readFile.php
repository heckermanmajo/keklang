<?php


Interpreter::$functions["readFile"] = function (array $args, array &$env): string {
  assert(count($args) == 1);
  $filename = Interpreter::eval($args[0], $env);
  assert(is_string($filename));
  $file = fopen($filename, "r");
  $contents = fread($file, filesize($filename));
  fclose($file);
  return $contents;
};