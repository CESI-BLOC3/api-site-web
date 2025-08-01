<?php
namespace App\Core;

class Router
{
    private array $routes = []; // [METHOD => [[regex, vars, handler], ...]]

    public function get(string $pattern, $handler){ return $this->map('GET', $pattern, $handler); }
    public function post(string $pattern, $handler){ return $this->map('POST', $pattern, $handler); }
    public function put(string $pattern, $handler){ return $this->map('PUT', $pattern, $handler); }
    public function delete(string $pattern, $handler){ return $this->map('DELETE', $pattern, $handler); }
    public function options(string $pattern, $handler){ return $this->map('OPTIONS', $pattern, $handler); }

    public function map(string $method, string $pattern, $handler){
        [$regex, $vars] = $this->compile($pattern);
        $this->routes[strtoupper($method)][] = [$regex, $vars, $handler];
        return $this;
    }

    private function compile(string $pattern): array {
        // /api/events/{id} -> #^/api/events/(?P<id>[^/]+)$# 
        $vars = [];
        $regex = preg_replace_callback('#\{([^/]+)\}#', function($m) use (&$vars){
            $vars[] = $m[1];
            return '(?P<'.$m[1].'>[^/]+)';
        }, $pattern);
        return ['#^'.$regex.'$#', $vars];
    }

    public function dispatch(){
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // Support X-HTTP-Method-Override pour proxys / fetch qui “forcent” POST
        $override = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? $_SERVER['HTTP_X_METHOD_OVERRIDE'] ?? null;
        if ($override) $method = strtoupper($override);

        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        // Tente la méthode demandée
        $resp = $this->tryDispatch($method, $path);
        if ($resp !== false) return;

        // Si pas trouvé, tente GET comme fallback (utile quand on tape la racine)
        if ($method !== 'GET' && isset($this->routes['GET'])) {
            $resp = $this->tryDispatch('GET', $path);
            if ($resp !== false) return;
        }

        // 404
        http_response_code(404);
        echo 'Not Found';
    }

    private function tryDispatch(string $method, string $path){
        $method = strtoupper($method);
        if (empty($this->routes[$method])) return false;

        foreach ($this->routes[$method] as [$regex, $vars, $handler]) {
            if (preg_match($regex, $path, $m)) {
                $params = [];
                foreach ($vars as $v) { $params[$v] = $m[$v] ?? null; }
                return $this->invoke($handler, $params);
            }
        }
        return false;
    }

    private function invoke($handler, array $params){
        if (is_callable($handler)) return call_user_func($handler, $params);

        if (is_string($handler) && strpos($handler, '@') !== false) {
            [$class, $method] = explode('@', $handler, 2);
            if (!class_exists($class)) throw new \RuntimeException("Controller $class not found");
            $obj = new $class();
            if (!method_exists($obj, $method)) throw new \RuntimeException("Method $class::$method not found");
            return $obj->$method($params);
        }

        throw new \RuntimeException('Invalid route handler');
    }
}
