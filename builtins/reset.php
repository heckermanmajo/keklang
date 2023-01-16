<?php

Interpreter::$functions["reset"] = function (array $args, array &$env){
  Interpreter::init();
};