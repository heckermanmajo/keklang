Debug mode

-> We can compile in debug mode, which will compile 
  to c code where everything is checked.
  The code will be slower, but each allocation is logged, each access
  is checked against, the type definition, etc.
  All stuff is asserted, but the logic itself stays the same.
  Each check changes nothing if the code is correct, otherwise 
  the application will crash: also logs are created until the crash point.