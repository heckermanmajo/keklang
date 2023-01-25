<?php

namespace kek;

class AstNode {

  public string $word;
  public string $line_number;
  public string $indentation;
  /** @var array<AstNode> */
  public array $children = array();
  public string $doc_comment = '';
  public string $type = '';
  public string $creator = '';

  public function __construct() {
  }

  function __toString(): string {
    $indent = str_repeat(" ", $this->indentation);
    $comment = $this->doc_comment;
    if ($comment != "") {
      $comment = "###\n" . $comment . "###\n";
    }
    $str = $comment . $indent . $this->word . " [$this->line_number, $this->indentation, $this->type]";
    foreach ($this->children as $child) {
      $str .= "\n" . $child;
    }
    return $str;
  }
}


class Parser {
  /**
   * @return array<AstNode>
   */
  static function makeAstNodes(
    array $preProcessedLines
  ): array {
    $nodes = array();

    // add node
    $newNode = function (
      string $type,
      string $word,
      int|string $line_number,
      int|string $indentation
    ) use (
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
    #print "\n";
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
        $node = $newNode("string", $value, $line_number, $indentation);
        goto collect_children;
      }

      if (is_numeric($raw_string)) {
        if (str_contains($raw_string, ".")) {
          $node = $newNode("float", $raw_string, $line_number, $indentation);
        } else {
          $node = $newNode("int", $raw_string, $line_number, $indentation);
        }
        goto collect_children;
      }

      if ($raw_string == "true" or $raw_string == "false") {
        $node = $newNode("boolean", $raw_string, $line_number, $indentation);
        goto collect_children;
      }

      if (str_ends_with($raw_string, ":")) {
        $node = $newNode("named_param", $raw_string, $line_number, $indentation);
        goto collect_children;
      }

      $node = $newNode("name", $raw_string, $line_number, $indentation);

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

  /**
   * @param string $code
   * @return array<string>
   */
  static function preProcessLines(
    string $code
  ): array {

    $string_literal_map = array();
    $string_literal_counter = 0;

    // replace the string between the quotes with a placeholder
    $newcode = "";
    $in_literal = false;
    foreach (str_split($code) as $key => $c) {
      if ($c == '"' and $code[$key - 1] != "\\") {
        $in_literal = !$in_literal;
        if ($in_literal) {
          $string_literal_counter++;
          $string_literal_map[$string_literal_counter] = "";
          $newcode .= "(($string_literal_counter))";
        } else {
          if ($c != '"') {
            $newcode .= $c;
          }
        }
      } else if ($in_literal) {
        $string_literal_map[$string_literal_counter] .= $c;
      } else {
        $newcode .= $c;
      }
    }

    $_lines = explode("\n", $newcode);
    $lines = array();
    foreach ($_lines as $line) {
      if (!str_starts_with(trim($line), "#")) {
        $line = explode(" # ", $line)[0];
      }
      $lines[] = $line;
    }

    $in_multiline_comment = false;
    $new_lines = array();
    $comment_line = '';
    foreach ($lines as $line_number => $line) {
      # Remove single line comments and empty lines
      $current_indentation = strlen($line) - strlen(ltrim($line));
      $line_number = $line_number + 1;

      if (str_ends_with(trim($line), "###") and $in_multiline_comment) {
        $in_multiline_comment = false;
        $new_lines[] = "#" . $comment_line . "[$line_number, $current_indentation]";
        continue;
      }

      if (str_starts_with(trim($line), "###") and !$in_multiline_comment) {
        $in_multiline_comment = true;
        $comment_line = '#'; # '#' at the start marks a doc comment line
        continue;
      }

      if ($in_multiline_comment) {
        $comment_line .= "#" . $line;
        continue;
      }

      if (str_starts_with(trim($line), "#") or trim($line) == "") {
        continue;
      }

      if (str_contains($line, " -> ") or str_contains($line, " | ") or str_contains($line, " || ") or str_contains($line, " ||| ")) {
        #print ("replace > with newline and indent");
        # replace > >> >>> >>>> >>>>> with corresponding indentation and a newline
        $indent = str_repeat(" ", $current_indentation);
        $line = str_replace(" -> ", "\n$indent", $line);
        $line = str_replace(" | ", "\n$indent" . "  ", $line);
        $line = str_replace(" || ", "\n$indent" . "    ", $line);
        $line = str_replace(" ||| ", "\n$indent" . "      ", $line);
        assert(!str_contains($line, " |||| "), "'||||' and longer is not supported, use linebreaks instead");
        $_lines = explode("\n", $line);
        foreach ($_lines as $l) {
          $new_indentation = strlen($l) - strlen(ltrim($l));
          $new_lines[] = $l . " [$line_number, $new_indentation]";
        }
        continue;
      }

      $new_lines[] = explode("#", $line)[0] . " [$line_number, $current_indentation]";
    }

    # if multiple words are in the same line, split them into multiple lines
    $lines = array();
    foreach ($new_lines as $line) {
      $words = explode(
        " ",
        trim(
        # the line number and indentation are in brackets
          explode("[", $line)[0]
        )
      );
      # todo: not super efficient
      $line_number = (int)json_decode("[" . explode("[", $line)[1])[0];
      $indentation = (int)json_decode("[" . explode("[", $line)[1])[1];

      # str_starts_with("#", $line) -> this is a doc comment
      if (count($words) > 1 and !str_starts_with(trim($line), "#")) {
        # the first word stays with the same indentation
        # all other words are indented by 2 spaces
        $indent = str_repeat(" ", $indentation); #  only for visualizing the indentation
        $lines[] = $indent . $words[0] . " [$line_number, $indentation]";
        $indent .= "  "; # only for visualizing the indentation (same for all children)
        for ($i = 1; $i < count($words); $i++) {
          if ($words[$i] == "") continue;
          $lines[] = $indent . $words[$i] . " [$line_number, " . ($indentation + 2) . "]";
        }
      } else {
        $lines[] = $line;
      }
    }

    // expand the type expressions into multiple lines
    $after_type_expansion = array();
    foreach ($lines as $line) {
      $word =
        trim(
        # the line number and indentation are in brackets
          explode("[", $line)[0]
        );
      # todo: not super efficient
      $line_number = (int)json_decode("[" . explode("[", $line)[1])[0];
      $indentation = (int)json_decode("[" . explode("[", $line)[1])[1];

      if (ctype_upper($word[0]) or str_starts_with($word, "?")){
        $indent = str_repeat(" ", $indentation);
        $line = str_replace("??", "Result\n  ", $line);
        $line = str_replace("?", "Option\n  ", $line);
      }
      $after_type_expansion[] = $line;
    }

    // replace the placeholders with the original string
    foreach ($lines as $i => $line) {
      foreach ($string_literal_map as $key => $value) {
        if (str_contains($line, "(($key))")) {
          $lines[$i] = str_replace("(($key))", "(($value))", $line);
          #remove the placeholder from the string literal map
          unset($string_literal_map[$key]);
        }
      }
    }

    return $lines;
  }
}