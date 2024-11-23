<?php

if ($argc < 2) {
    die("Usage: php make-controller.php <ControllerName>\n");
}

$controllerName = $argv[1];
$controllerFile = __DIR__ . "/../src/controllers/{$controllerName}.php";

if (file_exists($controllerFile)) {
    die("Controller already exists: $controllerFile\n");
}

$template = <<<PHP
<?php

namespace Php22\Controllers;

class {$controllerName} extends BaseController
{
    public function index()
    {
        echo "This is the {$controllerName} index method.";
    }
}
PHP;

file_put_contents($controllerFile, $template);

echo "Controller created: $controllerFile\n";
