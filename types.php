<?php

/**
 *
 * Kek script types:
 *
 * Uppercase letter, the type function.
 * Lowercase letter, the instance constructor.
 *
 * If it needs type params, the arguments determine the type.
 * If no arguments are provided, the type needs to be specified.
 *
 */

Interpreter::$functions =array_merge(
  Interpreter::$functions,
  [
    // builtin types as functions
    "Str"            => function (
      array $args,
      array $env
    ): string {
      return "Str";
    },
  
    "Int"            => function (
      array $args,
      array $env
    ): string {
      return "Int";
    },
  
    "Bool" => function (
      array $args,
      array $env
    ): string {
      return "Bool";
    },
  
    "Float" => function (
      array $args,
      array $env
    ): string {
      return "Float";
    },
  
    // todo: change to list
    "Array" => function (
      array $args,
      array $env
    ): string {
      assert(count($args) == 1);
      $arg_one = Interpreter::eval($args[0], $env);
      assert(is_string($arg_one), print_r($args[0], true));
      // assert that the type starts with a capital letter
      assert($arg_one[0] == strtoupper($arg_one[0]));
      return "Array<{$arg_one}>";
    },
  
    "Void" => function (
      array $args,
      array $env
    ): string {
      return "Void";
    },
  
    "Option" => function (
      array $args,
      array $env
    ): string {
      // todo: needs one type param
      return "Void";
    },
  
    "Result" => function (
      array $args,
      array $env
    ): string {
      // todo: needs one type param
      // other would be error
      return "Void";
    },
  
    "Dict" => function (
      array $args,
      array $env
    ): string {
      // todo: needs two type params
      return "Void";
    },
  
    "Pair" => function (
      array $args,
      array $env
    ): string {
      // todo: needs two type params
      return "Void";
    },
    
    // pair function
    
    
    
  ]
);