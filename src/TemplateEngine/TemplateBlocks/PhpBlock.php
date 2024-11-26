<?php

namespace Php22\TemplateEngine\TemplateBlocks;

use Php22\TemplateEngine\Interfaces\ITemplateBlock;

class PhpBlock implements ITemplateBlock
{
    private $content;

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    public function handleBlock(): string
    {
        $content = preg_replace('/^\s*#php\s*$/m', '<?php', $this->content);
        $content = preg_replace('/^\s*#\/php\s*$/m', '?>', $content);

        return $content;
    }
}
