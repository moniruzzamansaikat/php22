<?php

namespace Php22;

class TemplateEngine
{
    private $viewPath;
    private $cachePath;
    private $sections = []; // Stores content for sections
    private $currentSection = null; // The current section being captured

    public function __construct($viewPath, $cachePath)
    {
        $this->viewPath = rtrim($viewPath, '/');
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

        // 1. Handle #extends
        if (preg_match('/#extends\([\'"](.+?)[\'"]\)/', $content, $matches)) {
            $layoutFile = "{$this->viewPath}/{$matches[1]}.moni";
            if (!file_exists($layoutFile)) {
                throw new \Exception("Layout file '{$matches[1]}.moni' not found.");
            }
            $layoutContent = file_get_contents($layoutFile);
            $content = preg_replace('/#extends\([\'"](.+?)[\'"]\)/', '', $content);
            $content = $this->processSections($content);
            $content = $this->injectSections($layoutContent, $content);
        }

        // 2. Replace {{ variable }} syntax
        $content = preg_replace_callback('/{{\s*(.+?)\s*}}/', function ($matches) {
            $variable = trim($matches[1]);
            return "<?php echo htmlspecialchars({$variable}, ENT_QUOTES, 'UTF-8'); ?>";
        }, $content);

        // 3. Replace #if/#elseif/#else/#endif for conditionals
        $content = preg_replace_callback('/#if\s*\(([^()]*+(?:\((?1)\)[^()]*+)*?)\)/', function ($matches) {
            $condition = trim($matches[1]);
            return "<?php if ({$condition}): ?>";
        }, $content);

        $content = preg_replace_callback('/#elseif\s*\(([^()]*+(?:\((?1)\)[^()]*+)*?)\)/', function ($matches) {
            $condition = trim($matches[1]);
            return "<?php elseif ({$condition}): ?>";
        }, $content);

        $content = preg_replace('/#else/', '<?php else: ?>', $content);
        $content = preg_replace('/#endif/', '<?php endif; ?>', $content);

        // 4. Replace #foreach/#endforeach for loops
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

        // Save the compiled PHP content to the cached file
        file_put_contents($cachedFile, $content);
    }

    /**
     * Process #section and #endsection directives in the content
     *
     * @param string $content The content to process
     * @return string The processed content
     */
    private function processSections($content)
    {
        return preg_replace_callback('/#section\([\'"](.+?)[\'"]\)(.*?)#endsection/s', function ($matches) {
            $sectionName = $matches[1];
            $sectionContent = trim($matches[2]);
            $this->sections[$sectionName] = $sectionContent;
            return ''; // Remove section content from the compiled view
        }, $content);
    }

    /**
     * Inject #yield placeholders in the layout with section content
     *
     * @param string $layoutContent The layout content
     * @param string $viewContent The compiled view content
     * @return string The final content with sections injected
     */
    private function injectSections($layoutContent, $viewContent)
    {
        return preg_replace_callback('/#yield\([\'"](.+?)[\'"]\)/', function ($matches) {
            $sectionName = $matches[1];
            return $this->sections[$sectionName] ?? ''; // Replace with section content or leave empty
        }, $layoutContent);
    }
}
