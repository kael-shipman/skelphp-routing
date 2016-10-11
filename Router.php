<?php
namespace Skel;

abstract class Router implements Interfaces\Router {
  protected $routes = array();

  public function addRoute(Interfaces\Route $route, $name=null) {
    if (isset($this->routes[$name])) throw new \RuntimeException('A route named "'.$name.' is already registered!');
    if ($name === null) $this->routes[] = $route;
    else $this->routes[$name] = $route;
    return $this;
  }

  public function routeRequest(Interfaces\Request $r) {
    foreach($this->routes as $name => $route) {
      $vars = $route->match($r);
      if (is_array($vars)) return $route->execute($vars);
    }

    // If we made it here, we didn't match any routes. Throw a 404
    return null;
  }

  public function getRouteByName(string $name) {
    if (isset($this->routes[$name])) return $this->routes[$name];
    else return null;
  }

  public function getPath($name, $vars) {
    if (!($route = $this->getRouteByName($name))) throw new \RuntimeException('There is no route named `'.$name.'` registered!');
    return $route->getPath($vars);
  }
}

?>
