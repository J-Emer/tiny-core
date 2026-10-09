<?php 

namespace Jemer\Tiny\Parsers;

use Symfony\Component\Finder\SplFileInfo;
use Override;
use Symfony\Component\Yaml\Yaml;

class YamlParser implements ParserInterface
{

    #[Override]
    public function Parse(SplFileInfo $file): array
    {
        return Yaml::parse(file_get_contents($file));
    }

}

?>