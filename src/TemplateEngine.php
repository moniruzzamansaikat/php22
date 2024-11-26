<?php

namespace Php22;

class TemplateEngine
{
    private $viewPath;
    private $cachePath;
    private $sections = [];
    private $currentSection = null;

    public $layout = null; // Property for layout
    public $templateExtension = '.html'; // Default template extension
    public $cacheExtension = '.php'; // Default cache file extension
    public $csrfPlaceholder = '#csrf()'; // Placeholder for CSRF token

    public function getViewPath()
    {
        return $this->viewPath;
    }

    public function __construct($viewPath, $cachePath)
    {
        $this->viewPath = rtrim($viewPath, '/');
        $this->cachePath = rtrim($cachePath, '/');
    }

    public function render($view, $data = [])
    {
        $viewFile = "{$this->viewPath}/{$view}{$this->templateExtension}";
        $cachedFile = "{$this->cachePath}/{$view}{$this->cacheExtension}";

        if (!file_exists($viewFile)) {
            throw new \Exception("View file '{$view}{$this->templateExtension}' not found.");
        }

        if (!file_exists($cachedFile) || filemtime($viewFile) > filemtime($cachedFile)) {
            $this->compile($viewFile, $cachedFile);
        }

        extract($data, EXTR_SKIP);

        ob_start();
        include $cachedFile;
        return ob_get_clean();
    }

    private function compile($viewFile, $cachedFile)
    {
        // Load the view file content
        $content = file_get_contents($viewFile);

        // Match any PHP code and execute it to handle layout assignment
        $content = preg_replace_callback('/#php(.*?)#\/php/s', function ($matches) {
            ob_start();
            eval(trim($matches[1]));
            return ob_get_clean();
        }, $content);

        // Extract the layout if defined
        $layout = $this->layout ?? null;

        // Detect the main content (outside #php blocks)
        $mainContent = $this->extractMainContent($content);

        // If a layout is defined, inject the content automatically
        if ($layout) {
            $layoutFile = "{$this->viewPath}/{$layout}{$this->templateExtension}";
            if (!file_exists($layoutFile)) {
                throw new \Exception("Layout file '{$layout}{$this->templateExtension}' not found.");
            }

            $layoutContent = file_get_contents($layoutFile);

            // Inject the main content into the layout at #yield('content')
            $content = preg_replace('/#yield\([\'"]content[\'"]\)/', $mainContent, $layoutContent);
        }

        // Handle {{ variable }} syntax
        $content = preg_replace_callback('/{{\s*(.+?)\s*}}/', function ($matches) {
            $variable = trim($matches[1]);
            return "<?php echo htmlspecialchars({$variable}, ENT_QUOTES, 'UTF-8'); ?>";
        }, $content);

        // Handle CSRF token placeholder
        $content = str_replace($this->csrfPlaceholder, "<?php echo csrf_field(); ?>", $content);

        // Handle conditionals
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

        // Handle PHP blocks
        $content = preg_replace('/^\s*#php\s*$/m', '<?php', $content);
        $content = preg_replace('/^\s*#\/php\s*$/m', '?>', $content);

        // Handle loops
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

        // Save compiled content
        file_put_contents($cachedFile, $content);
    }

    /**
     * Extract the main content outside of PHP blocks.
     *
     * @param string $content
     * @return string
     */
    private function extractMainContent(string $content): string
    {
        // Remove all PHP blocks and return the rest as the main content
        $contentWithoutPhp = preg_replace('/#php(.*?)#\/php/s', '', $content);
        return trim($contentWithoutPhp);
    }

    private function processSections($content)
    {
        return preg_replace_callback('/#section\([\'"](.+?)[\'"]\)(.*?)#endsection/s', function ($matches) {
            $sectionName = $matches[1];
            $sectionContent = trim($matches[2]);
            $this->sections[$sectionName] = $sectionContent;
            return ''; // Remove section content from the compiled view
        }, $content);
    }

    private function injectSections($layoutContent, $viewContent)
    {
        return preg_replace_callback('/#yield\([\'"](.+?)[\'"]\)/', function ($matches) {
            $sectionName = $matches[1];
            return $this->sections[$sectionName] ?? ''; // Replace with section content or leave empty
        }, $layoutContent);
    }

    public function renderFromCore($view, $data = [], $viewPathOverride = null)
    {
        $viewPath = $viewPathOverride ?? $this->viewPath;
        $viewFile = "{$viewPath}/{$view}{$this->templateExtension}";
        $cachedFile = "{$this->cachePath}/core_{$view}.php";

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
}
