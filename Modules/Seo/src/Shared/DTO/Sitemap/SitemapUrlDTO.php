<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\DTO\Sitemap;

use Maatify\Seo\Exception\SeoInvalidArgumentException;

final readonly class SitemapUrlDTO implements \JsonSerializable
{
    /** @var list<SitemapAlternateUrlDTO> */
    public array $alternates;

    /**
     * @param array<mixed> $alternates
     */
    public function __construct(
        public string $loc,
        public ?string $lastmod = null,
        public ?string $changefreq = null,
        public ?float $priority = null,
        array $alternates = [],
    ) {
        if (trim($this->loc) === '') {
            throw SeoInvalidArgumentException::emptyField('loc');
        }
        if (filter_var($this->loc, FILTER_VALIDATE_URL) === false) {
            throw SeoInvalidArgumentException::emptyField('loc');
        }
        if ($this->lastmod !== null && !self::isValidLastmod($this->lastmod)) {
            throw SeoInvalidArgumentException::emptyField('lastmod');
        }
        if ($this->changefreq !== null && !in_array($this->changefreq, self::allowedChangefreqValues(), true)) {
            throw SeoInvalidArgumentException::emptyField('changefreq');
        }
        if ($this->priority !== null && ($this->priority < 0.0 || $this->priority > 1.0)) {
            throw SeoInvalidArgumentException::emptyField('priority');
        }

        $validAlternates = [];
        foreach ($alternates as $alternate) {
            if (!$alternate instanceof SitemapAlternateUrlDTO) {
                throw SeoInvalidArgumentException::emptyField('alternates');
            }

            $validAlternates[] = $alternate;
        }

        $this->alternates = $validAlternates;
    }

    public static function isValidLastmod(string $lastmod): bool
    {
        $value = trim($lastmod);
        if ($value === '') {
            return false;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            $parts = explode('-', $value);

            return checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0]);
        }

        return \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $value) instanceof \DateTimeImmutable;
    }

    /**
     * @return list<string>
     */
    public static function allowedChangefreqValues(): array
    {
        return ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'];
    }

    /**
     * @return array{loc: string, lastmod: ?string, changefreq: ?string, priority: ?float, alternates: list<array{hreflang: string, url: string}>}
     */
    public function jsonSerialize(): array
    {
        $alternates = [];
        foreach ($this->alternates as $alternate) {
            $alternates[] = $alternate->jsonSerialize();
        }

        return [
            'loc' => trim($this->loc),
            'lastmod' => $this->lastmod === null ? null : trim($this->lastmod),
            'changefreq' => $this->changefreq,
            'priority' => $this->priority,
            'alternates' => $alternates,
        ];
    }
}
