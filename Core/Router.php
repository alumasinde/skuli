<?php

declare(strict_types=1);

namespace Core;

use InvalidArgumentException;

final class Router
{
    private array $routes = [];
    private array $groupMiddleware = [];
    private string $groupPrefix = '';

    public function get(
        string $path,
        callable|array|string $handler,
        array $middleware = []
    ): void {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(
        string $path,
        callable|array|string $handler,
        array $middleware = []
    ): void {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    public function put(
        string $path,
        callable|array|string $handler,
        array $middleware = []
    ): void {
        $this->addRoute('PUT', $path, $handler, $middleware);
    }

    public function delete(
        string $path,
        callable|array|string $handler,
        array $middleware = []
    ): void {
        $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    public function group(
        string $prefix,
        array $middleware,
        callable $callback
    ): void {
        $previousPrefix = $this->groupPrefix;
        $previousMiddleware = $this->groupMiddleware;

        $this->groupPrefix = rtrim($previousPrefix . $prefix, '/');
        $this->groupMiddleware = array_merge(
            $previousMiddleware,
            $middleware
        );

        $callback($this);

        $this->groupPrefix = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;
    }

    public function dispatch(
        string $method,
        string $uri,
        object $container
    ): void {
        $path = rtrim(parse_url($uri, PHP_URL_PATH) ?: '/', '/');

        if ($path === '') {
            $path = '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== strtoupper($method)) {
                continue;
            }

            $params = $this->matchPath(
                $route['path'],
                $path
            );

            if ($params === null) {
                continue;
            }

            $context = [
                'params' => $params,
            ];

            $this->runMiddleware(
                $route['middleware'],
                $context,
                function () use (
                    $route,
                    $params,
                    $container
                ): void {
                    $this->invoke(
                        $route['handler'],
                        $params,
                        $container
                    );
                }
            );

            return;
        }

        $this->notFound();
    }

    private function addRoute(
        string $method,
        string $path,
        callable|array|string $handler,
        array $middleware
    ): void {
        $fullPath = rtrim(
            $this->groupPrefix . $path,
            '/'
        );

        if ($fullPath === '') {
            $fullPath = '/';
        }

        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $fullPath,
            'handler' => $handler,
            'middleware' => array_merge(
                $this->groupMiddleware,
                $middleware
            ),
        ];
    }

    private function matchPath(
        string $routePath,
        string $requestPath
    ): ?array {
        $routeParts = explode(
            '/',
            trim($routePath, '/')
        );

        $requestParts = explode(
            '/',
            trim($requestPath, '/')
        );

        if (count($routeParts) !== count($requestParts)) {
            return null;
        }

        $params = [];

        foreach ($routeParts as $index => $part) {
            if (
                str_starts_with($part, '{') &&
                str_ends_with($part, '}')
            ) {
                $params[
                    trim($part, '{}')
                ] = $requestParts[$index];

                continue;
            }

            if ($part !== $requestParts[$index]) {
                return null;
            }
        }

        return $params;
    }

    private function runMiddleware(
        array $middleware,
        array $context,
        callable $final
    ): void {
        if ($middleware === []) {
            $final();
            return;
        }

        $current = array_shift($middleware);

        $instance = is_string($current)
            ? new $current()
            : $current;

        if (!method_exists($instance, 'handle')) {
            throw new InvalidArgumentException(
                'Middleware must implement handle()'
            );
        }

        $instance->handle(
            $context,
            function () use (
                $middleware,
                $context,
                $final
            ): void {
                $this->runMiddleware(
                    $middleware,
                    $context,
                    $final
                );
            }
        );
    }

    private function invoke(
        callable|array|string $handler,
        array $params,
        object $container
    ): void {
        /**
         * Closure
         */
        if (
            is_callable($handler) &&
            !is_array($handler) &&
            !is_string($handler)
        ) {
            $handler($params);
            return;
        }

        /**
         * [Controller::class, 'method']
         */
        if (is_array($handler)) {
            if (count($handler) !== 2) {
                throw new InvalidArgumentException(
                    'Route handler array must contain [class, method]'
                );
            }

            [$class, $method] = $handler;

            $controller = $container->get($class);

            if (!method_exists($controller, $method)) {
                throw new InvalidArgumentException(
                    "Controller method {$method} not found"
                );
            }

            $controller->{$method}($params);

            return;
        }

        /**
         * Controller::class . '@method'
         */
        if (is_string($handler)) {
            if (!str_contains($handler, '@')) {
                throw new InvalidArgumentException(
                    'String handler must use Class@method format'
                );
            }

            [$class, $method] = explode(
                '@',
                $handler,
                2
            );

            $controller = $container->get($class);

            if (!method_exists($controller, $method)) {
                throw new InvalidArgumentException(
                    "Controller method {$method} not found"
                );
            }

            $controller->{$method}($params);

            return;
        }

        throw new InvalidArgumentException(
            'Invalid route handler'
        );
    }

    private function notFound(): void
    {
        http_response_code(404);

        header(
            'Content-Type: application/json'
        );

        echo json_encode([
            'success' => false,
            'error' => 'Route not found',
            'data' => null,
        ]);
    }
}