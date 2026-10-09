<?php 

namespace Jemer\Tiny\Parsers;


use Mni\FrontYAML\Parser;
use Override;
use Symfony\Component\Finder\SplFileInfo;


class MarkdownParser implements ParserInterface
{
    private Parser $parser;

    public function __construct()
    {
        $this->parser = new Parser();
    }

    #[Override]
    public function Parse(SplFileInfo $file): array
    {
        $document = $this->parser->parse($file->getContents());
        $yaml = $document->getYAML() ?? [];
        $yaml['content'] = $document->getContent();
        return $yaml;  
    }


}


?>