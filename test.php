<?php

include "AstNode.php";
include "preProcessLines.php";

foreach (preProcessLines(file_get_contents("parser.kek")) as $node) {
  echo $node . "\n";
}