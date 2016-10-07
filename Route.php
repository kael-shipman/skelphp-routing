<?php
namespace Skel;

class Route implements Interfaces\Route {
  protected $pattern;
  protected $handler;
  protected $callback;
  protected $method;

  public function __construct(string $pattern, $handler, string $callback, string $method=null) {
    $this->pattern = $pattern;
    $this->handler = $handler;
    $this->callback = $callback;
    $this->method = $method;
  }

  public function execute($vars = array()) {
    $callback = $this->callback;
    return $this->handler->$callback($vars);
  }

  public function getPath(array $vars) {
    $pattern_parts = explode('/', trim($this->pattern, '/'));

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
        if (!isset($vars[$matches[1]])) throw new \RuntimeException('No variable provided called `'.$matches[1].'` for this path. Please add the index `'.$matches[1].'` to the array you pass into getPath(). For example, `getPath(array(\''.$matches[1].'\' => \'value\', ....)`');
        $path .= '/'.$vars[$matches[1]];
        unset($vars[$matches[1]]);
      }

      // Else if it's a literal, just tack it on
      else $path .= '/'.$pattern_parts[$i];
    }

    return $path;
  }

  public function match(Interfaces\Request $r) {
    $uri = $r->getUri();
    $path = $uri->getPath();

    // Normalize paths
    $path = trim($path, '/');
    $pattern = trim($this->pattern, '/');

    // Break into parts
    $path_parts = explode('/',$path);
    $pattern_parts = explode('/', $pattern);

    $vars = array();

    // Check method match
    if ($this->method != null && $this->method != $r->getMethod()) return false;

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
}
