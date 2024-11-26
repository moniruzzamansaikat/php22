<?php

namespace Php22\TemplateEngine\TemplateBlocks;

use Php22\TemplateEngine\Interfaces\ITemplateBlock;

class Conditionals implements ITemplateBlock
{
    private $content;

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    public function handleBlock(): string
    {
        $content = preg_replace_callback('/#if\s*\(([^()]*+(?:\((?1)\)[^()]*+)*?)\)/', function ($matches) {
            $condition = trim($matches[1]);
            return "<?php if ({$condition}): ?>";
        }, $this->content);

        $content = preg_replace_callback('/#elseif\s*\(([^()]*+(?:\((?1)\)[^()]*+)*?)\)/', function ($matches) {
            $condition = trim($matches[1]);
            return "<?php elseif ({$condition}): ?>";
        }, $content);

        $content = preg_replace('/#else/', '<?php else: ?>', $content);
        $content = preg_replace('/#endif/', '<?php endif; ?>', $content);

        return $content;
    }
}
