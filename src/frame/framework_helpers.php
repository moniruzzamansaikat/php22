<?php

use Php22\Container;
use Php22\Db\Database;
use Php22\Http\Request;
use Php22\Utils\Flash;
use Php22\Utils\Session;

function request(): Request
{
    return new Request();
}

function csrf_token()
{
    if (!Session::has('_csrf_token')) {
        Session::set('_csrf_token', bin2hex(openssl_random_pseudo_bytes(10)));
    }

    $token = bin2hex(openssl_random_pseudo_bytes(10));

    Session::set('_csrf_token', $token);

    return $token;
}

function csrf_field()
{
    return '<input type="hidden" name="_token" value="' . csrf_token() . '" />';
}

function app(): \Php22\Application
{
    return Container::getInstance()->resolve('app');
}

function templateEngine() {
    return Container::getInstance()->resolve('templateEngine');
}

function router(): \Php22\Router
{
    return Container::getInstance()->resolve('router');
}

function db(): \Php22\Db\Database
{
    return Container::getInstance()->resolve('db');
}

/**
 * Helper to create a Flash instance.
 *
 * @return Flash
 */
function flash()
{
    return new Flash();
}

function hash_password(string $password)
{
    return password_hash($password, PASSWORD_DEFAULT);
}

function route($routeName, ...$params)
{
    return router()->route($routeName, ...$params);
}

/**
 * Resolve the base path dynamically based on the entry point.
 *
 * @param string $path Optional relative path to append.
 * @return string
 */
function base_path(string $path = ''): string
{
    $basePath = realpath($_SERVER['DOCUMENT_ROOT'] . '/../');
    return $path ? $basePath . DIRECTORY_SEPARATOR . $path : $basePath;
}

function debug(...$vars): void
{
    // Detect if running in CLI
    $isCli = php_sapi_name() === 'cli';

    // Capture backtrace to get the file and line number
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
    $file = $backtrace['file'] ?? 'Unknown file';
    $line = $backtrace['line'] ?? 'Unknown line';

    if ($isCli) {
        // CLI Output
        echo "\033[1;34mDebug called at \033[0m$file \033[1;34mon line \033[0m$line:\n\n";
        foreach ($vars as $var) {
            var_dump($var);
            echo "\n";
        }
    } else {
        // Include Font Awesome for icons
        echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-k5eM4iWy1/+HbZz+k6hL/4Gz8G3n9I+kL3FQdO4zJD3i0X0ZgB65/sS+Qc0ac0IOavWrtbkXo0bcMjd8Prlt0g==" crossorigin="anonymous" referrerpolicy="no-referrer" />';

        // HTML Output with enhanced styling
        echo '<style>
            pre.debug-output {
                background: #1e1e1e;
                color: #d4d4d4;
                padding: 15px;
                border-radius: 5px;
                font-size: 14px;
                font-family: "Source Code Pro", Menlo, Monaco, Consolas, "Courier New", monospace;
                line-height: 1.4;
                overflow: auto;
                white-space: pre-wrap;
                word-wrap: break-word;
            }
            .debug-header {
                color: #569cd6;
                margin-bottom: 10px;
                font-weight: bold;
            }
            .debug-variable {
                margin-bottom: 15px;
            }
            .debug-toggle {
                cursor: pointer;
                color: #9cdcfe;
                font-weight: bold;
                user-select: none;
            }
            .debug-content {
                display: none;
                margin-left: 20px;
                border-left: 2px solid #3e3e3e;
                padding-left: 10px;
            }
            .debug-code {
                color: #dcdcaa;
            }
            .debug-type {
                color: #4ec9b0;
                font-weight: bold;
            }
            .debug-key {
                color: #9cdcfe;
            }
            .debug-value {
                color: #ce9178;
            }
            .debug-separator {
                border-bottom: 1px solid #3e3e3e;
                margin: 15px 0;
            }
            .debug-icon {
                margin-right: 5px;
            }
            .debug-line {
                color: #858585;
                font-size: 12px;
                margin-bottom: 5px;
            }
            .debug-scroll {
                overflow: auto;
            }
        </style>';

        echo '<script>
            function toggleDebugContent(id) {
                var content = document.getElementById(id);
                var icon = document.getElementById(id + "-icon");
                if (content.style.display === "none") {
                    content.style.display = "block";
                    icon.classList.remove("fa-caret-right");
                    icon.classList.add("fa-caret-down");
                } else {
                    content.style.display = "none";
                    icon.classList.remove("fa-caret-down");
                    icon.classList.add("fa-caret-right");
                }
            }
        </script>';

        echo '<pre class="debug-output debug-scroll">';

        // Display file and line information
        echo '<div class="debug-header">Debug called at <span>' . htmlspecialchars($file) . '</span> on line <span>' . $line . '</span>:</div>';

        foreach ($vars as $index => $var) {
            $variableId = 'debugVar' . $index;
            echo '<div class="debug-variable">';
            echo '<div class="debug-toggle" onclick="toggleDebugContent(\'' . $variableId . '\')">';
            echo '<i id="' . $variableId . '-icon" class="fas fa-caret-right debug-icon"></i>';
            echo 'Variable ' . ($index + 1);
            echo '</div>';
            echo '<div id="' . $variableId . '" class="debug-content">';
            echo formatDebugOutput($var);
            echo '</div>';
            echo '</div>';
            if ($index < count($vars) - 1) {
                echo '<div class="debug-separator"></div>';
            }
        }

        echo '</pre>';
    }

    // Terminate script execution
    exit(1);
}

/**
 * Recursive function to format the debug output with syntax highlighting.
 */
function formatDebugOutput($var, $indent = 0): string
{
    $output = '';
    $indentation = str_repeat('    ', $indent);

    if (is_array($var)) {
        $output .= "<span class=\"debug-type\">Array</span> (\n";
        foreach ($var as $key => $value) {
            $output .= $indentation . "    [<span class=\"debug-key\">" . htmlspecialchars($key) . "</span>] => ";
            $output .= formatDebugOutput($value, $indent + 1);
        }
        $output .= $indentation . ")\n";
    } elseif (is_object($var)) {
        $className = get_class($var);
        $output .= "<span class=\"debug-type\">Object</span> (<span class=\"debug-key\">$className</span>) {\n";
        $vars = get_object_vars($var);
        foreach ($vars as $key => $value) {
            $output .= $indentation . "    -><span class=\"debug-key\">" . htmlspecialchars($key) . "</span> = ";
            $output .= formatDebugOutput($value, $indent + 1);
        }
        $output .= $indentation . "}\n";
    } elseif (is_string($var)) {
        $output .= "<span class=\"debug-value\">\"" . htmlspecialchars($var) . "\"</span>\n";
    } elseif (is_int($var) || is_float($var)) {
        $output .= "<span class=\"debug-value\">" . $var . "</span>\n";
    } elseif (is_bool($var)) {
        $output .= "<span class=\"debug-value\">" . ($var ? 'true' : 'false') . "</span>\n";
    } elseif (is_null($var)) {
        $output .= "<span class=\"debug-value\">null</span>\n";
    } else {
        $output .= "<span class=\"debug-value\">" . htmlspecialchars(print_r($var, true)) . "</span>\n";
    }

    return $output;
}

function debugPretty(...$vars): void
{
    // Detect if running in CLI
    $isCli = php_sapi_name() === 'cli';

    // Capture backtrace to get the file and line number
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
    $file = $backtrace['file'] ?? 'Unknown file';
    $line = $backtrace['line'] ?? 'Unknown line';

    if ($isCli) {
        // CLI Output
        echo "\033[1;34mDebugPretty called at \033[0m$file \033[1;34mon line \033[0m$line:\n\n";
        foreach ($vars as $var) {
            print_r($var);
            echo "\n";
        }
    } else {
        // Include Font Awesome for icons
        echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-k5eM4iWy1/+HbZz+k6hL/4Gz8G3n9I+kL3FQdO4zJD3i0X0ZgB65/sS+Qc0ac0IOavWrtbkXo0bcMjd8Prlt0g==" crossorigin="anonymous" referrerpolicy="no-referrer" />';

        // HTML Output with enhanced styling
        echo '<style>
            pre.debug-output {
                background: #ffffff;
                color: #333333;
                padding: 15px;
                border-radius: 5px;
                font-size: 14px;
                font-family: "Source Code Pro", Menlo, Monaco, Consolas, "Courier New", monospace;
                line-height: 1.4;
                overflow: auto;
                white-space: pre-wrap;
                word-wrap: break-word;
                border: 1px solid #ddd;
            }
            .debug-header {
                color: #0066cc;
                margin-bottom: 10px;
                font-weight: bold;
            }
            .debug-variable {
                margin-bottom: 15px;
            }
            .debug-toggle {
                cursor: pointer;
                color: #0066cc;
                font-weight: bold;
                user-select: none;
            }
            .debug-content {
                display: none;
                margin-left: 20px;
                border-left: 2px solid #eee;
                padding-left: 10px;
            }
            .debug-key {
                color: #a71d5d;
            }
            .debug-value {
                color: #008000;
            }
            .debug-separator {
                border-bottom: 1px solid #eee;
                margin: 15px 0;
            }
            .debug-icon {
                margin-right: 5px;
            }
            .debug-line {
                color: #999999;
                font-size: 12px;
                margin-bottom: 5px;
            }
            .debug-scroll {
                overflow: auto;
            }
        </style>';

        echo '<script>
            function toggleDebugContent(id) {
                var content = document.getElementById(id);
                var icon = document.getElementById(id + "-icon");
                if (content.style.display === "none") {
                    content.style.display = "block";
                    icon.classList.remove("fa-caret-right");
                    icon.classList.add("fa-caret-down");
                } else {
                    content.style.display = "none";
                    icon.classList.remove("fa-caret-down");
                    icon.classList.add("fa-caret-right");
                }
            }
        </script>';

        echo '<pre class="debug-output debug-scroll">';

        // Display file and line information
        echo '<div class="debug-header">DebugPretty called at <span>' . htmlspecialchars($file) . '</span> on line <span>' . $line . '</span>:</div>';

        foreach ($vars as $index => $var) {
            $variableId = 'debugVar' . $index;
            echo '<div class="debug-variable">';
            echo '<div class="debug-toggle" onclick="toggleDebugContent(\'' . $variableId . '\')">';
            echo '<i id="' . $variableId . '-icon" class="fas fa-caret-right debug-icon"></i>';
            echo 'Variable ' . ($index + 1);
            echo '</div>';
            echo '<div id="' . $variableId . '" class="debug-content">';
            echo formatPrettyOutput($var);
            echo '</div>';
            echo '</div>';
            if ($index < count($vars) - 1) {
                echo '<div class="debug-separator"></div>';
            }
        }

        echo '</pre>';
    }

    // Terminate script execution
    exit(1);
}

/**
 * Recursive function to format the debug output in a pretty way without type annotations.
 */
function formatPrettyOutput($var, $indent = 0): string
{
    $output = '';
    $indentation = str_repeat('    ', $indent);

    if (is_array($var)) {
        $output .= "{\n";
        foreach ($var as $key => $value) {
            $output .= $indentation . '    <span class="debug-key">' . htmlspecialchars($key) . '</span>: ';
            $output .= formatPrettyOutput($value, $indent + 1);
        }
        $output .= $indentation . "}\n";
    } elseif (is_object($var)) {
        $vars = get_object_vars($var);
        $output .= "{\n";
        foreach ($vars as $key => $value) {
            $output .= $indentation . '    <span class="debug-key">' . htmlspecialchars($key) . '</span>: ';
            $output .= formatPrettyOutput($value, $indent + 1);
        }
        $output .= $indentation . "}\n";
    } elseif (is_string($var)) {
        $output .= '<span class="debug-value">' . htmlspecialchars($var) . '</span>' . "\n";
    } elseif (is_int($var) || is_float($var)) {
        $output .= '<span class="debug-value">' . $var . '</span>' . "\n";
    } elseif (is_bool($var)) {
        $output .= '<span class="debug-value">' . ($var ? 'true' : 'false') . '</span>' . "\n";
    } elseif (is_null($var)) {
        $output .= '<span class="debug-value">null</span>' . "\n";
    } else {
        $output .= '<span class="debug-value">' . htmlspecialchars(print_r($var, true)) . '</span>' . "\n";
    }

    return $output;
}
