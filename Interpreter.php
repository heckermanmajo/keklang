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

function kekNodeToAstNode(
  Instance $kekNode
): AstNode {
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

function AstNodeToKekNode(
  AstNode $astNode
): Instance {
  $record = Interpreter::$records["Node"];
  $fields = [
    "word"        => $astNode->word,
    "type"        => $astNode->type,
    "line_number" => $astNode->line_number,
    "indentation" => $astNode->indentation,
    "doc_comment" => $astNode->doc_comment,
    "creator"     => $astNode->creator,
    "children"    => [],
  ];
  foreach ($astNode->children as $c) {
    $fields["children"][] = AstNodeToKekNode($c);
  }
  return new Instance($record, $fields);
}

class KekError extends Exception {
  public int $codeLine = 0;
  public string $codeString = "";
  public string $trace = "";
  public $message = "";
  
  public function __construct(
    string $message = "",
    int    $codeLine = 0,
    string $codeString = "",
    string $trace = ""
  ) {
    parent::__construct($message, 0, null);
    $this->codeLine = $codeLine;
    $this->codeString = $codeLine;
    $this->trace = $trace;
  }
  
  public function getRecordInstance(): Instance {
    return new Instance(
      Interpreter::$records["KekError"],
      [
        "message"  => $this->message,
        "codeLine" => $this->codeLine,
        "code"     => $this->code,
        "trace"    => $this->trace
      ]
    );
  }
}

# todo: create helper functions for stuff like
#      - getTypeName: Functions have a type name like: Function<Str,Str,Str><Str>
#      - getTypeInfo
#      - resolveToName: this means, string, function call, or name
#      - resolveToValue: this means, string, function call, or value

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
# "tos" methods

# todo: create a Type - Type
# todo: join -> method of list
# todo: make char list also method

# todo: add the execution stack to the interpreter, for real stack traces
#       and then each function call can add to the stack trace and substract
#       when it returns

# todo: make all return return values solid

class Interpreter {
  static array $records = [];
  static array $globals = [];
  static array $functions = [];
  static array $non_comptime_nodes = [];
  static array $tlogs = []; // used for testing
  static array $error_trace = []; // used for error tracing
  # todo: add all function infos here
  /**
   * @var array $functions
   */
  static array $function_infos = [];
  
  static function err(string $message): never {
    $trace = implode("\n", self::$error_trace);
    throw new KekError($message, 0, "", $trace);
  }
  
  static function assert(
    $comp,
    $message
  ) {
    if (!$comp) {
      self::err($message);
    }
  }
  
  static function resolveToAName(
    AstNode $node,
            &$env,
    bool    $allow_string_string = true
  ): string {
    $node->word = trim($node->word);
    if (count($node->children) == 0) {
      # todo: check if this is a valid name
      self::assert(!is_numeric($node->word), "Name must be a string, given " . gettype($node->word));
      self::assert($node->word != "", "Name must be a string, given " . gettype($node->word));
      
      // if the name is a string, we can just return it
      if ($allow_string_string) {
        if (array_key_exists($node->word, $env)) {
          return $env[$node->word];
        }
      }
      return $node->word;
    } else {
      $name = Interpreter::eval($node, $env);
      self::assert(is_string($name), "Name must be a string, resolving of node '" . $node->word . "' to string failed");
      self::assert(!is_numeric($name), "Name must be a string, given " . gettype($name));
      return $name;
    }
  }
  
  /**
   * Resolve to instance if the string contains ".".
   *
   * @param AstNode $node
   * @param $env
   * @return void
   */
  static function resolveToInstance(
    string $str,
           &$env
  ): Instance {
    #$str = trim($str);
    $parts = explode(".", $str);
    $instance = $env[$parts[0]];
    for ($i = 1; $i < count($parts)-1; $i++) {
      $instance = $instance->fields[$parts[$i]];
    }
    Interpreter::assert($instance instanceof Instance, "Expected instance, got " . gettype($instance));
    return $instance;
  }
  
  static function getTypeStringOfValue(mixed $value) {
    if (is_string($value)) {
      return "Str";
    } elseif (is_int($value)) {
      return "Int";
    } elseif (is_float($value)) {
      return "Float";
    } elseif (is_bool($value)) {
      return "Bool";
    } elseif (is_array($value)) {
      if (isset($node[-1])) {
        return "Dict";
      }
      return "List";
    } elseif (is_null($value)) {
      return "Null";
    } elseif (is_object($value)) {
      if ($value instanceof Instance) {
        return $value->type->name;
      } elseif ($value instanceof Closure) {
        # todo: use function info for better function tyoes
        return "Function";
      } elseif ($value instanceof Record) {
        return "Record";
      }
    } else {
      Interpreter::err("Unknown type of value: " . gettype($value));
    }
    Interpreter::err("Unknown type of value: " . gettype($value));
  }
  
  static function checkGivenStringIsType(
    string $node
  ) {
    $builtin_types = [
      "Str",
      "Int",
      "Float",
      "Bool",
      "Null",
      "List",
      "Dict",
      "Function",
      "Record",
    ];
    if (!in_array($node, $builtin_types) or !isset(self::$records[$node])) {
      Interpreter::err("Unknown type: " . $node);
    }
  }
  
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
    
    if ($node->word == "null") {
      return null;
    }
    
    if (str_starts_with($node->word, "!")) {
      $node->type = "macro";
      #$node->word = substr($node->word, 1);
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
      Interpreter::$error_trace[] = "Function: " . $node->word . " at line " . $node->line_number;
      $function = static::$functions[$node->word];
      $args = [];
      foreach ($node->children as $c) {
        $args[] = $c; // the function decides if it evals the given nodes
      }
      $ret = $function($args, $env);
      array_pop(Interpreter::$error_trace);
      return $ret;
    }
    
    if (array_key_exists($node->word, $env)) {
      return $env[$node->word];
    }
    
    if (str_contains($node->word, ".")) {
      Interpreter::$error_trace[] = "Trying to access property: " . $node->word;
      $parts = explode(".", $node->word);
      $_env =array_merge($env, static::$globals);
      $obj = $_env[$parts[0]];
      foreach ($parts as $key => $value) {
        if ($key == 0) continue;
        $obj = $obj->fields[$value];
      }
      array_pop(Interpreter::$error_trace);
      return $obj;
    }
    
    // handle protocols that start with ::
    if (str_starts_with($node->word, "::")) {
      // first children is the protocol target
      Interpreter::$error_trace[] =
        "Evaluation ot this node: " . $node->word . " " . $node->type . " " . $node->line_number;
      $value = Interpreter::eval($node->children[0], $env);
      array_pop(Interpreter::$error_trace);
      if (is_string($value)) {
        $func_name = "Str" . $node->word;
      } elseif (is_int($value)) {
        $func_name = "Int" . $node->word;
      } elseif (is_float($value)) {
        $func_name = "Float" . $node->word;
      } elseif (is_bool($value)) {
        $func_name = "Bool" . $node->word;
      } elseif (is_array($value)) {
        $func_name = "List" . $node->word;
      } else {
        $type = get_class($value);
        $func_name = match ($type) {
          "Instance" => $value->type->name . $node->word,
          "Record" => "Type" . $node->word,
          default => Interpreter::err("Unknown type: $type ")
        };
      }
      // todo: check if the function exists
      if (!array_key_exists($func_name, static::$functions)) {
        Interpreter::err("Protocol function $func_name does not exist");
      }
      $function = static::$functions[$func_name];
      $args = [$value];
      foreach ($node->children as $key => $c) {
        if ($key == 0) continue; // ignore the "this" variable
        $args[] = $c; // the function decides if it evals the given nodes
        // except for "this" which is the first child
      }
      Interpreter::$error_trace[] =
        "Protocol $func_name called in line " . $node->line_number . " Indent: " . $node->indentation;
      $ret = $function($args, $env);
      array_pop(Interpreter::$error_trace);
      return $ret;
    }
    
    // this is a named parameter, if it ends with ":"
    if (str_ends_with($node->word, ":")) {
      return static::eval($node->children[0], $env);
    }
  
    if (array_key_exists($node->word, static::$globals)) {
      return static::$globals[$node->word];
    }
    
    Interpreter::err("Unknown word: " . $node->word);
    
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
    Interpreter::$error_trace[] = "Parsing node: " . $node->word . " " . $node->type . " " . $node->line_number;
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
      array_pop(Interpreter::$error_trace);
      return null;
    }
    
    if (str_starts_with($node->word, "!") and $node->type == "name") {
      #print("Find a macro node: {$node->word}\n");
      Interpreter::$error_trace[] = "Macro: " . $node->word . " at line " . $node->line_number;
      $node = static::eval($node, static::$globals);
      if ($node == null) return null;
      assert($node instanceof AstNode);
      array_pop(Interpreter::$error_trace);
      array_pop(Interpreter::$error_trace);
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
      array_pop(Interpreter::$error_trace);
      return null;
    }
    array_pop(Interpreter::$error_trace);
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
          "name"    => "Str",
          "builtin" => "Bool",
          #"definitionFile" => "Str",
          #"docComment" => "Str",
          "fields"  => "Dict<Str,Str>"
        ])
      ),
      "KekError" => new Record(
        name: "KekError",
        fields: ([
          "message"  => "Str",
          "codeLine" => "Int",
          "code"     => "Str",
          "trace"    => "Str"
        ])
      ),
      "Node"     => new Record(
        name: "Node",
        fields: ([
          "word"        => "Str",
          "type"        => "Str",
          "children"    => "List<Node>",
          "line_number" => "Int",
          "indentation" => "Int",
          "doc_comment" => "Str",
          "creator"     => "Str",
        ])
      ),
    ];
    static::$globals = [];
    static::$non_comptime_nodes = [];
    static::$functions = [];
    static::$tlogs = [];
    // todo: include the builtin functions from other files later on
    
    Interpreter::$functions = [
      
      "Node::makeAstNode" => function (
        array $args,
        array &$env
      ) {
        $node = Interpreter::eval($args[0], $env);
        # todo: CHeck all types and stuff
        return kekNodeToAstNode($node);
      },
      
      "isDict" => function (
        array $args,
        array &$env
      ): bool {
        $value = Interpreter::eval($args[0], $env);
        return isset($value[-1]);
      },
      
      "dict" => function (
        array $args,
        array &$env
      ): array {
        $dict = [];
        for ($i = 0; $i < count($args); $i += 2) {
          $key_val = Interpreter::eval($args[$i], $env);
          $dict[$key_val] = Interpreter::eval($args[$i + 1], $env);
          # todo: check all are the same
        }
        if (!isset($dict[-1])) $dict[-1] = true;
        return $dict;
      },
      
      "kset" => function (
        array $args,
        array &$env
      ): mixed {
        $dict = Interpreter::eval($args[0], $env);
        $key = Interpreter::eval($args[1], $env);
        $value = Interpreter::eval($args[2], $env);
        $dict[$key] = $value;
        return null;
      },
      
      "getLastNode" => function (
        array $args,
        array &$env
      ): Instance {
        $last_inserted_node = static::$non_comptime_nodes[count(static::$non_comptime_nodes) - 1];
        $node = AstNodeToKekNode($last_inserted_node);
        return $node;
      },
      
      "getAstNodeAsNode" => function (
        array $args,
        array &$env
      ): Instance {
        $node = Interpreter::eval($args[0], $env);
        assert($node instanceof AstNode);
        $node_instance = AstNodeToKekNode($node);
        return $node_instance;
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
        $value = Interpreter::eval($args[0], $env);
        assert(count($args) == 1);
        var_dump($value);
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
      
      "getAllNodes" => function (
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


