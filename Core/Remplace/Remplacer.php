<?php

namespace App\Core\Remplace;

class Remplacer
{

    private $templateText = '';
    private $delimiter = '$$';

    function __construct($templatePath)
    {
        $this->templateText = file_get_contents($templatePath);
    }

    public function getRegex($varname)
    {
        return '/' . $this->getDelimiter() . $varname . $this->getDelimiter() . '/';
    }

    public function getCompareExpression($varname)
    {
        return $this->delimiter . $varname . $this->delimiter;
    }

    public function getDelimiter()
    {
        return ($this->delimiter);
    }

    public function remplace($varname, $value)
    {
        $modified = str_replace($this->getCompareExpression($varname), $value, $this->templateText);
        return $this->templateText = $modified;
    }

    public function save($filename)
    {
        $newfile = fopen($filename, 'w');
        $result = fwrite($newfile, $this->templateText);
        fclose($newfile);
        return $result;
    }
}
