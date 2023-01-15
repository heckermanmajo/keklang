<?php

Interpreter::$functions["if"] = function (array $args, array $env){
  // first get a condition
  // then cases or do blocks  -> case or do
  $condition = $args[0];
  $eval_cond = Interpreter::eval($condition, $env);
  if ($args[1]->word == "do") {
    // it is a if
    $then = $args[1];
    $else = $args[2] ?? null;
    #assert($eval_cond == true);
    assert($else->word == "do" or $else == null);
    if ($eval_cond) {
      return Interpreter::eval($then, $env);
    } else {
      if ($else != null) {
        return Interpreter::eval($else, $env);
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
      throw new Exception("no else in if case");
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
    throw new Exception("if or case expected: line ");
  }
};