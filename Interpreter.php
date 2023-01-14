<?php

# todo: finish the interpreter
# todo: create benchmarks -> jit

/**
 * The Kek Programming Language
 *
 * The kek programming language is a statically typed
 * transpiler-preprocessor.
 *
 * Its primary goal is to generate code in for example C
 * and allow a crazy level of flexibility and static analysis.
 *
 * So there is the interpretation of the comptime kek code
 * and then the in kek implemented transpilation of the
 * noncomp kek code to C, Js, Lua, etc. ...
 *
 * List [Type]
 * Dict [Type, Type]
 * Bool
 * Str
 * Int
 * Float
 * Char
 * Void
 * Null
 * Union [Array [Type]]
 * Option [Type]
 * Error
 * Result [Type]  ->The instance or an error
 * Name -> A name or code that evaluates to a string that matches a name
 * # Name is not allowed to contain spaces or special characters
 * AstNode -> The nodes of the kek AST from the non comptime nodes
 * Function[Dict[Str, Type], Type]
 *
 * Types are just strings that are used to identify types.
 *
 * Comptime-specials:
 * comptime
 * !  -> prefix for comptime calls, that are not in the comptime context
 * # you could say macros *
 *
 * Comptime builtins:
 * ...
 */

include_once "keywords.php";
include "AstNode.php";
include "preProcessLines.php";
include "makeAstNodes.php";


// this is the script record type
class Record {
  public string $name;
  /** @var array<string, string> */
  public array $fields;
}

class Instance {
  public Record $type;
  public array $fields;
}

# todo: make functions just variables and wrap then into a value type
#       that can also have a doc comment and annotations
#       So we can call/analyze even the script code and do stuff like this
#       var a > fn Void do >> print "hello"
# todo: make functions wok like this

class Interpreter {
  static array $records = [];
  static array $globals = [];
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
    global $KEYWORDS;
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
      if ($node->word == "false") {
        return false;
      } else {
        return true;
      }
    }
    
    if ($node->word == "new") {
      $typename = $node->children[0]->word;
      $fields = [];
      foreach ($node->children as $key => $name_arg) {
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
      // assert that the function name is not already used
      assert(!array_key_exists($function_name, static::$functions), $function_name);
      // assert that the function name is not a keyword
      assert(!in_array($function_name, $KEYWORDS, $function_name));
      // assert that the function name is lowercase or, if it starts with uppercase that it is also a type
      assert(
        $function_name[0] == strtolower($function_name[0]) || array_key_exists($function_name, static::$records),
        $function_name . " is not a valid function name"
      );
      $function_args = []; // args to check against
      $do_node = null;
      $return_type = null;
      foreach ($node->children as $key => $c) {
        if ($key == 0) {
          continue; // ignore function name
        }
        if (count($c->children) == 0) {
          // todo: assert that this is a type
          $return_type = $c;
          continue;
        }
        if ($c->word == "do") {
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
        foreach ($function_args as $key => $type_name) {
          $given_argument = $args[$i];
          if ($type_name != "AstNode") {
            $given_argument = static::eval($given_argument, $env);
            // todo: do type check here
          }
          $local_env[$key] = $given_argument;
          $i++;
        }
        
        $ret = null;
        foreach ($do_node->children as $c) {
          if ($c)
            $ret = static::eval($c, $local_env);
        }
        // todo: check return type here
        return $ret;
      };
      // if the name is "_", this is an anonymous function
      // just return it
      if ($function_name == "_") {
        return $function;
      }
      static::$functions[$function_name] = $function;
      return null;
    }
    
    if ($node->word == "var") {
      $var_name = $node->children[0]->word;
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
          $cond = static::eval($c->children[0], $env);
          if ($cond == $value) {
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
      while (static::eval($condition, $env)) {
        static::eval($do, $env);
      }
      return null;
    }
    
    if ($node->word == "type") {
      #print_r($node);
      $typename = $node->children[0]->word;
      $fields = [];
      foreach ($node->children as $key => $value) {
        if ($key == 0) continue;
        $field_name = $value->word;
        // static eval the type -> should return a string
        $fields[$field_name] = static::eval($value->children[0], $env);
      }
      unset($i);
      $record = new Record();
      $record->name = $typename;
      $record->fields = $fields;
      // assert that the type name starts with a capital letter
      assert($typename[0] == strtoupper($typename[0]));
      // assert that the type name is not already used as a type
      assert(!isset(static::$records[$typename]));
      static::$records[$typename] = $record;
      // now add the type name as function, so we can get the string
      static::$functions[$typename] = static function () use
      (
        $typename
      ) {
        return $typename;
      };
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
      for ($i = $start_value; $i < $end_value; $i += $step) {
        $env[$name] = $i;
        static::eval($do, $env);
      }
      // remove name from env
      unset($env[$name]);
      return null;
    }
    
    if ($node->word == "set") {
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
      } else {
        // this measn we are not allowed to change globals, except if the global s a instance
        $env[$name] = static::eval($node->children[1], $env);
      }
      return null;
    }
    
    if ($node->word == "each") {
      // each k i list/dict do
      $key_name = $node->children[0]->word;
      assert(count($node->children[0]->children) == 0);
      assert($node->children[0]->type == "name");
      $value_name = $node->children[1]->word;
      assert(count($node->children[1]->children) == 0);
      assert($node->children[1]->type == "name");
      $list_or_dict = static::eval($node->children[2], $env);
      assert(is_array($list_or_dict), print_r($list_or_dict, true));
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
    if ($node->word == "map") {
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
    
    if ($node->word == "do") {
      // do block
      $ret = null;
      foreach ($node->children as $c) {
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
    
    throw new Exception("Unknown node type: {$node->word} " . $node->type . " " . $node->line_number);
  }
  
  # todo: create all the Ast nodes into a kek builtin Node type
  # todo: create the builtin type node
  /**
   * @throws Exception
   */
  static function parse(AstNode $node): ?AstNode {
    // parse node
    if ($node->word == "comptime") {
      $ret = null;
      foreach ($node->children as $c)
        $ret = static::eval($c, static::$globals);
      if ($ret == null) return null;
      assert($ret instanceof AstNode);
      // if we gor a return node: append it to the root if the
      // makro is a root node
      // else return it -> it is then appended to the parent node
      if ($node->indentation == 0) {
        static::$non_comptime_nodes[] = $ret;
      }
      return null;
    }
    
    if (str_starts_with($node->word, "!") and $node->type == "name") {
      print("Find a macro node: {$node->word}\n");
      $node = static::eval($node, static::$globals);
      if ($node == null) return null;
      assert($node instanceof AstNode);
      return static::parse($node);
    }
    
    $new_children = [];
    foreach ($node->children as $c) {
      $c = static::parse($c);
      if ($c == null) continue;
      $new_children[] = $c;
    }
    $node->children = $new_children;
    
    if ($node->indentation == 0) {
      static::$non_comptime_nodes[] = $node;
      return null;
    }
    return $node;
  }
  
  /**
   * Inits and resets the interpreter.
   * @return void
   */
  public static function init() {
    static::$records = [];
    static::$globals = [];
    static::$non_comptime_nodes = [];
    static::$functions = [];
    static::$tlogs = [];
    // todo: include the builtin functions from other files later on
    
    Interpreter::$functions = [
      
      "print" => function (
        array $args,
        array $env
      ): void {
        foreach ($args as $i => $a) {
          $args[$i] = Interpreter::eval($a, $env);
        };
        assert(is_string($args[0]));
        assert(count($args) == 1, print_r($args, true));
        echo str_replace(">n", "\n", $args[0]);
      },
      
      "itos" => function (
        array $args,
        array $env
      ): string {
        foreach ($args as $i => $a) {
          $args[$i] = Interpreter::eval($a, $env);
        };
        assert(count($args) == 1);
        return (string)$args[0];
      },
      
      "dumpTypes" => function (
        array $args,
        array $env
      ): void {
        foreach ($args as $i => $a) {
          $args[$i] = Interpreter::eval($a, $env);
        };
        assert(count($args) == 0);
        var_dump(Interpreter::$records);
      },
      
      "dump" => function (
        array $args,
        array $env
      ): void {
        foreach ($args as $i => $a) {
          $args[$i] = Interpreter::eval($a, $env);
        };
        assert(count($args) == 1);
        var_dump($args[0]);
      },
      
      "tlog" => function (
        array $args,
        array $env
      ): void {
        foreach ($args as $i => $a) {
          $args[$i] = Interpreter::eval($a, $env);
        };
        assert(is_string($args[0]), print_r($args[0], true));
        assert(count($args) == 1);
        Interpreter::$tlogs[] = $args[0];
      },
      
      "expect" => function (
        array $args,
        array $env
      ): void {
        // pops the last tlog and compares it to the expected value
        foreach ($args as $i => $a) {
          $args[$i] = Interpreter::eval($a, $env);
        };
        assert(is_string($args[0]));
        assert(count($args) == 1);
        $expected = $args[0];
        $actual = array_pop(Interpreter::$tlogs);
        if ($expected != $actual) {
          throw new Exception("Expected $expected, got $actual");
        }
      },
      
      "lt" => function (
        array $args,
        array $env
      ): bool {
        foreach ($args as $i => $a) {
          $args[$i] = Interpreter::eval($a, $env);
        };
        assert(count($args) == 2);
        return $args[0] < $args[1];
      },
      
      "add" => function (
        array $args,
        array $env
      ): int {
        foreach ($args as $i => $a) {
          $args[$i] = Interpreter::eval($a, $env);
        };
        assert(count($args) == 2);
        return $args[0] + $args[1];
      },
      
      "dumpThisNode" => function (
        array $args,
        array $env
      ): void {
        // do not eval the node...
        assert(count($args) == 1);
        var_dump($args[0]);
      },
      
      "dumpNode" => function (
        array $args,
        array $env
      ): void {
        $node = Interpreter::eval($args[0], $env);
        assert(count($args) == 1);
        assert($node instanceof AstNode);
        var_dump($node);
      },
      
      "eval_node" => function (
        array $args,
        array $env
      ): mixed {
        $node = Interpreter::eval($args[0], $env);
        assert(count($args) == 1);
        assert($node instanceof AstNode);
        return $node;
      },
      
      "newNode" => function (
        array $args,
        array $env
      ): AstNode {
        $word = Interpreter::eval($args[0], $env);
        $type = Interpreter::eval($args[1], $env);
        $line_number = Interpreter::eval($args[2], $env);
        $children = Interpreter::eval($args[4], $env);
        $doc_comment = Interpreter::eval($args[5], $env);
        $node = new AstNode();
        $node->word = $word;
        $node->type = $type;
        $node->line_number = $line_number;
        $node->indentation = 2;  # changed if this is appeneded toanother node
        $node->children = $children;
        $node->doc_comment = $doc_comment;
        $node->creator = "macro";
        return $node;
      },
      
      // change to list
      "array"   => function (
        array $args,
        array $env
      ): array {
        $array = [];
        foreach ($args as $arg) {
          $array[] = Interpreter::eval($arg, $env);
        }
        return $array;
      },
      
      "dict" => function (
        array $args,
        array $env
      ): array {
        $array = [];
        foreach ($args as $arg) {
          $array[] = Interpreter::eval($arg, $env);
        }
        return $array;
      },
      
      "ppAllCollectedNodes" => function (
        array $args,
        array $env
      ): void {
        assert(count($args) == 0);
        foreach (Interpreter::$non_comptime_nodes as $node) {
          echo $node;
        }
      },
      
      "addNodeAsChild" => function (
        array $args,
        array $env
      ): void {
        $node = Interpreter::eval($args[0], $env);
        $child = Interpreter::eval($args[1], $env);
        assert($node instanceof AstNode);
        assert($child instanceof AstNode);
        $node->children[] = $child;
        return;
      },
      
      "allNodes" => function (
        array $args,
        array $env
      ): array {
        return Interpreter::$non_comptime_nodes;
      },
      
      /**
       * The get type function returns the type of kek script instances.
       * Types that are returned ars strings.
       *
       * If you invoke a generic function, it is checked if this type exists
       * otherwise it is created as record type.
       */
      "getTypeName"  => function (
        array $args,
        array $env
      ) {
        return "getType";
      },
      
      // returns the type info based on the given type name
      "getTypeInfo" => function (
        array $args,
        array $env
      ){}
      
      # todo: create generic type -> own generics type for
      # gtype function, where firts eklemnt is list of placeholders
      
      // todo: basic math
      // todo: file input output
      // todo: string manipulation
      // todo: array manipulation
      // todo: dict literal
      // todo: typeof
      // todo: include file
    
    ];
    include "types.php";  // the type definitions and constructors
  }
}


/** @language=*.kek */
$code = <<<CODE

comptime
  ###
    In Kek we handle types as interfaces.
    Types are just descriptions of data.
  ###
  type Node
    word Str
    type Str
    line_number Int
    indentation Int
    children > Array "Node"
    doc_comment Str
    creator Str
      
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
      var indentation > 1000
      var children > array
      var doc_comment > ""
      var node > newNode word nodeType line_number indentation children doc_comment
      node
      
  fn !intNode value Int > AstNode > do >> intNode value

type SomeStruct
  a Int
  
type SomeStruct2
  a Int
  b weak SomeStruct   # weak means the type is not managed
  # if we delete SomeStruct2 the SomeStruct will not be deleted
  # This allows also to save this pointer into other weak pointers

  # Each access to a weak pointer needs to be checked if it is still valid

# this should set SomeStruct.a to readonly
# !readonly SomeStruct "a"

fn someFunc
  a Int
  b Int
  Int
  do
    add a b
    
print > itos >> !intNode 1

comptime
  print "hello world >n "
  dumpNode > !intNode 1

comptime
  ppAllCollectedNodes

comptime
  var _allNodes > allNodes
  each key _node _allNodes
    do
      print "node: >n "
      dumpNode _node

CODE;
Interpreter::init();
assert(isset($KEYWORDS));
$pplines = preProcessLines($code);
$nodes = makeAstNodes($pplines, $KEYWORDS);
foreach ($nodes as $node) {
  Interpreter::parse($node);
}
