<?php

/**
 *
 * Aiden.
 *
 * Super Integrated IDE.
 *
 * -> Static analysis
 * -> AI recommendations
 * -> Code completion
 * -> Code Check
 * -> Test Generation
 * -> History
 * -> Code Visualization
 * -> Documentation
 *
 * All of that bloat free.
 *
 *
 */


?>
<html>
<head>
  <!-- w3-css -->
  <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
  <!-- jquery -->
  <script
    src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
  <style>
    button {
      margin-top: 0px !important;
      margin-bottom: 0px !important;
      padding-top: 0px !important;
      padding-bottom: 0px !important;
      line-height: normal !important;
      font: inherit !important;
    }
  </style>
</head>
<body>

<script>
  function nav(page){
    $(".page").hide();
    $("#"+page).show();
  }
</script>

<div class="w3-padding" style="margin-top: 1px !important;">
  <button onclick="nav('system-page')"> System View </button>
  <button onclick="nav('analyze-page')"> Analyze </button>
  <button onclick="nav('code-view')"> Code View </button>
  <button onclick="nav('plugins-page')"> Plugins </button>
  <button onclick="nav('settings-page')"> Settings </button>
  <button onclick="nav('team-page')"> Team </button>
  <button onclick="nav('wiki-page')"> Wiki </button>
</div>

<div id="wiki-page" class="page" style="display:none">
  Wiki-information about the application.
</div>

<div id="system-page" class="page" style="display: none">
  Layout the system, show dependencies.
  display semantic requests.
  Updated, if the code is updated.
  Also describe each system.
  -> Add more info for training the ai on topic related data
</div>

<div id="team-page" class="page" style="display: none">
  Rudimentäre team page for simple issues and coworking ...
</div>

<div id="settings-page" class="page" style="display: none">
settings
</div>

<div id="plugins-page" class="page" style="display: none">
plugins
</div>

<div id="analyze-page" class="page" style="display: none">
analyze
</div>

<div class="w3-row w3-padding page" id="code-view" style="display: none">

  <div class="w3-col m3 w3-card w3-padding">
    <input placeholder="Search" name="Search"> <br>
    <button>Order</button>
    <button>Current Usage</button>
    <!-- also put ai recommendations here based on the current open function -->
    <br>
    <button>region</button>
    <br>
    &nbsp;&nbsp;&nbsp;<button>module</button>
    <br>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <button>class</button>
    <br>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <button>method</button>
    <br>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <button>method</button>
    <br>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <button>method</button>
    <br>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <button>method</button>
    <br>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <button>class</button>
    <br>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <button>method</button>
    <br>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <button>method</button>
    <br>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <button>method ></button>
    <br>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <button>method ></button>
    <br>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    argument: Type
    <br>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    argument: Type
    <br>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    argument: Type
    <br>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    return
    <br>
  </div>

  <div class="w3-col m9 w3-padding">
    <div style="padding-bottom: 5px">
      <button>Tab 1</button>
      <button>Tab 2</button>
      <button>Tab 3</button>
      <button>Tab 4</button>
      <button>Tab 5</button>
    </div>
    <div class="w3-card w3-padding">
      <small>
        <input placeholder="Function Name">
      </small>
      <br>
      <button> Code Mode</button>
      <button> Usages</button>
      <button> History Mode</button>
      <button> Code Check</button>
      <button> Example Usage</button>
      <button> Unit tests</button>
      <textarea class="" style="width: 100%">
      The comment for the function.
    </textarea>
      <br><br>
      <input placeholder="AnnotationName">
      <input placeholder="AnnotationValue">
      <br>
      <input placeholder="AnnotationName">
      <input placeholder="AnnotationValue">
      <br>
      <input placeholder="AnnotationName">
      <input placeholder="AnnotationValue">
      <br>
      <br>
      <input placeholder="parameter">
      <input placeholder="Type">
      <input placeholder="Annotation">
      <br>
      <input placeholder="parameter">
      <input placeholder="Type">
      <input placeholder="Annotation">
      <br>
      <br>
      <small>Chunks or code: </small>
      <textarea style="width: 100%"></textarea>
      <br>
      <textarea style="width: 100%"></textarea>
      <br>
      <textarea style="width: 100%"></textarea>

      <br>
      <p>Code in one dump: </p>
      <textarea style="width: 100%" rows="10">
        Code in one textarea ...
      </textarea>
    </div>

    <br>

    <div class="w3-card w3-padding">
      <small>
        <input placeholder="Type name">
      </small>
      <button> Collapse</button>
      <button> Extend</button>
      <br>
      <textarea></textarea>
      <br>
      <div contenteditable="true">
        <span style="color:red">comptime</span>
        <span> &nbsp; </span>
      </div>

      <input placeholder="FieldName">
      <input placeholder="Type">
      <input placeholder="Annotation">
      <br>

      <div>
        Usages: all usages of the type
      </div>

    </div>
  </div>

</div>

</body>
</html>