<?php

namespace Php22;

class TemplateEngine
{
    private $viewPath;
    private $cachePath;

    public function __construct($viewPath, $cachePath)
    {
        $this->viewPath .= rtrim($viewPath, '/');
        $this->cachePath = rtrim($cachePath, '/');
    }

    /**
     * Render a view by compiling it (if necessary) and injecting data
     *
     * @param string $view The view file name (without extension)
     * @param array $data The data to inject into the view
     * @return string The rendered HTML output
     */
    public function render($view, $data = [])
    {
        $viewFile = "{$this->viewPath}/{$view}.moni";
        $cachedFile = "{$this->cachePath}/{$view}.php";

        if (!file_exists($viewFile)) {
            throw new \Exception("View file '{$view}.moni' not found.");
        }

        // deleting the cached file
        if(file_exists($cachedFile)) unlink($cachedFile);

        if (!file_exists($cachedFile) || filemtime($viewFile) > filemtime($cachedFile)) {
            $this->compile($viewFile, $cachedFile);
        }

        extract($data, EXTR_SKIP);

        ob_start();
        include $cachedFile;
        return ob_get_clean();
    }

    /**
     * Compile a .moni file into a PHP file
     *
     * @param string $viewFile Path to the .moni view file
     * @param string $cachedFile Path to the compiled PHP file
     */
    private function compile($viewFile, $cachedFile)
    {
        // Load the view file content
        $content = file_get_contents($viewFile);

        // DEBUG: Log raw template content (optional)
        // file_put_contents(__DIR__ . '/../debug/raw_template.log', $content);

        // 1. Replace {{ variable }} syntax
        $content = preg_replace_callback('/{{\s*(.+?)\s*}}/', function ($matches) {
            $variable = trim($matches[1]);
            return "<?php echo htmlspecialchars({$variable}, ENT_QUOTES, 'UTF-8'); ?>";
        }, $content);

        // 2. Replace #if/#elseif/#else/#endif for conditionals
        $content = preg_replace_callback('/#if\s*\(([^()]*+(?:\((?1)\)[^()]*+)*?)\)/', function ($matches) {
            $condition = trim($matches[1]);
        
            return "<?php if ({$condition}): ?>";
        }, $content);
        
        $content = preg_replace_callback('/#elseif\s*\((.*?)\)/', function ($matches) {
            $condition = trim($matches[1]);
            var_dump($condition);
            return "<?php elseif ({$condition}): ?>";
        }, $content);

        $content = preg_replace('/#else/', '<?php else: ?>', $content);
        $content = preg_replace('/#endif/', '<?php endif; ?>', $content);

        // 3. Replace #foreach/#endforeach for loops
        $content = preg_replace_callback('/#foreach\s*\((.+?)\)/', function ($matches) {
            $loop = trim($matches[1]);
            return "<?php foreach ({$loop}): ?>";
        }, $content);

        $content = preg_replace('/#endforeach/', '<?php endforeach; ?>', $content);

        // Ensure the cache directory exists
        $cachedDir = dirname($cachedFile);
        if (!is_dir($cachedDir)) {
            mkdir($cachedDir, 0777, true); // Create directories recursively
        }

        // DEBUG: Log the final compiled content (optional)
        // file_put_contents(__DIR__ . '/../debug/compiled_template.log', $content);

        // Save the compiled PHP content to the cached file
        file_put_contents($cachedFile, $content);
    }
}
