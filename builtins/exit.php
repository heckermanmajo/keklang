<?php

Interpreter::$functions["exit"] = function (array $args, array &$env){
  exit;
};