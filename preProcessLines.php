<?php

include_once "keywords.php";

/**
 * @param string $code
 * @return array<string>
 */
function preProcessLines(
  string $code
): array {
  
  $string_literal_map = array();
  $string_literal_counter = 0;
  
  $lines = explode("\n", $code);
  $string_escaped_lines = array();
  foreach ($lines as $l) {
    if (str_contains($l, '"')) {
      // replace the string between the quotes with a placeholder
      $newline = "";
      $in_literal = false;
      foreach (str_split($l) as $c) {
        if ($c == '"') {
          $in_literal = !$in_literal;
          if ($in_literal) {
            $string_literal_counter++;
            $string_literal_map[$string_literal_counter] = "";
            $newline .= "(($string_literal_counter))";
          } else {
            if ($c != '"') {
              $newline .= $c;
            }
          }
        } else if ($in_literal) {
          $string_literal_map[$string_literal_counter] .= $c;
        } else {
          $newline .= $c;
        }
      }
      $string_escaped_lines[] = $newline;
    } else {
      $string_escaped_lines[] = $l;
    }
  }
  $lines = $string_escaped_lines;
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
    
    if (str_contains($line, " > ")) {
      #print ("replace > with newline and indent");
      # replace > >> >>> >>>> >>>>> with corresponding indentation and a newline
      $indent = str_repeat(" ", $current_indentation);
      $line = str_replace(" > ", "\n$indent" . "  ", $line);
      $line = str_replace(" >> ", "\n$indent" . "    ", $line);
      $line = str_replace(" >>> ", "\n$indent" . "      ", $line);
      assert(!str_contains($line, " >>>> "), "'>>>' and longer is not supported, use linebreaks instead");
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