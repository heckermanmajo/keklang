<?php

Interpreter::$functions["if"] = function (array $args, array &$env){
  // first get a condition
  // then cases or do blocks  -> case or do
  $condition = $args[0];
  $eval_cond = Interpreter::eval($condition, $env);
  if ($args[1]->word == "then") {
    // it is an if
    $then = $args[1];
    $else = $args[2] ?? null;
    
    Interpreter::assert(
      is_bool($eval_cond),
      "if: expected true, got " . $eval_cond
    );
    
    if ($eval_cond) {
      $ret = null;
      foreach ($then->children as $c){
        $ret = Interpreter::eval($c, $env);
      }
      return $ret;
    } else {
      if ($else != null) {
        $ret = null;
        foreach ($else->children as $c){
          $ret = Interpreter::eval($c, $env);
        }
        return $ret;
      }
      return null;
    }
  } elseif ($args[1]->word == "case") {
    $value = Interpreter::eval($args[0], $env);
    $cases = [];
    $else = null;
    # remove the first child -> it is the value
    $_cases = array_slice($args, 1);
    foreach ($_cases as $c) {
      if ($c->word == "case") {
        $cases[] = $c;
      } else if ($c->word == "else") {
        $else = $c;
      } else {
        throw new Exception("invalid if case: " . $c->word);
      }
    }
    if ($else == null) {
      Interpreter::err("if case: expected else");
    }
    
    foreach ($cases as $c) {
      $cond = Interpreter::eval($c->children[0], $env);
      if ($cond == $value) {
        //todo: this expects a do block, but we could remove the do block
        return Interpreter::eval($c->children[1], $env);
      }
    }
    // do th do block after else, no condition
    return Interpreter::eval($else->children[0], $env);
    
  } else {
    Interpreter::err("if: expected 'then' or 'case', got " . $args[1]->word);
  }
};