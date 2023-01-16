<?php




Interpreter::$functions["dumpenv"] = function (
  array $args,
  array &$env
) {
  $dumpArray = null;
  $dumpArray = function (array $arr, int $depth, $dump): string{
    $ret = "";
    foreach ($arr as $key => $value){
      $ret .= str_repeat("  ", $depth) . $key . ": ";
      if (is_array($value)){
        $ret .= $dump($value, $depth + 1);
      }else{
        $ret .= $value . "\n";
      }
    }
    return $ret;
  };
  foreach ($env as $k => $v) {
    if (is_array($v)) {
      echo $k . ":\n";
      echo $dumpArray($v, 1, $dumpArray);
    } else {
      echo $k . ": " . $v . "\n";
    }
  }
  return null;
};