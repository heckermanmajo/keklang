<?php

class AstNode {
  
  public string $word;
  public string $line_number;
  public string $indentation;
  /** @var array AstNode */
  public array $annotations = array();
  /** @var array<AstNode> */
  public array $children = array();
  public string $doc_comment = '';
  public string $type = '';
  public string $creator = '';
  
  public function __construct() { }
  
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


