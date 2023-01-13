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
  static array $records = [];
  static array $globals = [
    "version" => "0.0.1",
  ];
  static array $functions = [];
  
  static array $non_comptime_nodes = [];
  
  static array $tlogs = []; // used for testing
  
  /**
   * Function used to evaluate script nodes.
   * @param AstNode $node
   * @param array|null $env
   * @return mixed
   * @throws Exception
   */
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
      foreach ($node->children as $key => $name_arg){
        if ($key == 0) {
          continue;
        }
        assert($name_arg->type == "named_param", $name_arg);
        $value = static::eval($name_arg->children[0], $env);
        $name = str_replace(":", "", $name_arg->word);
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
        array $args,
        array $env
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
          if($type_name != "AstNode"){
            $given_argument = static::eval($given_argument, $env);
            // todo: do type check here
          }
          $local_env[$key] = $given_argument;
          $i++;
        }
        
        $ret = null;
        foreach ($do_node->children as $c) {
          if($c)
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
        $value = static::eval($node->children[0], $env);
        $cases = [];
        $else = null;
        # remove the first child -> it is the value
        $_cases = array_slice($node->children, 1);
        foreach ($_cases as $c) {
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
        foreach ($cases as $c){
          $cond = static::eval($c->children[0], $env);
          if($cond == $value){
            //todo: this expects a do block, but we could remove the do block
            return static::eval($c->children[1], $env);
          }
        }
        // do th do block after else, no condition
        return static::eval($else->children[0], $env);
        
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
      print_r($node);
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
      #$env[$name] = $start_value;
      for($i = $start_value; $i < $end_value; $i += $step) {
        $env[$name] = $i;
        static::eval($do, $env);
      }
      // remove name from env
      unset($env[$name]);
      return null;
    }
    
    if ($node->word == "set"){
      $name = $node->children[0]->word;
      if (str_contains($name, ".")) {
        $parts = explode(".", $name);
        $obj = $env[$parts[0]];
        $last_part = $parts[count($parts) - 1];
        $other_parts = array_slice($parts, 1, count($parts) - 2);
        foreach ($other_parts as $key => $value) {
          if ($key == 0) continue;
          $obj = $obj->fields[$value];
        }
        $instance = $obj;
        $instance->fields[$last_part] = static::eval($node->children[1], $env);
      }else{
        // this measn we are not allowed to change globals, except if the global s a instance
        $env[$name] = static::eval ($node->children[1], $env);
      }
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
        $args[] = $c; // the function decides if it evals the given nodes
      }
      return $function($args, $env);
    }
    
    if (array_key_exists($node->word, $env)) {
      return $env[$node->word];
    }
    
    if (str_contains($node->word, ".")) {
      $parts = explode(".", $node->word);
      $obj = $env[$parts[0]];
      foreach ($parts as $key => $value) {
        if ($key == 0) continue;
        $obj = $obj->fields[$value];
      }
      return $obj;
    }
    
    throw new Exception("Unknown node type: {$node->word} " . $node->type);
  }
  
  /**
   * @throws Exception
   */
  static function parse(AstNode $node): ?AstNode {
    // parse node
    if ($node->word == "comptime"){
      $ret = null;
      foreach ($node->children as $c)
        $ret = static::eval($c, static::$globals);
      if ($ret == null) return null;
      assert($ret instanceof AstNode);
      $node = $ret;
    }
    if(str_starts_with($node->word, "!") and $node->type == "name"){
      $node = static::eval($node, static::$globals);
      if ($node == null) return null;
    }
    if($node->indentation == 0){
      static::$non_comptime_nodes[] = $node;
      return null;
    }
    return $node;
  }
  
}

Interpreter::$functions = [
  "print" => function (array $args, array $env): void {
    foreach ($args as $i => $a){$args[$i] = Interpreter::eval($a, $env);};
    assert(is_string($args[0]));
    assert(count($args) == 1, print_r($args, true));
    echo str_replace( ">n", "\n", $args[0]);
  },
  "itos" => function (array $args, array $env): string {
    foreach ($args as $i => $a){$args[$i] = Interpreter::eval($a, $env);};
    assert(count($args) == 1);
    return (string)$args[0];
  },
  "dumpTypes" => function (array $args, array $env): void {
    foreach ($args as $i => $a){$args[$i] = Interpreter::eval($a, $env);};
    assert(count($args) == 0);
    var_dump(Interpreter::$records);
  },
  "dump" => function (array $args, array $env): void {
    foreach ($args as $i => $a){$args[$i] = Interpreter::eval($a, $env);};
    assert(count($args) == 1);
    var_dump($args[0]);
  },
  "tlog" => function (array $args, array $env): void {
    foreach ($args as $i => $a){$args[$i] = Interpreter::eval($a, $env);};
    assert(is_string($args[0]), print_r($args[0], true));
    assert(count($args) == 1);
    Interpreter::$tlogs[] = $args[0];
  },
  "expect" => function (array $args, array $env): void {
    // pops the last tlog and compares it to the expected value
    foreach ($args as $i => $a){$args[$i] = Interpreter::eval($a, $env);};
    assert(is_string($args[0]));
    assert(count($args) == 1);
    $expected = $args[0];
    $actual = array_pop(Interpreter::$tlogs);
    if ($expected != $actual) {
      throw new Exception("Expected $expected, got $actual");
    }
  },
  "lt" => function (array $args, array $env): bool {
    foreach ($args as $i => $a){$args[$i] = Interpreter::eval($a, $env);};
    assert(count($args) == 2);
    return $args[0] < $args[1];
  },
  "add" => function (array $args, array $env): int {
    foreach ($args as $i => $a){$args[$i] = Interpreter::eval($a, $env);};
    assert(count($args) == 2);
    return $args[0] + $args[1];
  },
  "dumpThisNode" => function (array $args, array $env): void {
    // do not eval the node...
    assert(count($args) == 1);
    var_dump($args[0]);
  },
  "dumpNode" => function (array $args, array $env): void {
    $node = Interpreter::eval($args[0], $env);
    assert(count($args) == 1);
    assert($node instanceof AstNode);
    var_dump($node);
  },
  "eval_node" => function (array $args, array $env): mixed {
    $node = Interpreter::eval($args[0], $env);
    assert(count($args) == 1);
    assert($node instanceof AstNode);
    return $node;
  },
  "newNode" => function (array $args, array $env): AstNode {
    $word = Interpreter::eval($args[0], $env);
    $type = Interpreter::eval($args[1], $env);
    $line_number = Interpreter::eval($args[2], $env);
    $indentation = Interpreter::eval($args[3], $env);
    $children = Interpreter::eval($args[4], $env);
    $doc_comment = Interpreter::eval($args[5], $env);
    $node = new AstNode();
    $node->word = $word;
    $node->type = $type;
    $node->line_number = $line_number;
    $node->indentation = $indentation;
    $node->children = $children;
    $node->doc_comment = $doc_comment;
    return $node;
  },
  "array" => function (array $args, array $env): array {
    $array = [];
    foreach ( $args as $arg){
      $array[] = Interpreter::eval($arg, $env);
    }
    return $array;
  },
];

/** @language=*.kek*/
$code = <<<CODE

#print "hello world >n "
#if false
#  do > print version
#  do > print "else block ... >n "
comptime
  var myvar 10
  
  #print > itos myvar
  
  type Person
    name Str
    age Int
    
  #dumpTypes
  
  var p
    new Person
      name: "John"
      age: 20
  
  #dump p
  
  tlog p.name
  expect "John"
  tlog > itos p.age
  expect "20"
  
  set p.name "Jane"
  tlog p.name
  expect "Jane"
  
  #dump p
  
  set p 123 # currently no type checks ...
  
  #dump p
  
  fn foo > a Str > b Str > Null
    do
      tlog a
      tlog b
  
  foo "kek" "lol"
  expect "lol"
  expect "kek"
  
  tlog "hello"
  expect "hello"
  
  if 12
    case 33 > do >> tlog "33"
    case 12 > do >> tlog "12"
    else > do >> tlog "else"
  expect "12"
  var lol 12
  if lol
    case 33 > do >> tlog "33"
    case 12 > do >> tlog "12"
    else > do >> tlog "else"
  expect "12"
  
  if 0
    case 33 > do >> tlog "33"
    case 2212 > do >> tlog "12"
    else > do >> tlog "else"
  expect "else"
  
  for i 0 5 1
    do
      #print > itos i
      tlog > itos i
      
  expect "4"
  expect "3"
  expect "2"
  expect "1"
  expect "0"
  
  var i 0
  while > lt i 5
    do
      tlog "while"
      set i > add i 1
      
  expect "while"
  expect "while"
  expect "while"
  expect "while"
  expect "while"
  
  type Car
    name Str
    owner Person
  
  fn bar > age Int > name Str > Person
    do
      new Person
        name: name
        age: age
  
  var p2 > bar 20 "Lol"
  #dump p2
  
  tlog p2.name
  expect "Lol"
  tlog > itos p2.age
  expect "20"
  
  var c
    new Car
      name: "BMW"
      owner: p2
      
  tlog c.name
  expect "BMW"
  tlog c.owner.name
  expect "Lol"
  
  fn baz > a Int > b Int > Int
    do
      add a b
      
  tlog > itos >> baz 1 2
  expect "3"
  
  fn wow > a Int > b Int > Int
    do
      var asd > add a b
      var xxx > add a b
      add asd xxx
  
  tlog > itos >> wow 1 2
  expect "6"
  
  print "All tests passed"
  
  fn !macro > a AstNode > AstNode
    do
      dumpNode a
      a

!macro > print >> itos 1 >> itos 2

comptime
  var word > "1"
  var nodeType > "Int"
  var line_number > 0
  var indentation > 0
  var children > array
  var doc_comment > ""
  var node > newNode word nodeType line_number indentation children doc_comment
  dumpNode node

comptime
  fn intNode value Int > AstNode
    do
      var word > "1"
      var nodeType > "Int"
      var line_number > 0
      var indentation > 0
      var children > array
      var doc_comment > ""
      var node > newNode word nodeType line_number indentation children doc_comment
      node
      
  fn !intNode value Int > AstNode > do >> intNode value
  
print > itos >> !intNode 1

comptime
  print "hello world >n "
  dumpNode > !intNode 1

CODE;
assert(isset($KEYWORDS));
$pplines = preProcessLines($code);
$nodes = makeAstNodes($pplines, $KEYWORDS);
foreach($nodes as $node) {
  Interpreter::parse($node);
}
