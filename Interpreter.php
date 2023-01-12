<?php
include_once "keywords.php";
include "AstNode.php";
include "preProcessLines.php";
include "makeAstNodes.php";

// todo: introduce a comptime node, that is interpreted using Interpreter::eval
// todo: Create the parser-mode, that parses ast nodes into the interpreter
//       for manipulation
// todo: write a function that takes the collection of ast nodes and transforms
//       them into c code.


// todo: annotations
class Annotation{
  public string $name;
  public mixed $arguments = array();
}

 // todo: make function work ...

class Record{
  public string $name;
  /** @var array<string, string> */
  public array $fields;
}

class Instance {
  public Record $type;
  public array $fields;
}

class Interpreter {
  static array $records = [

  ];
  static array $globals = [
    "version" => "0.0.1",
  ];
  static array $functions = [

  ];
  
  static function eval(
    AstNode $node,
    array   &$env = null
  ): mixed {
    // eval one node
    if ($node->type == "string") {
      return $node->word;
    }
    if ($node->type == "int") {
      return (int)$node->word;
    }
    if ($node->type == "float") {
      return (float)$node->word;
    }
    if ($node->type == "boolean") {
      if($node->word == "false"){
        return false;
      }else{
        return true;
      }
    }
    
    if($node->word == "new"){
      $typename = $node->children[0]->word;
      $fields = [];
      foreach ($node->children as $key => $value){
        if ($key == 0) {
          continue;
        }
        assert($value->type == "named_param", $value);
        $value = static::eval($value->children[0]);
        $name = str_replace(":", "", $value);
        $fields[$name] = $value;
      }
      $instance = new Instance();
      $instance->type = static::$records[$typename];
      $instance->fields = $fields;
      return $instance;
    }
    
    if ($node->word == "fn") {
      // todo: arguments
      // todo: check return type -> at first at runtime
      // todo: use annotations
      $function_name = $node->children[0]->word;
      $function_args = []; // args to check against
      $do_node = null;
      $return_type = null;
      foreach ($node->children as $key => $c){
        if($key == 0){
          continue; // ignore function name
        }
        if (count($c->children) == 0) {
          // todo: assert that this is a type
          $return_type = $c;
          continue;
        }
        if ($c->word == "do"){
          $do_node = $c;
          assert($key == count($node->children) - 1);
          break;
        }
        $name = $c->word;
        $type = $c->children[0]->word;
        $function_args[$name] = $type;
      }
      $function = static function (
        array $args
      ) use
      (
        $do_node,
        $function_args,
        $return_type
      ) {
        $local_env = array();
        
        $i = 0;
        foreach ($function_args as $key => $type_name){
          $given_argument = $args[$i];
          // todo: do type check here
          $local_env[$key] = $given_argument;
          $i++;
        }
        
        $ret = null;
        foreach ($do_node->children as $c) {
          $ret = static::eval($c, $local_env);
        }
        // todo: check return type here
        return $ret;
      };
      static::$functions[$function_name] = $function;
      return null;
    }
    
    if ($node->word == "var") {
      $var_name =  $node->children[0]->word;
      $value = $node->children[1];
      $env[$var_name] = static::eval($value, $env);
      return null;
    }
    
    if ($node->word == "if") {
      // first get a condition
      // then cases or do blocks  -> case or do
      $condition = $node->children[0];
      $eval_cond = static::eval($condition, $env);
      if ($node->children[1]->word == "do") {
        // it is a if
        $then = $node->children[1];
        $else = $node->children[2] ?? null;
        #assert($eval_cond == true);
        assert($else->word == "do" or $else == null);
        if ($eval_cond) {
          return static::eval($then, $env);
        } else {
          if ($else != null) {
            return static::eval($else, $env);
          }
          return null;
        }
      } elseif ($node->children[1]->word == "case") {
        $value = static::eval($node->children[1]);
        $cases = [];
        $else = null;
        foreach ($node->children as $c) {
          if ($c->word == "case") {
            $cases[] = $c;
          }else if ($c->word == "else") {
            $else = $c;
          }else{
            throw new Exception("invalid if case: " . $c->word);
          }
        }
        if($else == null) {
          throw new Exception("no else in if case");
        }
        $found = false;
        foreach ($cases as $c){
          $cond = static::eval($c->children[0], $env);
          if($cond == $value){
            $found = true;
            //todo: this expects a do block, but we could remove the do block
            static::eval($c->children[1], $env);
            break;
          }
        }
        if (!$found){
          static::eval($else, $env);
        }
        return null;
        
      } else {
        throw new Exception("if or case expected: line " . $node->line_number);
      }
      
    }
    
    if ($node->word == "while") {
      // first get a condition
      // then cases or do blocks  -> case or do
      $condition = $node->children[0];
      $do = $node->children[1];
      assert($do->word == "do");
      while(static::eval($condition, $env)) {
        static::eval($do, $env);
      }
      return null;
    }
    
    if ($node->word == "type"){
      
      $typename = $node->children[0]->word;
      $fields = [];
      // todo: annotations / default values
      foreach ($node->children as $key => $value){
        if($key == 0)continue;
        $field_name = $value->word;
        $fields[$field_name] = $value->children[0]->word;
      }
      unset($i);
      $record = new Record();
      $record->name = $typename;
      $record->fields = $fields;
      static::$records[$typename] = $record;
      return null;
    }
    
    if ($node->word == "for") {
      /**
       *  for i 0 10 1 # 4 values
       */
      $name = $node->children[0]->word;
      $start_value = static::eval($node->children[1], $env);
      assert(is_int($start_value));
      $end_value = static::eval($node->children[2], $env);
      assert(is_int($end_value));
      $step = static::eval($node->children[3], $env);
      assert(is_int($step));
      $do = $node->children[4];
      // add name to env
      $env[$name] = $start_value;
      for($i = $start_value; $i < $end_value; $i += $step) {
        static ::eval($do, $env);
      }
      // remove name from env
      unset($env[$name]);
      return null;
    }
    
    if ($node->word == "each") {
      // each k i list/dict, @use ContextVar do
      $key_name = $node->children[0]->word;
      assert(count($node->children[0]->children) == 0);
      assert($node->children[0]->type == "name");
      $value_name = $node->children[1]->word;
      assert(count($node->children[1]->children) == 0);
      assert($node->children[1]->type == "name");
      $list_or_dict = static::eval($node->children[2], $env);
      assert(is_array($list_or_dict));
      $do = $node->children[3];
      assert($do->word == "do");
      foreach ($list_or_dict as $k => $v) {
        $env[$key_name] = $k;
        $env[$value_name] = $v;
        static::eval($do, $env);
      }
      unset($env[$key_name]);
      unset($env[$value_name]);
      return null;
    }
    
    // like each, but collects each instance in a list
    if ($node->word == "map"){
      // each k i list/dict, @use ContextVar do
      $key_name = $node->children[0]->word;
      assert(count($node->children[0]->children) == 0);
      assert($node->children[0]->type == "name");
      $value_name = $node->children[1]->word;
      assert(count($node->children[1]->children) == 0);
      assert($node->children[1]->type == "name");
      $list_or_dict = static::eval($node->children[2], $env);
      assert(is_array($list_or_dict));
      $do = $node->children[3];
      assert($do->word == "do");
      $ret = [];
      foreach ($list_or_dict as $k => $v) {
        $env[$key_name] = $k;
        $env[$value_name] = $v;
        // do returns the last value
        $_ret = static::eval($do, $env);
        $ret[] = $_ret;
      }
      unset($env[$key_name]);
      unset($env[$value_name]);
      return $ret;
    }
    
    if ($node->word == "do"){
      // do block
      $ret = null;
      foreach ($node->children as $c){
        $ret = static::eval($c, $env);
      }
      return $ret;
    }
    
    if (array_key_exists($node->word, static::$functions)) {
      $function = static::$functions[$node->word];
      $args = [];
      foreach ($node->children as $c) {
        $args[] = static::eval($c, $env);
      }
      return $function($args);
    }
    
    if (array_key_exists($node->word, $env)) {
      return $env[$node->word];
    }
    
    if (str_contains($node->word, ".")) {
      $parts = explode(".", $node->word);
      $obj = $env[$parts[0]];
      foreach ($parts as $key => $value) {
        if ($key == 0) continue;
        $obj = $obj->$value;
      }
      return $obj;
    }
    
    throw new Exception("Unknown node type: {$node->word} " . $node->type);
  }
  
}

Interpreter::$functions = [
  "print" => function (array $args): void {
    assert(is_string($args[0]));
    assert(count($args) == 1);
    echo str_replace( ">n", "\n", $args[0]);
  },
  "itos" => function (array $args): string {
    assert(count($args) == 1);
    return (string)$args[0];
  },
  "dumpTypes" => function (array $args): void {
    assert(count($args) == 0);
    var_dump(Interpreter::$records);
  },
];

$code = <<<CODE

print "hello world >n "
if false
  do > print version
  do > print "else block ... >n "

var myvar 10

print > itos myvar

type Person
  name Str
  age Int
  
dumpTypes

var p
  new Person
    name: "John"
    age: 20

fn foo > a Str > b Str > Null
  do
    print a
    print b

foo "kek" "lol"

CODE;
assert(isset($KEYWORDS));
$pplines = preProcessLines($code);
$nodes = makeAstNodes($pplines, $KEYWORDS);
foreach($nodes as $node) {
  Interpreter::eval($node, Interpreter::$globals);
}
