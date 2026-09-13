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
            'title'   => 'Sidan finns inte',
            'message' => 'Sidan du försökte nå finns inte.',
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
            'title'   => 'Sessionen har gått ut',
            'message' => 'Formuläret kunde inte verifieras. Gå tillbaka och försök igen.',
        ]);
        exit;
    }

    $controller = new $controllerClass();
    $controller->$method();
} catch (Throwable $e) {
    Logger::error($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    View::render('error', [
        'title'   => 'Något gick fel',
        'message' => 'Det gick inte att läsa informationen just nu. Försök igen eller kontakta administratören.',
    ]);
}
