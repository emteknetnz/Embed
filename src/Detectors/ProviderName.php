<?php
declare(strict_types = 1);

namespace Embed\Detectors;

class ProviderName extends Detector
{
    private static array $suffixes;

    public function detect(): string
    {
        $oembed = $this->extractor->getOEmbed();
        $document = $this->extractor->getDocument();

        return $oembed->str('provider_name')
            ?: $document->meta('og:site_name')
            ?: ucfirst($this->fallback());
    }

    private function fallback(): string
    {
        $host = $this->extractor->getUri()->getHost();

        $host = array_reverse(explode('.', $host));

        switch (count($host)) {
            case 1:
                return $host[0];
            case 2:
                return $host[1];
            default:
                $tld = $host[1].'.'.$host[0];
                $suffixes = self::getSuffixes();

                if (in_array($tld, $suffixes, true)) {
                    return $host[2];
                }

                return $host[1];
        }
    }

    private static function getSuffixes(): array
    {
        if (!isset(self::$suffixes)) {
            self::$suffixes = require dirname(__DIR__).'/resources/suffix.php';
        }

        return self::$suffixes;
    }
}
