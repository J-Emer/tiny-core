<?php 

namespace Jemer\Tiny\Parsers;

use Symfony\Component\Finder\SplFileInfo;

interface ParserInterface
{
    public function Parse(SplFileInfo $file) : array;
}


?>