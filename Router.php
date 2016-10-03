<?php
namespace Skel;

abstract class Router implements Interfaces\Router {
  protected $routes;

  public function addRoute(string $pattern, string $appCallback, string $name=null, string $method=null) {
    if ($name != null && isset($this->routes[$name])) throw new \RuntimeException('You\'ve already registered a route with this name!');
    if ($name == null) $name = count($this->routes);
    $this->routes[$name] = array('pattern' => $pattern, 'callback' => $appCallback, 'method' => $method);
    return $this;
  }

  public function match($route, Interfaces\Request $r) {
    // Poor-man's type-check
    if (
      !is_array($route) ||
      !isset($route['pattern']) ||
      !isset($route['callback']) ||
      !isset($route['method'])
    ) throw new \RuntimeException('`$route` must be an array containing `pattern`, `callback` and `method` indices');


    $uri = $r->getUri();
    $path = $uri->getPath();

    // Normalize paths
    $path = trim($path, '/');
    $pattern = trim($route['pattern'], '/');

    // Break into parts
    $path_parts = explode('/',$path);
    $pattern_parts = explode('/', $pattern);

    $vars = array();

    // Check method match
    if ($route['method'] != null && $route['method'] != $r->getMethod()) return false;

    // Now check pattern against path
    for($i = 0; $i < count($pattern_parts); $i++) {

      $matches = array();

      // If we find a star, then we add all the remaining path parts to the $vars array
      if ($pattern_parts[$i] == '*') {
        for ($i; $i < count($path_parts); $i++) $vars[] = $path_parts[$i];
        return $vars;
      }

      // Else if we've run out of path variables, it's not a match
      elseif (!isset($path_parts[$i])) return false;

      // Else if we find a variable, add it
      elseif (preg_match('/^\{([^}]+)\}$/', $pattern_parts[$i], $matches)) {
        $vars[$matches[1]] = $path_parts[$i];
      }

      // Else if the pattern is a literal, make sure it matches
      elseif ($pattern_parts[$i] != $path_parts[$i]) return false;
    }

    // If we've got path variables left over (and there wasn't a star), it's not a match
    if (isset($path_parts[$i])) return false;

    return $vars;
  }

  public function routeRequest(Interfaces\Request $r, Interfaces\App $app) {
    foreach($this->routes as $route) {
      // If we get a possibly empty array of variables back, it's a match
      $vars = $this->match($route, $r);
      if (is_array($vars)) {
        $callback = $route['callback'];
        return $app->$callback($vars);
      }
    }

    // If we made it here, we didn't match any routes. Throw a 404
    $app->generateError(null, 404);
  }

  public function getPath($name, $vars) {
    if (!($route = $this->routes[$name])) throw new \RuntimeException('There is no route named `'.$name.'` registered!');

    $pattern_parts = explode('/', trim($route['pattern'], '/'));

    $path = '';

    for ($i = 0; $i < count($pattern_parts); $i++) {
      $matches = array();
      // If it's a star, fill in the rest with $vars
      if ($pattern_parts[$i] == '*') {
        if (count($vars) == 0) return $path;
        else return $path .= '/'.implode('/',$vars);
      }

      // Else if it's a named variable, replace from $vars
      elseif (preg_match('/^\{([^}]+)\}$/', $pattern_parts[$i], $matches)) {
        if (!isset($vars[$matches[1]])) throw new \RuntimeException('No variable provided called `'.$matches[1].'` for path `'.$name.'`. Please add the index `'.$matches[1].'` to the array you pass into getPath(). For example, `getPath(\''.$name.'\', array(\''.$matches[1].'\' => \'value\', ....)`');
        $path .= '/'.$vars[$matches[1]];
        unset($vars[$matches[1]]);
      }

      // Else if it's a literal, just tack it on
      else $path .= '/'.$pattern_parts[$i];
    }

    return $path;
  }
}

?>
