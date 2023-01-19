<?php

namespace kek;

/**
 * All types the compiler needs.
 */
class InternalNode { }

class Method{

}

class Type {
  public string $name;
  /** @var array<string, Type> */
  public array $fields;
  /** @var array<string, Value|string|int|float|null> */
  public array $default_arguments;
  public array $functions;
}

class Value {
  public function __construct(
    public Type  $type,
    /** @var array<int, Value|string|int|float|null>|array<string, Value|string|int|float|null>*/
    public array $data  // list/dict/instance
  ) {
  }
}

class Scope{
  public function __construct(
    public Scope $parent,
    public array $vars,
    public array $functions,
  ) {}
}

class Interpreter{
  public Scope $currentEnv;
  
  /** @var Scope[] */
  public array $envs;
  
  /** @var array<string,Type> */
  public array $types;
  
  /** @var array<string> */
  public array $error_trace;
  
  /**
   * Keep track of each/for/while loops for break/continue.
   * @var array<InternalNode>
   */
  public array $current_loop;
}