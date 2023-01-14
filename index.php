<?php


?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Keklang</title>
  <!-- get jquery -->
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <!-- get w3-css -->
  <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
  <script>
  /*
    $.ajax({
      url: 'Interpreter.php',
      type: 'POST',
      data: {
        'code': $('#code').text(),
        'action': 'run'
      },
      success: function(data) {
        console.log(data);
        const d = JSON.parse(data);
        $('#output').text(JSON.stringify(d.result));
        $('#non_comptime_nodes').text(JSON.stringify(d.non_comptime_nodes));
        $('#records').text(JSON.stringify(d.records));
        $('#functions').text(JSON.stringify(d.functions));
      }
    });
    */
    
  </script>
  <style>
    .keyword {
      color: #84d2cf;
    }
    body{
      background-color: #1e1e1e;
      color: #d4d4d4;
    }
  </style>
</head>
<body>
<!--
<h3>
  Kek lang introspection.
</h3>

<pre>
  The keklang ide, should be the tool to edit the code
  and to inspect various things about the code.
</pre>


-> Make it also work like jupiter notebooks
-> Execute a block and then check the code for errors and data
-> Also you can implement expections

-> The generated debug c can also create a file log at runtime, that you can dissect
   with the ide...


-->
<div>
  
  <nav>
    <button> Help </button>
    <button> File View </button>
    <button> Dependency View </button>
    <button> Lib View -> Search libs </button>
    <button> File View </button>
  </nav>
  
  <div class="w3-padding">
  <input placeholder="Search Word">
  <button> In File </button>
  <button> In Project </button>
  <button> In Libs </button>
  </div>
  
  <div class="w3-row">
    
    <div class="w3-col m10 l10">
      <button> File1</button>
      <button> File2</button>
      <button> File3</button>
      <button> File4</button>
      <button> File5</button>
      <button> File6</button>
      <hr>
      <pre id="code" contenteditable="true" style="color:white; font-size: 130%">
        comptime
        <span class="keyword">print</span> "Hello world!"
      </pre>
      <button onclick=""> Compile a Test </button>
      <pre id="output"></pre>
      <pre id="non_comptime_nodes"></pre>
      <pre id="records"></pre>
      <pre id="functions"></pre>
      
      <hr>
      
      <pre>
        
        -> get function by name
        -> get variable by name
        -> get nodey by name
        
        -> display node tree
        
        -> Run the s-programm until here and check the output.
        
        -> Get overview of all allocations
        
      </pre>
      
      
    </div>
  
  
  <div class="w3-col l2 m2">
    <pre style="color:white" contenteditable="true">
    > Console for interaction
    
    asdva
    sdfadf
    dfadsfads
    </pre>
    <button> Execute >>> </button>
  </div>
  </div>
</div>
</body>
</html>
