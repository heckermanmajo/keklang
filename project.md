
# Resicon
Complex systems are hard enough. 
Make the tools easy.

## Systems-Theory
- Komplexitätsbestimmung
- Wer darf mit wem reden und wer nicht?
- Wie wird was benannt
- spatial dimensions (normal, 3d, 2d, tiled) Add icons to systems for spaces, so we can visualize

## Debugging Mode
- loggs
- Lots of debug checks
- step through code -> what was executed, etc

## Keklang 
Simple two level language.
This allows for great flexibility and 
maintain easy code.

## Aiden
Super integrated development environment.

## Domain Knowledge
Train a model on the domain specific knowledge
you need to create your simulation.

## Disable /enable and compare different strategies

## Use ai to optimize configuration for KPIS

## Train decisions during the simulation

## Runner/Deploy

## Analyze
- statische Analyse

## Code
- globale suche
- Code schreiben

## Documentation is there from the start

## Tests: Unit tests

## Report

## Team

## Code Modules for plug and Play

## Visualisation

## Input Gui

## Snap shotting and saving/comparing and continuing 

## Input via csv or json?

## Interaktion während der simulation


# Roadmap
Ende des jahres, ot abbilden können in kek.
Entwickelt in aiden.
Und dann auf einem server ausgeführt.

- Keklang - Interpreter : kekLang mit einer menge tests
- Keklang - to c -> Generate simple c code 
- Aiden - Ide(login and stuff) 
- Aiden - Editor (edit files and classes, etc.)
- Aiden - Debugger (debugging mode)/visualisation
- Runner (run the simulation)
- Analyze (analyze the simulation)
- Input Gui (input for the simulation)



// ? and ?? are special types -> expand to Option and Result
// All types can be nested
// type arguments are separated by , and put in []
// type arguments can be nested ...
// make this function work recursively
// a type can be any alphanum string with a capital letter
// there are no spaces in the expression and no other signs
function splitTypeExpressionToArray(string $typeExpression): array {
...
}
assert(splitTypeExpressionToArray("Foo") == ["name"=>"Foo", "children" => null]);
assert(splitTypeExpressionToArray("Int") == ["name"=>"Int", "children" => null]);
assert(splitTypeExpressionToArray("?Int") == ["name"=>"Option", "children" => [["name"=>"Int", "children" => null]]]);
assert(splitTypeExpressionToArray("??Int") == ["name"=>"Result", "children" => [["name"=>"Int", "children" => null]]]);
assert(splitTypeExpressionToArray("List[Int]") == ["name"=>"List", "children" => [["name"=>"Int", "children" => null]]]);
assert(splitTypeExpressionToArray("Dict[String,Int]") == ["name"=>"Dict", "children" => [["name"=>"String", "children" => null],["name"=>"Int", "children" => null]]]);
assert(splitTypeExpressionToArray("Bar[String,Int]") == ["name"=>"Bar", "children" => [["name"=>"String", "children" => null],["name"=>"Int", "children" => null]]]);
assert(splitTypeExpressionToArray("Dict[?Foo,Int]") == ["name"=>"Dict", "children" => ["name"=>"Option","children" => [[["name"=>"Foo", "children" => null]]],["name"=>"Int", "children" => null]]]);