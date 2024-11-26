<?php

namespace Php22;

class TemplateEngine
{
    private $viewPath;
    private $cachePath;
    private $sections = []; 
    private $currentSection = null; 

    public function __construct($viewPath, $cachePath)
    {
        $this->viewPath = rtrim($viewPath, '/');
        $this->cachePath = rtrim($cachePath, '/');
    }

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

        $content = preg_replace('/#csrf\(\)/', "<?php echo csrf_field(); ?>", $content);

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

        // php block start and end
        $content = preg_replace('/^\s*#php\s*$/m', '<?php', $content);
        $content = preg_replace('/^\s*#\/php\s*$/m', '?>', $content);
        
        $content = preg_replace_callback('/#foreach\s*\((.+?)\)/', function ($matches) {
            $loop = trim($matches[1]);
            return "<?php foreach ({$loop}): ?>";
        }, $content);

        $content = preg_replace('/#endforeach/', '<?php endforeach; ?>', $content);

        $cachedDir = dirname($cachedFile);
        if (!is_dir($cachedDir)) {
            mkdir($cachedDir, 0777, true); // Create directories recursively
        }

        file_put_contents($cachedFile, $content);
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
}
