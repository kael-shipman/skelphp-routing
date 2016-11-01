<?php
namespace Skel;

class Router implements Interfaces\Router {
  protected $routes = array();
  protected $activeRoute = null;

  public function addRoute(Interfaces\Route $route) {
    $name = $route->getName();
    if ($name && isset($this->routes[$name])) throw new \RuntimeException('A route named "'.$name.' is already registered!');
    if ($name === null) {
      $name = count($this->routes);
      while (isset($this->routes[$name])) $name++;
      $route->setName($name);
    }
    $this->routes[$name] = $route;
    return $this;
  }

  public function routeRequest(Interfaces\Request $r) {
    foreach($this->routes as $name => $route) {
      $vars = $route->match($r);
      if (is_array($vars)) {
        $this->activeRoute = $route;
        return $route->execute($vars);
      }
    }

    // If we made it here, we didn't match any routes. Throw a 404
    return null;
  }

  public function getActiveRoute() { return $this->activeRoute; }

  public function getRouteByName(string $name) {
    if (isset($this->routes[$name])) return $this->routes[$name];
    else return null;
  }

  public function getPath($name, $vars=array()) {
    if (!($route = $this->getRouteByName($name))) throw new \RuntimeException('There is no route named `'.$name.'` registered!');
    return $route->getPath($vars);
  }
}

?>
