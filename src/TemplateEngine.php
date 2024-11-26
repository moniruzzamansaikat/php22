<?php

namespace Php22;

use Php22\TemplateEngine\TemplateBlocks\Assets;
use Php22\TemplateEngine\TemplateBlocks\Conditionals;
use Php22\TemplateEngine\TemplateBlocks\Loops;
use Php22\TemplateEngine\TemplateBlocks\PhpBlock;
use Php22\TemplateEngine\TemplateBlocks\Variable;

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

    private $assets = [];

    private $blocks = [];


    public function getViewPath()
    {
        return $this->viewPath;
    }

    public function __construct($viewPath, $cachePath)
    {
        $this->viewPath = rtrim($viewPath, '/');
        $this->cachePath = rtrim($cachePath, '/');

        $this->blocks = [
            Assets::class,
            Variable::class,
            Loops::class,
            PhpBlock::class,
            Conditionals::class
        ];
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

        // Process asset blocks
        $content = preg_replace_callback('/#assets\([\'"](.+?)[\'"]\)(.*?)#\/assets/s', function ($matches) {
            $key = trim($matches[1]);
            $assetContent = trim($matches[2]);

            // Store the assets under the given key
            if (!isset($this->assets[$key])) {
                $this->assets[$key] = [];
            }
            $this->assets[$key][] = $assetContent;

            return ''; // Remove the asset block from the compiled content
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

            // Inject assets into the layout at #loadAssets('key')
            $content = preg_replace_callback('/#loadAssets\([\'"](.+?)[\'"]\)/', function ($matches) {
                $key = trim($matches[1]);

                // Render all assets under the given key
                if (isset($this->assets[$key])) {
                    return implode("\n", $this->assets[$key]);
                }

                return ''; // If no assets for the key, leave it empty
            }, $content);
        }

        foreach ($this->blocks as $block) {
            $content = (new $block($content))->handleBlock();
        }

        // Handle CSRF token placeholder
        $content = str_replace($this->csrfPlaceholder, "<?php echo csrf_field(); ?>", $content);

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
}
