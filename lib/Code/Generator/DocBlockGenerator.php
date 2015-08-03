<?php
namespace Goetas\Xsd\XsdToPhp\Code\Generator;

use Zend\Code\Generator\DocBlockGenerator as ZendDocBlockGenerator;

/**
 * @author Warnar Boekkooi <warnar@boekkooi.net>
 */
class DocBlockGenerator extends ZendDocBlockGenerator
{
    /**
     * @param  string $content
     * @return string
     */
    protected function docCommentize($content)
    {
        $indent  = $this->getIndentation();
        $output  = $indent . '/**' . self::LINE_FEED;

        $lines = explode(self::LINE_FEED, $content);
        foreach ($lines as $rawLine) {
            if ($this->getWordWrap() === true && substr($rawLine, 0, 1) !== '@') {
                $rawLines = explode(self::LINE_FEED, wordwrap($rawLine, 80, self::LINE_FEED));
            } else {
                $rawLines = [$rawLine];
            }

            foreach ($rawLines as $line) {
                $output .= $indent . ' *';
                if ($line) {
                    $output .= " $line";
                }
                $output .= self::LINE_FEED;
            }
        }
        $output .= $indent . ' */' . self::LINE_FEED;

        return $output;
    }
}
