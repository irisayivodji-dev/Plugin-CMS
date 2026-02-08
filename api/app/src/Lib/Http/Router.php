<?php

namespace App\Lib\Http;

use App\Lib\Controllers\AbstractController;


class Router {

    const  CONTROLLER_NAMESPACE_PREFIX = "App\\Controllers\\";
    const  ROUTE_CONFIG_PATH = __DIR__ . '/../../../config/routes.json';
    

    public static function route(Request $request): Response {
        $config = self::getConfig();

        foreach($config as $route) {
            if(self::checkMethod($request, $route) === false || self::checkUri($request, $route) === false) {
                continue;
            }

            $controller = self::getControllerInstance($route['controller']);
            return $controller->process($request);
        }

        throw new \Exception('Route not found', 404);
    }
    
    private static function getConfig(): array {
        $routesConfigContent = file_get_contents(self::ROUTE_CONFIG_PATH);
        $routesConfig = json_decode($routesConfigContent, true);

        return $routesConfig;
    }


    private static function checkMethod(Request $request, array $route): bool {
        $requestMethod = $request->getMethod();
        
        // Support pour les méthodes HTTP simulées via _method dans les formulaires POST
        if ($requestMethod === 'POST') {
            $postData = $request->getPost();
            if (isset($postData['_method'])) {
                $requestMethod = strtoupper($postData['_method']);
            }
        }
        
        return $requestMethod === $route['method'];
    }

    private static function checkUri(Request $request, array $route): bool {
        $requestUriParts = self::getUrlParts($request->getPath());
        $routePathParts = self::getUrlParts($route['path']);

        if(self::checkUrlPartsNumberMatches($requestUriParts, $routePathParts) === false) {
            return false;
        }

        foreach($routePathParts as $key => $part) {
            if(self::isUrlPartSlug($part) === false) {
                if($part !== $requestUriParts[$key]) {
                    return false;
                }
            }else{
                $request->addSlug(substr($part, 1), $requestUriParts[$key]);
            }
        }

        return true;
    }
    
    private static function getControllerInstance(string $controller): AbstractController {
        $controllerClass = self::CONTROLLER_NAMESPACE_PREFIX . $controller;

        if(class_exists($controllerClass) === false) {
            throw new \Exception('Route not found', 404);
        }

        $controllerInstance = new $controllerClass();

        if(is_subclass_of($controllerInstance, AbstractController::class)=== false){
            throw new \Exception('Route not found', 404);
        }
        
        return $controllerInstance;
    }

    private static function getUrlParts(string $url): array {
        return explode('/', trim($url, '/'));
    }

    private static function checkUrlPartsNumberMatches(array $requestUriParts, array $routePathParts): bool {
        return count($requestUriParts) === count($routePathParts);
    }

    private static function isUrlPartSlug(string $part): bool {
        return strpos($part, ':') === 0;
    }

}
