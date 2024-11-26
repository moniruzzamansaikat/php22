<?php

namespace Php22\TemplateEngine\TemplateBlocks;

use Php22\TemplateEngine\Interfaces\ITemplateBlock;

class Loops implements ITemplateBlock
{
    private $content;

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    public function handleBlock(): string
    {
        $content = preg_replace_callback('/#foreach\s*\((.+?)\)/', function ($matches) {
            $loop = trim($matches[1]);
            return "<?php foreach ({$loop}): ?>";
        }, $this->content);

        $content = preg_replace('/#endforeach/', '<?php endforeach; ?>', $content);

        return $content;
    }
}
