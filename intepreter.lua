local Utils = {
  stringStartsWith = function(str, start)
    return str:sub(1, #start) == start
  end,
  stringEndsWith = function(str, ending)
    return ending == "" or str:sub(-#ending) == ending
  end,
  stringContains = function(str, sub)
    return str:find(sub) ~= nil
  end,
  stringSplit = function(str, sep, with_sep)
    -- the separator should be included if with_sep is true
    local result = {}
    local regex = ("([^%s]+)"):format(sep)
    for each in str:gmatch(regex) do
      table.insert(result, each)
      if with_sep then
        table.insert(result, sep)
      end
    end
  end,
  stringTrimLeft = function(str)
    return str:gsub("^%s*(.-)$", "%1")
  end,
  stringTrimRight = function(str)
    return str:gsub("(.-)%s*$", "%1")
  end,
  stringTrim = function(str)
    return str:gsub("^%s*(.-)%s*$", "%1")
  end,
  stringReplaceAll = function(str, find, replace)
    return str:gsub(find, replace, -1)
  end,
  stringIsNumber = function(str)
    return tonumber(str) ~= nil
  end,
  stringIsBoolean = function(str)
    return str == "true" or str == "false"
  end,
  stringIsNull = function(str)
    return str == "null"
  end,
  numberIsInteger = function(num)
    return num % 1 == 0
  end,
  stringStartsWithCapitalLetter = function(str)
    return str:match("^%u")
  end,
}

--- @class Token
--- @field word string
--- @field indent number
local Token = {
  __cls__ = "Token",
}

function Token.new(word, indent, children)
  local self = setmetatable({ }, Token)
  self.word = word
  self.indent = indent or 0
  self.return_type = "" -- this field is used for type checking
  self.children = children or { }
  return self
end

function isToken(t)
  return getmetatable(t) == Token
end

--- @class File
--- @field name string
--- @field tokens Token[]
local File = {
  __cls__ = "File",
}

function File.new()
  local self = setmetatable({ }, File)
  self.name = ""
  self.tokens = { }
  return self
end

function isFile(t)
  return getmetatable(t) == File
end

--- @class Field
--- @field type string
--- @field default Value
local Field = {
  __cls__ = "Field",
}

function Field.new()
  local self = setmetatable({ }, Field)
  self.type = ""
  self.default = nil
  return self
end

function isField(t)
  return getmetatable(t) == Field
end

--- @class Type
--- @field foreign boolean
--- @field fields table<string, Field>
local Type = {
  __cls__ = "Type",
}

function Type.new(
  fields,
  foreign
)
  local self = setmetatable({ }, Type)
  self.foreign = foreign or false
  self.fields = fields or { }
  self.protocols = { }
  return self
end

function isType(t)
  return getmetatable(t) == Type
end

--- @class Value
--- @field type Type
--- @field value any
local Value = {
  __cls__ = "Value",
}

function Value.new(type, value)
  local self = setmetatable({ }, Value)
  self.type = type -- type is the signature for a function
  self.value = value  -- can ba a number, bool, string, or table
  return self
end

--- @class Function The function name tells us if it is a protocol implementation.
--- @field name string
--- @field arguments table<string, Type>
--- @field returns string
--- @field code table<Token>|function

local Function = {
  __cls__ = "Function",
}

function Function.new(arguments, returnType, code)
  local self = setmetatable({ }, Function)
  self.arguments = arguments
  self.returns = returnType  -- string
  self.func = code
  return self
end

function isFunction(t)
  return getmetatable(t) == Function
end

function Function:execute(interpreter, args)
  interpreter:pushScope()
  for k, v in pairs(self.arguments) do
    -- todo: check types and arguments
    env[k] = args[k]
  end
  interpreter:eval(self.func)
end

--- @class Scope
--- @field parent Scope
--- @field variables table<string, Value>
local Scope = {
  __cls__ = "Scope",
}

function Scope.new(parent, variables, functions)
  local self = setmetatable({ }, Scope)
  self.parent = parent
  self.variables = variables or { }
  self.functions = functions or { }
  return self
end

function isScope(t)
  return getmetatable(t) == Scope
end

--- @class Interpreter
--- @field files File[]
--- @field global Scope
--- @field scopeFrames Scope[]
--- @field types table<string, Type>
--- @field callTrace string[]

local Interpreter = {}

function Interpreter.new()
  local self = setmetatable({}, { __index = Interpreter })
  self.error = nil
  self.current_mode = ""  -- if, else, function, etc
  self.current_loops = {} -- list of the loop nodes, so we can jump out or continue with labels
  self.global = Scope.new(
    nil,
    { }, -- no global variables for now
    {
      ["Int::tos"] = Function.new(
        {
          ["this"] = "Int",
        },
        "String", -- return type
        function(interpreter)
          -- we know this is in the env
          --- @type number
          local this = interpreter:resolveName(
            "this", false, false
          )
          -- we know this is type checked at this point
          return tostring(this)
        end
      )
    }
  )
  self.scopeFrames = { self.global }
  -- special types like Option, Union, Void, are not defined as types
  -- since they dont really exist
  -- Container types (List, Dict, Function) are created on demand
  self.types = {
    ["Int"] = Type.new(
      {},
      true
    ),
    ["Float"] = Type.new(
      {},
      true
    ),
    ["String"] = Type.new(
      {},
      true
    ),
    ["Bool"] = Type.new(
      {},
      true
    ),
    ["Null"] = Type.new(
      {},
      true
    ),
    -- Internal is a type that offers no functions at all ...
    ["Internal"] = Type.new(
      {},
      true
    )
  }
  self.callTrace = {}
  return self
end

function isInterpreter(obj)
  return getmetatable(obj) == Interpreter
end

--- This is called if we call a function.
function Interpreter:pushScope()
  local scope = Scope.new(self.scopeFrames[#self.scopeFrames])
  table.insert(self.scopeFrames, scope)
end

function Interpreter:popScope()
  table.remove(self.scopeFrames)
end

function Interpreter:currentScope()
  return self.scopeFrames[#self.scopeFrames]
end

function Interpreter:assert(cond, message)

end

function Interpreter:error(message)

end

function Interpreter:parse(node)
  -- @annotation
  -- !makro
  -- comptime
end

function Interpreter:resolveName(
  name,
  allowGlobals,
  allowParents
)

end

--- @param node Token
--- @param allowGlobals boolean
--- @param allowParents boolean
function Interpreter:getReturnTypeOfFunction(
  node,
  allowGlobals,
  allowParents
)

end

function Interpreter:getReturnTypeOfIfStatement(node)

end

function Interpreter:getReturnTypeOfDoStatement(node)

end

--- @param node Token
--- @return number|boolean|string|Value
function Interpreter:eval(node)
  local word = node.word
  local utils = Utils
  -- Jump over all nodes after an error occurred
  if self.error and not word == "catch" then return nil end

  if word == "catch" then
    -- todo: handle catch case
  end

  -- "123" -> string
  -- "123.0" -> string
  -- "true" -> string
  -- "lol" -> string
  if utils.stringStartsWith(word "\"") then
    return word:sub(2, -2)
  end

  -- 123  -> int
  -- 123.  -> float
  -- 123.123 -> float
  if utils.stringIsNumber(word) then
    if utils.stringContains(word, ".") then
      tonumber(word)
    else
      tonumber(word)
    end
  end

  -- true -> bool
  -- false -> bool
  if word == "true" then return true end
  if word == "false" then return false end

  -- null -> nil
  if word == "null" then return nil end


  -- ::protocolCall -> protocolCall
  if utils.stringStartsWith("::") then

  end

  -- :functionCall -> functionCall (no this and type lookup)
  if utils.stringStartsWith(":") then

  end

  -- .access -> var access value
  -- .access.lol -> var access value
  -- .access.lol.fooo -> var access value
  if utils.stringStartsWith(".") then

  end

  -- $.name -> access to Interpreter

  if utils.stringStartsWith("$.") then

  end

  -- $::funcName -> into compile process callback
  if utils.stringStartsWith("$::") then

  end

  -- ??TypeName
  -- ?TypeName
  if utils.stringStartsWith("?") then
    if utils.stringStartsWith("??") then

    end
  end

  if utils.stringStartsWithCapitalLetter(word) then
    if utils.stringContains("::") then

    end
  end

  --[[
    fn var type redef alias
    list dict new
    delete set # remove from current scope
    do  # new scope
    if else then while for case each
    try catch
  ]]

  -- name or keyword
  if word == "if" then

  end

  if word == "then" then

  end

  if word == "case" then

  end

  if word == "else" then

  end

  if word == "do" then

  end

  if word == "while" then

  end

  if word == "for" then

  end

  if word == "each" then

  end

  if word == "continue" then

  end

  if word == "break" then

  end

  if word == "var" then

  end

  if word == "fn" then

  end

  if word == "type" then

  end

  if word == "redef" then

  end

  if word == "alias" then

  end

  if word == "delete" then

  end

  if word == "redef" then

  end

  if word == "set" then

  end

  if word == "list" then

  end

  if word == "dict" then

  end

  if word == "interface" then

  end

  if word == "set" then

  end

  if word == "try" then

  end

  if word == "do" then

  end

  -- TypeName::funcName  -> name of a protocol definition
  -- TypeName   --> check that the type exist, other wise error
  --            a TypeName will return string





  -- todo: do all the keywords here in eval

  -- The builtin functions are not in eval
  -- they are just written in lua

  -- We can create functions that start with an uppercase letter
  -- but we can only call them in a TypeString Context
  -- interpret a node
  -- if it is numeric, return a number
  -- if it is a string, return a string (with quotes)
  -- if it starts
end
