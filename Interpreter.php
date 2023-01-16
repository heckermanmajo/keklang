<?php

// display errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
  
  public function __construct(
    string $name = "",
    array  $fields = []
  ) {
    $this->name = $name;
    $this->fields = $fields;
  }
  
}

class Instance {
  public Record $type;
  public array $fields;
  
  public function __construct(
    Record $type,
    array  $fields
  ) {
    $this->type = $type;
    $this->fields = $fields;
  }
}

class KekError extends Exception {
  public int $codeLine = 0;
  public string $codeString = "";
  
  public $message = "";
  
  public function __construct(
    string $message = "",
    int    $codeLine = 0,
    string $codeString = ""
  ) {
    parent::__construct($message, 0, null);
    $this->codeLine = $codeLine;
    $this->codeString = $codeLine;
  }
  
  public function getRecordInstance(): Instance {
    return new Instance(
      Interpreter::$records["KekError"],
      [
        "message"  => $this->message,
        "codeLine" => $this->codeLine,
        "code"     => $this->code
      ]
    );
  }
}

# todo: make all "names" also accept normal string values so we can do
#       var > concat "a_" >> itos 123 > "some value"
#       # like var a_123 "some value"
#       -> type, fn

# todo: allow varargs -> via dict and list VarList, VarDict "Type"

# todo: Create builtins for file io, so i can create a import function in kek itself,
#       The more functions i can implement in kek itself, the better

# todo: replace assert with KekError -> can be catched and handled by kek itself

# todo: how to break a loop?

# todo: Separate dict from list by setting dict -1 so we can check if -1 is set
#       and if not, we know its a list

# todo: make get and push for list methods
#       make print methods

# todo: btos, ftos, itos -> methods for the type

# todo: create a Type - Type
# todo: join -> method of list
# todo: make char list also method

# todo: add the execution stack to the interpreter, for real stack traces
#       and then each function call can add to the stack trace and substract
#       when it returns


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
    
    // handle protocols that start with ::
    if (str_starts_with($node->word, "::")) {
      // first children is the protocol target
      $value = Interpreter::eval($node->children[0], $env);
      if(is_string($value)){
        $func_name = "Str" . $node->word;
      }elseif(is_int($value)){
        $func_name = "Int" . $node->word;
      }elseif(is_float($value)){
        $func_name = "Float" . $node->word;
      }elseif(is_bool($value)){
        $func_name = "Bool" . $node->word;
      }elseif(is_array($value)){
        $func_name = "List" . $node->word;
      }else {
        $type = get_class($value);
        $func_name = match ($type) {
          "Instance" => $value->type->name . $node->word,
          "Record" => "Type" . $node->word,
          default => throw new Exception("Unknown type: $type")
        };
      }
      // todo: check if the function exists
      if (!array_key_exists($func_name, static::$functions)) {
        throw new KekError("Protocol function $func_name does not exist");
      }
      $function = static::$functions[$func_name];
      $args = [$value];
      foreach ($node->children as $key => $c) {
        if ($key == 0) continue; // ignore the "this" variable
        $args[] = $c; // the function decides if it evals the given nodes
        // except for "this" which is the first child
      }
      return $function($args, $env);
    }
    
    // this is a named parameter, if it ends with ":"
    if (str_ends_with($node->word, ":")) {
      return static::eval($node->children[0], $env);
    }
    
    print_r($env);
    throw new Exception("Unknown node type: {$node->word} " . $node->type . " " . $node->line_number);
  }
  
  # todo: create all the Ast nodes into a kek builtin Node type
  # todo: create the builtin type node
  /**
   * @throws Exception
   */
  static function parse(
    AstNode $node,
    array   &$env = null
  ): ?AstNode {
    if ($node->word == "comptime") {
      $ret = null;
      foreach ($node->children as $c) {
        if ($env === null) {
          $ret = static::eval($c, static::$globals);
        } else {
          $ret = static::eval($c, $env);
        }
      }
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
      return static::parse($node, $env);
    }
    
    $new_children = [];
    foreach ($node->children as $c) {
      $c = static::parse($c, $env);
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
    static::$records = [
      "TypeInfo" => new Record(
        name: "TypeInfo",
        fields: ([
          "name"   => "Str",
          #"builtin" => "Bool",
          #"definitionFile" => "Str",
          #"docComment" => "Str",
          "fields" => "Dict<Str,Str>"
        ])
      ),
      "KekError" => new Record(
        name: "KekError",
        fields: ([
          "message"  => "Str",
          "codeLine" => "Int",
          "code"     => "Str",
        ])
      ),
    ];
    static::$globals = [];
    static::$non_comptime_nodes = [];
    static::$functions = [];
    static::$tlogs = [];
    // todo: include the builtin functions from other files later on
    
    Interpreter::$functions = [
      
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
      
      "allNodes"    => function (
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
      "getTypeName" => function (
        array $args,
        array $env
      ) {
        return "getType";
      },
      
      // returns the type info based on the given type name
      // typeinfo -> type of type info-> script type
      "getTypeInfo" => function (
        array $args,
        array $env
      ) {
        // return the info struct based on the given value
        // the type info is created if it is builtin
      },
      
      
      # todo: create generic type -> own generics type for
      # gtype function, where firts eklemnt is list of placeholders
      
      // todo: basic math
      // todo: file input output
      // todo: string manipulation
      // todo: array manipulation
      // todo: dict literal
      // todo: typeof
      // todo: include file
      // todo: all_functions
    
    ];
    include "types.php";  // the type definitions and constructors
    foreach (scandir("builtins/") as $file)
      if (str_ends_with($file, ".php"))
        include "builtins/$file";
    
    
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
      #dumpNode a
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
  #dumpNode node

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
  #dumpNode > !intNode 1

comptime
  ppAllCollectedNodes

comptime
  var _allNodes > allNodes
  each key _node _allNodes
    do
      print "node: >n "
      #dumpNode _node

CODE;
Interpreter::init();
// read all test files and interpret them
foreach (scandir("./tests/") as $file) {
  if (str_ends_with($file, ".kek")) {
    $code = file_get_contents("./tests/$file");
    $pplines = preProcessLines($code);
    $nodes = makeAstNodes($pplines);
    foreach ($nodes as $node) {
      Interpreter::parse($node);
    }
  }
}

if (debug_backtrace() == 0) {
  Interpreter::init();
  assert(isset($KEYWORDS));
  $pplines = preProcessLines($code);
  $nodes = makeAstNodes($pplines);
  foreach ($nodes as $node) {
    Interpreter::parse($node);
  }
  
}

if (isset($_POST["code"])) {
  Interpreter::init();
  $code = $_POST["code"];
  $pplines = preProcessLines($code);
  $nodes = makeAstNodes($pplines);
  ob_start();
  foreach ($nodes as $node) {
    Interpreter::parse($node);
  }
  $result = ob_get_clean();
  
  echo json_encode(array(
                     "result"             => $result,
                     "non_comptime_nodes" => Interpreter::$non_comptime_nodes,
                     "records"            => Interpreter::$records,
                     "functions"          => Interpreter::$functions,
                   ));
}
