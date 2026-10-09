<?php 

namespace Jemer\Tiny\Parsers;

use Symfony\Component\Finder\SplFileInfo;
use Override;

class JsonParser implements ParserInterface
{
    #[Override]
    public function Parse(SplFileInfo $file): array
    {
        return json_decode($file->getContents(), true);    
    }
}


?>