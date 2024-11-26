<?php

namespace Php22\TemplateEngine\TemplateBlocks;

use Php22\TemplateEngine\Interfaces\ITemplateBlock;

class Variable implements ITemplateBlock
{
    private $pattern = '/{{\s*(.+?)\s*}}/';

    private $content;

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    public function handleBlock(): string
    {
        $content = preg_replace_callback($this->pattern, function ($matches) {
            $variable = trim($matches[1]);
            return "<?php echo htmlspecialchars({$variable}, ENT_QUOTES, 'UTF-8'); ?>";
        }, $this->content);

        return $content;
    }
}
