<?php


Interpreter::$functions["writeFile"] = function (
  array $args,
  array &$env
) {
  Interpreter::assert(
    count($args) == 2,
    "writeFile: expected 2 arguments, got " . count($args)
  );
  $path = Interpreter::eval($args[0], $env);
  $contents = Interpreter::eval($args[1], $env);
  Interpreter::assert(
    is_string($path),
    "writeFile: expected String, got " . gettype($path)
  );
  Interpreter::assert(
    is_string($contents),
    "writeFile: expected String, got " . gettype($contents)
  );
  $file = fopen($path, "w");
  fwrite($file, $contents);
  fclose($file);
  return null;
};
