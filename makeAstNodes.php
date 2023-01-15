<?php
include_once "keywords.php";
include_once "AstNode.php";
include_once "preProcessLines.php";

/**
 * @return array<AstNode>
 */
function makeAstNodes(
  array $preProcessedLines
): array {
  $nodes = array();
  
  $na = function (
    string     $type,
    string     $word,
    int|string $line_number,
    int|string $indentation
  ) use
  (
    &
    $nodes
  ): AstNode {
    $node = new AstNode();
    $node->word = $word;
    $node->line_number = $line_number;
    $node->indentation = $indentation;
    $node->type = $type;
    $nodes[] = $node;
    #print "add node";
    return $node;
  };
  
  $lines = $preProcessedLines;
  $doc_comment_the_line_before = '';
  $nodes_before = [];
  print "\n";
  foreach ($lines as $line) {
    
    #print $line . "\n";
    
    if (str_starts_with(trim($line), "#")) {
      $doc_comment_the_line_before = explode("[", $line)[0];
      continue;
    }
    $raw_string = trim(explode("[", $line)[0]);
    # todo: error if a "[" is in a string
    $extended_info = json_decode("[" . explode("[", $line)[1]);
    #print "extended_info: " . json_encode($extended_info) . "\n";
    $line_number = $extended_info[0];
    $indentation = $extended_info[1];
    
    if (trim($raw_string) == "") {
      continue;
    }
    
    if (str_starts_with(trim($raw_string), "((")) {
      $value = str_replace("((", "", $raw_string);
      $value = str_replace("))", "", $value);
      $node = $na("string", $value, $line_number, $indentation);
      goto collect_children;
    }
    
    if (is_numeric($raw_string)) {
      if (str_contains($raw_string, ".")) {
        $node = $na("float", $raw_string, $line_number, $indentation);
      } else {
        $node = $na("int", $raw_string, $line_number, $indentation);
      }
      goto collect_children;
    }
    
    if ($raw_string == "true" or $raw_string == "false") {
      $node = $na("boolean", $raw_string, $line_number, $indentation);
      goto collect_children;
    }
    
    if (str_ends_with($raw_string, ":")) {
      $node = $na("named_param", $raw_string, $line_number, $indentation);
      goto collect_children;
    }
    
    $node = $na("name", $raw_string, $line_number, $indentation);
    
    collect_children:
    
    # append the doc comment the line before
    
    $indent = str_repeat(" ", $node->indentation);
    $node->doc_comment = $doc_comment_the_line_before;
    $node->doc_comment = str_replace("###", "", $node->doc_comment);
    $node->doc_comment = str_replace("#", "\n" . $indent, $node->doc_comment);
    if (strlen($node->doc_comment) > 0) $node->doc_comment .= "\n";
    $doc_comment_the_line_before = '';
    
    # check if i am on a higher indentation level than the last node
    $end = fn(
      $arr
    ) => $arr[count($arr) - 1] ?? false;
    $last_node = $end($nodes_before);
    while ($last_node && $last_node->indentation >= $node->indentation) {
      array_pop($nodes_before);
      $last_node = $end($nodes_before);
    }
    if ($last_node) {
      $last_node->children[] = $node;
    }
    $nodes_before[] = $node;
  }
  
  // array filter to only get the top level nodes
  $nodes = array_filter($nodes, fn(
    $node
  ) => $node->indentation == 0);
  
  
  return $nodes;
  
}

#$ppl = preProcessLines(file_get_contents("parser.kek"));
if (!debug_backtrace()):
  $ppl = preProcessLines(
    "
###
  This is a beautiful print
  KEKEKEKEKEKEKE
###
print > add 1 2

###
  Class of A
  @see B
  Doc comments are used to generate documentation.
  We can also append to the doc comments via makros.
###
type A
  
  @log_each_access
  
  a int     @readonly
  b File    @weak
  
print \"lol\"
"
  );
  
  $astNodes = makeAstNodes(
    $ppl,
    $KEYWORDS
  );
  
  foreach ($astNodes as $item) {
    echo $item . "\n";
  }
  #print_r($astNodes);

endif;