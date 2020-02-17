<?php
declare(strict_types = 1);

namespace Embed;

use DOMDocument;
use DOMNode;
use DOMXPath;
use HtmlParser\Parser;

class Document
{
    private Extractor $extractor;
    private DOMDocument $document;
    private DOMXPath $xpath;

    public function __construct(Extractor $extractor)
    {
        $this->extractor = $extractor;

        $html = (string) $extractor->getResponse()->getBody();
        $html = str_replace('<br>', "\n<br>", $html);
        $html = str_replace('<br ', "\n <br ", $html);

        $this->document = !empty($html) ? Parser::parse($html) : new DOMDocument();
        $this->initXPath();
    }

    private function initXPath()
    {
        $this->xpath = new DOMXPath($this->document);
        $this->xpath->registerNamespace('php', 'http://php.net/xpath');
        $this->xpath->registerPhpFunctions();
    }

    public function __clone()
    {
        $this->document = clone $this->document;
        $this->initXPath();
    }

    public function remove(string $query): void
    {
        foreach ($this->select($query)->nodes() as $node) {
            $node->parentNode->removeChild($node);
        }
    }

    public function getDocument(): DOMDocument
    {
        return $this->document;
    }

    /**
     * Helper to build xpath queries easily and case insensitive
     */
    private static function buildQuery(string $startQuery, array $attributes): string
    {
        $selector = [$startQuery];

        foreach ($attributes as $name => $value) {
            $selector[] = sprintf('[php:functionString("strtolower", @%s)="%s"]', $name, mb_strtolower($value));
        }

        return implode('', $selector);
    }

    /**
     * Select a element in the dom
     */
    public function select(string $query, array $attributes = null, DOMNode $context = null): ?QueryResult
    {
        if (!empty($attributes)) {
            $query = self::buildQuery($query, $attributes);
        }

        return new QueryResult($this->xpath->query($query, $context), $this->extractor);
    }

    public function meta(string $type): ?string
    {
        return $this->select('.//meta', ['name' => $type])->str('content')
            ?: $this->select('.//meta', ['property' => $type])->str('content')
            ?: $this->select('.//meta', ['itemprop' => $type])->str('content');
    }

    public function link(string $rel, array $extra = []): ?string
    {
        return $this->select('.//link', ['rel' => $rel] + $extra)->url('href');
    }

    public function __toString(): string
    {
        return Parser::stringify($this->getDocument());
    }
}
