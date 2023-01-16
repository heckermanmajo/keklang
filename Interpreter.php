<?php

// display errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once "AstNode.php";
include_once "preProcessLines.php";
include_once "makeAstNodes.php";

// this is the script record type
class Record {
  public function __construct(
    public string $name = "",
    /** @var array<string, string> */
    public array  $fields = []
  ) {
  }
}

class Instance {
  public function __construct(
    public Record $type,
    public array  $fields
  ) {
  }
}

function kekNodeToAstNode(Instance $kekNode): AstNode {
  # todo: Check all types and stuff
  $node = new AstNode();
  $node->word = $kekNode->fields["word"];
  $node->type = $kekNode->fields["type"];
  $node->line_number = $kekNode->fields["line_number"];
  $node->indentation = $kekNode->fields["indentation"];
  $node->doc_comment = $kekNode->fields["doc_comment"];
  $node->creator = $kekNode->fields["creator"];
  
  foreach ($kekNode->fields["children"] as $c) {
    $node->children[] = kekNodeToAstNode($c);
  }
  return $node;
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

# todo: rename parse to eval
# todo: rename eval to evalScriptNode

# todo: all functions of the interpreter should be accessible from the kek code

# todo: create a ASTNODE-record and use this for the manipulation: Translate all read nodes
#       to this record and then we can translate it from kek to c for example

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
          "builtin" => "Bool",
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
      "Node" => new Record(
        name: "Node",
        fields: ([
          "word"     => "Str",
          "type"     => "Str",
          "children" => "List<Node>",
          "line_number" => "Int",
          "indentation" => "Int",
          "doc_comment" => "Str",
          "creator" => "Str",
        ])
      ),
    ];
    static::$globals = [];
    static::$non_comptime_nodes = [];
    static::$functions = [];
    static::$tlogs = [];
    // todo: include the builtin functions from other files later on
    

    
    Interpreter::$functions = [
      
      "Node::ast" => function(
        array $args,
        array &$env
      ) {
        $node = Interpreter::eval($args[0], $env);
        # todo: CHeck all types and stuff
        return kekNodeToAstNode($node);
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
    foreach (scandir("builtins/") as $file) {
      if (str_ends_with($file, ".php")) {
        include "builtins/$file";
      }
    }
  }
}


