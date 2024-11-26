<?php

namespace Php22\TemplateEngine\TemplateBlocks;

use Php22\TemplateEngine\Interfaces\ITemplateBlock;

class Assets implements ITemplateBlock
{
    private $content;

    private $assets = [];

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    public function handleBlock(): string
    {
        $content = preg_replace_callback('/#assets\([\'"](.+?)[\'"]\)(.*?)#\/assets/s', function ($matches) {
            $key = trim($matches[1]);
            $assetContent = trim($matches[2]);

            // Store the assets under the given key
            if (!isset($this->assets[$key])) {
                $this->assets[$key] = [];
            }
            $this->assets[$key][] = $assetContent;

            return ''; // Remove the asset block from the compiled content
        }, $this->content);

        return $content;
    }
}
