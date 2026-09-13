<?php
/**
 * HemFresh TimeRapport — front controller.
 * All requests go through here: index.php?page=<route>
 */
define('TIMERAPPORT', 1);

require __DIR__ . '/app/bootstrap.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Logger;
use App\Core\View;

$routes = require __DIR__ . '/app/routes.php';

$page = isset($_GET['page']) ? (string)$_GET['page'] : 'dashboard';

try {
    if (!isset($routes[$page])) {
        http_response_code(404);
        View::render('error', [
            'title'   => t('err.404_title'),
            'message' => t('err.404_msg'),
        ]);
        exit;
    }

    [$controllerClass, $method, $requiresAuth, $postOnly] = $routes[$page];

    if ($postOnly && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . View::url('dashboard'));
        exit;
    }

    if ($requiresAuth && !Auth::check()) {
        header('Location: ' . View::url('login'));
        exit;
    }

    // Central CSRF check for every POST request.
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !Csrf::validate($_POST['_token'] ?? null)) {
        http_response_code(403);
        View::render('error', [
            'title'   => t('err.csrf_title'),
            'message' => t('err.csrf_msg'),
        ]);
        exit;
    }

    $controller = new $controllerClass();
    $controller->$method();
} catch (Throwable $e) {
    Logger::error($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    View::render('error', [
        'title'   => t('err.generic_title'),
        'message' => t('err.generic_msg'),
    ]);
}
