<?php

namespace kek;

include_once "parser.php";

use Exception;

class FunctionArgument {
  public function __construct(
    public string $name,
    public string $type,
    public bool $readonly = false,
    public bool $consume = false,
    public mixed $default_value = null,
    public string $doc_comment = "",
    public array $annotations = [],
  ) {
  }
}

class KekFunction {
  public function __construct(
    public string $name,
    /** @var array<string, FunctionArgument> */
    public mixed $function,
    public string $doc_comment = "",
    /**
     * If the function has friends, only those friends can call it.
     * The friends need to be types.
     * @var array <string, string>
     */
    public array $friends = [],
    public array $args = [],
    public string $return_type = "",
    # annotations can be just executed like normal code and
    # add to an annotations' dict[String,Any]
    # @ KekCType AnnotationName "AnnotationData"
    # the first and second arguments are names
    public array $annotations = []
  ) {
  }
}

/**
 * A field for a type, since a field can contain many field
 * specific data.
 */
class Field {
  public function __construct(
    public string $name,
    public string $type,
    public mixed $default_value = null,
    public string $doc_comment = '',
    public array $annotations = []
  ) {
  }
}

function splitTypeExpressionToArray(string $typeExpression): array {
  // Initialize variables to store current type name and children
  $typeName = "";
  $typeChildren = null;

  // Iterate through each character in the type expression
  for ($i = 0; $i < strlen($typeExpression); $i++) {
    $char = $typeExpression[$i];

    // If the character is "[", it signifies the start of a type argument
    if ($char === "[") {
      // Get the type name before the "["
      $typeName = substr($typeExpression, 0, $i);

      // Recursively call the function to get the children of the current type
      $typeChildren = splitTypeArguments(substr($typeExpression, $i));
      break;
    }
  }

  // If the type name is not set, it means there are no type arguments
  if (empty($typeName)) {
    $typeName = $typeExpression;
  }

  // Check if the type name starts with "?" or "??"
  if (substr($typeName, 0, 1) === "?") {
    $typeName = "Option";
    if (substr($typeName, 0, 2) === "??") {
      $typeName = "Result";
    }
    $typeChildren = [splitTypeExpressionToArray(substr($typeName, 1))];
  }

  // Return an array containing the type name and children
  return ["name" => $typeName, "children" => $typeChildren];
}

function splitTypeArguments(string $typeArgumentExpression): array {
  $typeArguments = [];
  $currentArgument = "";
  $nested = 0;
  for ($i = 0; $i < strlen($typeArgumentExpression); $i++) {
    $char = $typeArgumentExpression[$i];
    if ($char === "[") {
      $nested++;
    } elseif ($char === "]") {
      $nested--;
    } elseif ($char === "," && $nested === 0) {
      $typeArguments[] = splitTypeExpressionToArray($currentArgument);
      $currentArgument = "";
    } else {
      $currentArgument .= $char;
    }
  }
  if (!empty($currentArgument)) {
    $typeArguments[] = splitTypeExpressionToArray($currentArgument);
  }
  return $typeArguments;
}

// implements, extends, contains, expandInto, functions -> allow for inheritance/interfaces
class Type {

  public function __construct(

    public string $name,

    /** @var array<string, Field> */
    public array $fields = [],

    /** @var array<string, KekFunction> */
    public array $methods = [],

    public bool $atomic = false,
    /**
     * If we create a generic type, we create the type based
     * on the meta type.
     * This allows for example to change some behavior of
     * a type afterwards.
     *
     * @var Type|null
     */
    public ?Type $meta_type = null,

    # annotations can be just executed like normal code and
    # add to an annotations dict[String,Any]
    # @ KekCType AnnotationName "AnnotationData"
    # the first and second arguments are names
    public array $annotations = array(),
    public array $friends = array(),
    public array $uses = array(),
  ) {
  }

  public static function getDefaultTypes(): array {

    $types = [];
    $types["Internal"] = new Type("Internal", atomic: true);
    $types["Name"] = new Type("Name", atomic: true);
    // the "type" is the string, the "typeinfo" is the type-class
    // so "type" is basically a string with a type that exists
    // if we create a type that not exists, we try to created it
    // since we can create container like list, dict, Union, Option and Result
    // if the type is a not existing container, we create it
    // this is only true for the builtin containers
    $types["Type"] = new Type("Type", atomic: true);
    $types["TypeInfo"] = new Type("Type", atomic: true);
    $types["Int"] = new Type("Int", atomic: true);
    $types["Float"] = new Type("Float", atomic: true);
    $types["String"] = new Type("String", atomic: true);
    $types["Boolean"] = new Type("Boolean", atomic: true);
    $types["Null"] = new Type("Null", atomic: true);
    $types["Any"] = new Type("Any", atomic: true);
    $types["Void"] = new Type("Void", atomic: true);

    $types["Error"] = new Type("Error", [
      "message" => "String",
      "trace" => "List[String]",
    ], atomic: true);

    $types["AstNode"] = new Type("AstNode", [
      "word" => "String",
      "line_number" => "Int",
      "indentation" => "Int",
      "annotations" => "List[AstNode]",
      "children" => "List[AstNode]",
      "doc_comment" => "String",
      "type" => "String",
      "creator" => "String",
    ], atomic: true);

    $types["List[AstNode]"] = new Type("List[AstNode]", [], atomic: false);

    return $types;
  }

  /**
   * This function
   * @param Interpreter $i
   * @param string $typeExpression
   * @return Type
   */
  public static function getTypeFromExpression(
    Interpreter $i,
    string $typeExpression
  ): Type {
    if (isset($i->types[$typeExpression])) {
      return $i->types[$typeExpression];
    } else {
      // todo: create if it is a known type
      throw new \Exception("Type $typeExpression not found");
    }
  }

  // creates some default protocols for a user defined type
  // ::getAnnotations() -> returns a dict of all annotations
  // ::getAnnotation(String) -> returns the annotation with the given name
  // ::@(String, Any) -> sets the annotation with the given name to the given value
  // ::dump  -> debug function
  // ::set
  // ::get
  // ::delete
  // ::equals
  // ::toString
  /**
   * @return array<string, KekFunction>
   */
  public static function createProtocolsForUserDefinedType(): array {
  }

  // creates a builtin container and appends it to the given interpreter
  // this works for list, dict, union, option and result
  // todo: how do we handle nested containers?
  public static function createDict(string $dictType, Interpreter $i): void{

  }

}

class Value {
  public function __construct(
    public Type $type,
    /** @var array<int, Value|string|int|float|null>|array<string, Value|string|int|float|null> */
    public array|string|int|float|null $data,
    public bool $is_constant = false,
    public bool $is_frozen = false,
    public bool $is_local = false,
    /**
     * If this value was consumed by another function,
     * it cannot be used anymore.
     *
     * @var bool
     */
    public bool $is_consumed = false,
    # annotations can be just executed like normal code and
    # add to an annotations' dict[String,Any]
    # @ KekCType AnnotationName "AnnotationData"
    # the first and second arguments are names
    public array $annotations = [],

  ) {
  }
}

class Scope {
  public function __construct(
    public ?Scope $parent,
    public string $return_type = "Void",

    public array $vars = [],
    public array $imported_vars= [],

    public array $types= [],
    public array $imported_types= [],

    public array $interfaces= [],
    public array $imported_interfaces= [],
  ) {
  }
}

// todo: also allow to set the "how to compile vars" in the kek code itself
class Interpreter {

  public static array $keywords = [
    "var",
    "type",
    "fn",
    "interface",

    "export",
    "import",

    "include",

    "do",

    "new",
    "delete",
    "alias",

    "if",
    "then",
    "else",
    "case",

    "while",
    "for",
    "each",
    "break",
    "continue",
    "return",

    "const",
    "freeze",
    "melt",
    "readonly",
    "private",
    "local",
    "weak",
    "friend",

  ];

  public Scope $currentEnv;

  /** @var Scope[] */
  public array $envs = [];

  /** @var array<string> */
  public array $error_trace = [];

  public bool $return_current_scope = false;
  public mixed $return_value = null;
  public int $break_current_loop = 0;
  public int $continue_current_loop = 0;

  /**
   * @var array <string, Type>
   */
  public array $interpreterCallbacks = [];

  public function __construct() {
    $this->currentEnv = new Scope(null);
    $this->envs[] = $this->currentEnv;
  }

  // executes a file and returns the scope of this file
  // this is used to include files.
  // the include function can then decide what to do with this
  // scope -> We can simply import to global
  // We can only import exported stuff from the file .. .
  public function doFile(string $path): Value {

    $code = file_get_contents($path);
    $lines = Parser::preProcessLines($code);
    $astNodes = Parser::makeAstNodes($lines);

    foreach ($astNodes as $astNode) {
      $this->executeNode($astNode);
    }

    // the return value should be an Optional[KekError]
    // this way we can check if the file was executed correctly
    // and if not, we can get the error
    return new Value( // currently no return value
      type: $this->currentEnv->types["Void"],
      data: null,
    );
  }

  // needs to create also all methods for the specific container since the return values are
  // different for each container
  public function createListContainerType(string $typeName): void {
    if (isset($this->currentEnv->types["List[" . $typeName . "]"])) {
      throw new \Exception("Type List[$typeName] already exists");
    }
    // check that the typename exists
    if (
      !isset($this->currentEnv->types[$typeName])
      && !isset($this->currentEnv->imported_types[$typeName])
    ) {
      throw new \Exception("Type $typeName does not exist");
    }

    $this->currentEnv->types["List[$typeName]"]
      = new Type(
      name: "List[$typeName]",
      fields: [
        "data" => new Field(
          name: "data",
          type: "Internal",
          doc_comment: "
            The internal data of the list.
            In Kek-Script, this is a php array. 
            In cKek, this is a pointer to a c array.
          ",
        ),
      ],
      methods: [
        "add" => new KekFunction(
          "append",
          function (Value $self, Value $thingToAppend) use ($typeName) {
            // all type checking is done before the call
            $self->data[] = $thingToAppend;
          },
          doc_comment: "
            Appends a new element to the list.
            ```kek
               var myList :> List[Int] = new List[Int]
               ::add myList 5
            ```   
            @param this List[$typeName] The list to append to. 
            @param $typeName thingToAppend The element to append to the list.
            @return Void
          ",
          args: [
            "thingToAppend" => $typeName,
          ],
          return_type: "Void",
        ),
        //"get"
        //"set"
        //"unset"
        //"equals"
        //"toString"
        //"len"
        //"clear"
        //"dump"
        //"getAnnotations"
        //"getAnnotation"
        //"setAnnotation"
      ],
      atomic: false
    );
  }

  // create new scope
  // create new type
  // create new function / method
  // create new value
  // create new variable
  // resolve Name
  // resolve Type -> String or Dict[String,List[Float]]
  // export Function
  // export Type
  // export Variable
  // call a function -> check args, new scope
  // collect Ast nodes to parsed nodes
  //    make the collection directly in a variable
  // call the invoker-functions
  //    make the callbacks, so they determine if the
  //    "normal" code is also executed or not

  // type expression is a string like "MyClass" or "List[MyClass]"
  // only non-existing builtin containers are created
  // all other types need to exist or unknown type error
  public function resolveTypeExpression(string $typeExpression): string {
    if (str_starts_with($typeExpression, "??")) {
      $typeExpression = substr($typeExpression, 2);
      // Result[]
    }
    if (str_starts_with($typeExpression, "?")) {
      $typeExpression = substr($typeExpression, 1);
      // Optional[]
    }
    // translate | -> to union
  }

  public function executeNode(AstNode $node): void {

    if ($node->word === "comptime"){
      foreach ($node->children as $child) {
        $this->executeScriptNode($child);
      }
    }

    if(str_starts_with($node->word,"!")){
      // remove the !
      $node->word = substr($node->word,1);
      $value = $this->executeScriptNode($node);
      // todo: value needs to be of type AstNode
      //       and is then added to the list of nodes
      //        But it needs to be the internal AstNode type
    }

    // just print for now
    // later we will add the node to the internal list of parsed nodes
    // this way we can translate the nodes to c code from the kek code itself
    print "executeNode: " . $node->word . "" . PHP_EOL;

  }

  /**
   *
   * @param AstNode $node
   * @return Value|string|int|float|bool|null
   * @throws Exception
   */
  public function executeScriptNode(AstNode $node): Value|string|int|float|bool|null {

    // literals
    if ($node->type === "int") return (int)$node->word;
    if ($node->type === "float") return (float)$node->word;
    if ($node->type === "string") return substr($node->word, 1, -1);
    if ($node->type === "bool") return (bool)$node->word;
    if ($node->type === "null") return null;

    if (in_array($node->word, static::$keywords)) {
      switch ($node->word) {

        ###################################################################
        # VAR
        # var myVar :> Int 5
        ###################################################################
        case "var":
          // create a new variable
          if (count($node->children) !== 4) {
            throw new \Exception("var needs 3 children");
          }
          $name = $this->executeScriptNode($node->children[0]);
          $typeSign = $node->children[1]->word;
          if ($typeSign !== ":>") {
            throw new \Exception("Expected :> after variable name");
          }
          $typeName = $this->executeScriptNode($node->children[1]->children[0]);
          if (isset($this->currentEnv->types[$typeName])) {
            $typeInstance = $this->currentEnv->types[$typeName];
          } elseif (isset($this->currentEnv->imported_types[$typeName])) {
            $typeInstance = $this->currentEnv->imported_types[$typeName];
          } else {
            throw new \Exception("Type $typeName does not exist or is not imported");
          }
          $value = $this->executeScriptNode($node->children[2]);
          $this->currentEnv->vars[$name] = new Value(
            type: $typeInstance,
            data: $value,
          );
          break;

        ###################################################################
        # TYPE
        #  type MyType
        #    foo: ?String null
        #    bar: Int 0
        ###################################################################
        case "type":
          // todo: "friend" keyword
          $name = $this->executeScriptNode($node->children[0]);
          // todo: check name for correct name and also check if it is
          //       already defined
          // fields are defined as names: type default
          $fields = [];
          foreach ($node->children as $key => $field) {
            if ($key === 0) continue;
            if ($field->name == "use") {
              $typeName = $this->executeScriptNode($field->children[0]);
              // todo: check if type exists and is interface
            }
            $fieldName = $field->word;
            if (!str_ends_with($fieldName, ":"))
              throw new \Exception("End the field name with ':'");
            $fieldType = $this->executeScriptNode($field->children[0]);
            # todo: default is mandatory, but can be "undefined" keyword
            $fieldDefault = $this->executeScriptNode($field->children[2]);
            // todo: check all stuff
            $fields[$fieldName] = new Field($fieldName, $fieldType, $fieldDefault);
          }
          break;

        ###################################################################
        # FN
        # fn myFunction
        #   arg1: String "defaultString"
        #   arg2: Int 24
        #   do :> String
        #     ::print "Hello World"
        #     return "Hello World"
        ###################################################################
        case "fn":
          # todo: allow friend as firts arguments in the do list
          # todo: if we overwrite a field that was implemented by an interface
          #       we need to check that we dont change the signature
          $name = $this->executeScriptNode($node->children[0]);
          $args = [];
          $returnType = null;
          foreach ($node->children as $key => $child) {
            if ($key === 0) continue;
            if ($child->word === "do") {
              $typeSign = $child->children[0]->word; // should be :>
              // String, since type can be undefined at this point
              $returnType = $this->executeScriptNode($child->children[1]);
              $doNodes = array_slice($node->children, 2);
              break;
            }
            $argName = $child->word;
            if (!str_ends_with($argName, ":"))
              throw new \Exception("End the argument name with ':'");
            $argType = $this->executeScriptNode($child->children[0]);
            if (count($child->children) === 2) {
              $argDefault = $this->executeScriptNode($child->children[1]);
            } else {
              $argDefault = null;
            }
            $args[$argName] = new FunctionArgument(
              $argName,
              $argType,
              $argDefault
            );
          }
          // safe do node into function - body
          $callback = function (array $params) use ($doNodes) {
            // expect $params to be a correct assoc array
            // also expect the params to be executed already
            foreach ($params as $key => $value){
              $this->currentEnv->vars[$key] = $value;
            }
            $ret = null;
            foreach ($doNodes as $key => $child) {
              $ret = $this->executeScriptNode($child);
              if($this->return_value!== null) {
                $ret = $this->return_value;
                $this->return_value = null;
                break;
              }
            }
            return $ret;
          };

          $fn = new KekFunction(
            name: $name,
            function: $callback,
            args: $args,
            return_type: $returnType
          );

          $typeName = explode("::", $name)[0];

          // todo: check that first argument is "this: $typeName"
          if (
            !isset($this->currentEnv->types[$typeName])
            or !isset($this->curretEnv->imported_types[$typeName])
          ) {
            throw new \Exception("Type $typeName does not exist");
          }
          // todo: check if method already exists
          $type = $this->resolveTypeExpression(explode("::", $name)[0]);
          $type->methods[$name] = $fn;

          break;

        case "interface":
          # simple interface to add protocols to other types
          $name = $this->executeScriptNode($node->children[0]);
          $types = [];
          foreach ($node->children as $key => $child) {
            if ($key === 0) continue;
            $types[$child->children[0]]
              = $this->executeScriptNode($child->children[1]);
          }
          $this->currentEnv->interfaces[$name] = new Type(
            name: $name,
            fields: $types,
            methods: []
          );
          break;

        case "export":
          # put a type, function or variable into the global scope
          $name = $this->executeScriptNode($node->children[0]);
          // check that is a name/variable/type/interface
          break;

        case "import":
          # import a type, function or variable from the outer scope
          # or the global scope
          # if the sope above and the global scope do not have the
          # requested type, function or variable, loop over all other
          # scopes and try to find it there
          break;

        case "include":
          # include and parse another file
          break;

        case "do":
          # creates a new scope
          # needs a return type
          $returnSign = $node->children[0]->word;
          if ($returnSign !== ":>") {
            throw new \Exception("Expected :> after do");
          }
          $returnType = $this->executeScriptNode($node->children[1]);
          # todo: expect import keyword
          # todo: call all the other children
          $scope = new Scope($this->currentEnv, $returnType);
          $this->currentEnv = $scope;
          $this->envs[] = $scope;
          $ret = null;
          foreach ($node->children as $key => $child) {
            if ($key === 0 or $key === 1) continue;
            $ret = $this->executeScriptNode($child);
            // if the return keyword was used
            // we need to return the current scope
            if($this->return_current_scope){
              $this->return_current_scope = false;
              return $this->return_value;
            }
          }
          // if no return keyword was used
          // we need to return the last value
          return $ret;

        case "new":
          // note: ?? is just a shortcut for an Result type like MyClass|Error
          // which is basically Result[MyClass]
          // note: ? is just an shortcut for an Optional type like MyClass|Null
          // which is basically Optional[MyClass]
          // node the | is just a shortcut for a Union type like Union[MyClass,MyOtherClass]
          // varargs, first is the type, then the args
          $type = $this->resolveTypeExpression($node->children[0]);
          $args = [];
          foreach ($node->children as $key => $child) {
            if ($key === 0) continue;
            $args[] = $this->executeScriptNode($child);
          }
          return $type->create($args, $this);
          break;

        case "delete":
          $name = $this->executeScriptNode($node->children[0]);
          // todo: check if variable/method exists

          break;

        case "if":
          # then, else, case
          $condition = $this->executeScriptNode($node->children[0]);

          if ($node->children[1]->word === "case") {

            $cases = [];
            foreach ($node->children as $key => $case) {
              if ($key == 0) continue;
              if ($key == count($node->children) - 1) continue;
              $case_cond = $this->executeScriptNode($case->children[0]);
              $case_code = [];
              foreach ($case->children as $key => $case_child) {
                if ($key == 0) continue;
                $case_code[] = $case_child;
              }
              $cases[$case_cond] = $case_code;
            }
            // get the last "else" case
            $else = $node->children[array_key_last($node->children)];
            foreach ($cases as $case_cond => $case_code) {
              if ($case_cond === $condition) {
                foreach ($case_code as $case_code_node) {
                  $this->executeScriptNode($case_code_node);
                  // if the return keyword was used
                  // we need to return the current scope
                  // the return value is stored in $this->return_value
                  if($this->return_current_scope)return null;
                }
                return null;
              }
            }
            foreach ($else->children as $else_child) {
              $this->executeScriptNode($else_child);
              // if the return keyword was used
              // we need to return the current scope
              // the return value is stored in $this->return_value
              if($this->return_current_scope)return null;
            }
            return null;
          } else {
            $then_children = $node->children[1]->children;
            $else_children = $node->children[2]->children;
            $do_nodes = [];
            if (count($node->children) == 2) { // cond then
              if($condition)$do_nodes = $then_children;
            } else {
              if ($condition) $do_nodes = $then_children;
              else $do_nodes = $else_children;
            }
            foreach ($do_nodes as $do_node) {
              $this->executeScriptNode($do_node);
              // if the return keyword was used
              // we need to return the current scope
              // the return value is stored in $this->return_value
              if($this->return_current_scope)return null;
            }
          }
          break;

        case "while":
          $condition = $this->executeScriptNode($node->children[0]);
          while($condition) {
            foreach ($node->children as $key => $child) {
              if ($key === 0) continue;
              $this->executeScriptNode($child);
              // if the return keyword was used
              // we need to return the current scope
              // the return value is stored in $this->return_value
              if($this->return_current_scope)return null;
            }
            $condition = $this->executeScriptNode($node->children[0]);
          }
          break;

        case "for":
          $var_name = $this->executeScriptNode($node->children[0]);
          $var_type = $this->executeScriptNode($node->children[1]);
          $var_default = $this->executeScriptNode($node->children[2]);
          $condition = $node->children[3];
          $increment_or_decrement = $node->children[4];
          // rest is the code ...
          // todo: check that names are not used
          // todo: check that types are valid
          $this->currentEnv->vars[$var_name] = $var_default;
          // todo: assert int
          // also allow smaller than steps
          for ($i = $var_default; $i < $condition; $i += $increment_or_decrement) {
            $this->currentEnv->vars[$var_name] = $i;
            foreach ($node->children as $key => $child) {
              if ($key === 0) continue;
              if ($key === 1) continue;
              if ($key === 2) continue;
              if ($key === 3) continue;
              if ($key === 4) continue;
              $this->executeScriptNode($child);
              // if the return keyword was used
              // we need to return the current scope
              // the return value is stored in $this->return_value
              if($this->return_current_scope)return null;
              if($this->break_current_loop != 0){
                $this->break_current_loop -= 1;
                break 2; //break two -> the inner foreach loop and the outer for loop
              }
              if($this->continue_current_loop != 0){
                $this->continue_current_loop -= 1;
                break; //break one -> the inner foreach loop
              }
            }
          }
          // unset the variable
          unset($this->currentEnv->vars[$var_name]);
          return null;

        case "each":
          // key: Type value: Type myList
          $key_name = $this->executeScriptNode($node->children[0]);
          $key_type = $this->executeScriptNode($node->children[1]);
          $value_name = $this->executeScriptNode($node->children[2]);
          $value_type = $this->executeScriptNode($node->children[3]);
          $list = $this->executeScriptNode($node->children[4]);
          // rest is the code ...
          break;

        case "break":
          if (count($node->children) === 1) {
            $this->break_current_loop = $this->executeScriptNode($node->children[0]);
          } else {
            $this->break_current_loop = 1;
          }
          break;

        case "continue":
          if(count($node->children) === 1) {
            $this->continue_current_loop = $this->executeScriptNode($node->children[0]);
          } else {
            $this->continue_current_loop = 1;
          }
          break;

        ###################################################################
        # RETURN
        # return "Hello World"
        ###################################################################d
        case "return":
          # todo: check that  the return is the return value of the
          #      current method
          $this->return_current_scope = true;
          $ret = $this->executeScriptNode($node->children[0]);
          return $ret;

        ###################################################################
        #  CONST
        ###################################################################
        case "const":
          if (count($node->children) !== 1) {
            throw new Exception("const needs exactly one child");
          }
          $name = $this->executeScriptNode($node->children[0]);
          if (!is_string($name)) {
            throw new Exception("const needs a string as name");
          }
          if (!isset($this->current_scope->variables[$name])) {
            throw new Exception(
              "const needs a variable that exists\n"
              . "Can also not set const to imported variables."
            );
          }
          $this->current_scope->variables[$name]->is_const = true;
          break;

        ###################################################################
        #  FREEZE
        #  freeze variable
        ###################################################################
        case "freeze":
          if (count($node->children) !== 1) {
            throw new Exception("freeze needs exactly one child");
          }
          $name = $this->executeScriptNode($node->children[0]);
          if (!is_string($name)) {
            throw new Exception("freeze needs a string as name");
          }
          if (!isset($this->current_scope->variables[$name])) {
            throw new Exception(
              "freeze needs a variable that exists\n"
              . "Can also not set freeze to imported variables."
            );
          }
          $this->current_scope->variables[$name]->is_frozen = true;
          break;


        ###################################################################
        #  MELT
        #  melt variable
        ###################################################################
        case "melt":
          if (count($node->children) !== 1) {
            throw new Exception("melt needs exactly one child");
          }
          $name = $this->executeScriptNode($node->children[0]);
          if (!is_string($name)) {
            throw new Exception("melt needs a string as name");
          }
          if (!isset($this->current_scope->variables[$name])) {
            throw new Exception(
              "melt needs a variable that exists\n"
              . "Can also not set melt to imported variables."
            );
          }
          $this->current_scope->variables[$name]->is_frozen = false;
          break;

        default:
          throw new Exception("Keyword at wrong position: " . $node->word);
      }
    }

    // add annotation information
    if (str_starts_with("@", $node->word)) {
      $name = substr($node->word, 1);
      $annotation_key = $this->executeScriptNode($node->children[0]);
      $annotation_value = $this->executeScriptNode($node->children[1]);
      // check if name is a type
      // then check if it is a method (contains ::)
      // then check if it is a variable
      // then append the annotation to
    }

    // type expression -> starts with ? or ?? or Capital letter
    // just returns a string with the type expression
    if (
      str_starts_with("?", $node->word)
      || str_starts_with("??", $node->word)
      || ctype_upper($node->word[0])
    ) {
      // todo: parse the type expression in a solid way
    }

    if(count($node->children) >= 0){
      // this a method call
      // todo: keep in mind the the named parameters
      $self = $this->executeScriptNode($node->children[0]);
      $type = $self->type;
      // check if the type exists
    }else{
      // since everything is a protocol
      // we know that variable access has no children
      // and is lowercase
      // named arguments: the children of name:
      // name -> look for a variable
      // resolve access to a variable
      // now we need to resolve to a name
      if (str_contains($node->word, ".")) {

      } else {
        // resolve to a variable -> simple name
      }
    }

  }
}