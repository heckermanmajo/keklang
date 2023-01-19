GameObject = class {
  name = "GameObject",
  fields = {

  }
}

Player = class {
  name = "Player",
  fields = {
    { "go", "GameObject" }
  }
}

var {
  p,
  new {
    Player,
    new {
      GameObject
    }
  }
}