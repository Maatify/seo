<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Robots;

use Maatify\Seo\Exception\SeoInvalidArgumentException;

/**
 * Builds an ordered, duplicate-free robots meta directive list.
 */
final class MetaRobotsBuilder
{
    private const DIRECTIVE_INDEX = 'index';
    private const DIRECTIVE_NOINDEX = 'noindex';
    private const DIRECTIVE_FOLLOW = 'follow';
    private const DIRECTIVE_NOFOLLOW = 'nofollow';
    private const DIRECTIVE_NOARCHIVE = 'noarchive';
    private const DIRECTIVE_NOSNIPPET = 'nosnippet';
    private const DIRECTIVE_NOIMAGEINDEX = 'noimageindex';
    private const DIRECTIVE_NOTRANSLATE = 'notranslate';
    private const PREFIX_MAX_SNIPPET = 'max-snippet:';
    private const PREFIX_MAX_IMAGE_PREVIEW = 'max-image-preview:';
    private const PREFIX_MAX_VIDEO_PREVIEW = 'max-video-preview:';
    private const PREFIX_UNAVAILABLE_AFTER = 'unavailable_after:';

    /** @var list<string> */
    private array $directives = [];

    public function __construct()
    {
    }

    /**
     * Allow search engines to index the page and remove any noindex directive.
     */
    public function index(): static
    {
        return $this->addExclusive(self::DIRECTIVE_INDEX, [self::DIRECTIVE_NOINDEX]);
    }

    /**
     * Prevent search engines from indexing the page and remove any index directive.
     */
    public function noIndex(): static
    {
        return $this->addExclusive(self::DIRECTIVE_NOINDEX, [self::DIRECTIVE_INDEX]);
    }

    /**
     * Allow search engines to follow links and remove any nofollow directive.
     */
    public function follow(): static
    {
        return $this->addExclusive(self::DIRECTIVE_FOLLOW, [self::DIRECTIVE_NOFOLLOW]);
    }

    /**
     * Prevent search engines from following links and remove any follow directive.
     */
    public function noFollow(): static
    {
        return $this->addExclusive(self::DIRECTIVE_NOFOLLOW, [self::DIRECTIVE_FOLLOW]);
    }

    /**
     * Prevent search engines from showing a cached copy of the page.
     */
    public function noArchive(): static
    {
        return $this->add(self::DIRECTIVE_NOARCHIVE);
    }

    /**
     * Prevent search engines from showing text snippets or video previews.
     */
    public function noSnippet(): static
    {
        return $this->add(self::DIRECTIVE_NOSNIPPET);
    }

    /**
     * Prevent search engines from indexing images on the page.
     */
    public function noImageIndex(): static
    {
        return $this->add(self::DIRECTIVE_NOIMAGEINDEX);
    }

    /**
     * Prevent search engines from offering translated versions of the page.
     */
    public function noTranslate(): static
    {
        return $this->add(self::DIRECTIVE_NOTRANSLATE);
    }

    /**
     * Set the maximum snippet length in characters.
     *
     * @throws SeoInvalidArgumentException When $value is negative.
     */
    public function maxSnippet(int $value): static
    {
        $this->assertNonNegative($value, 'max-snippet');

        return $this->replacePrefixed(self::PREFIX_MAX_SNIPPET, self::PREFIX_MAX_SNIPPET . $value);
    }

    /**
     * Set the maximum image preview size.
     *
     * @throws SeoInvalidArgumentException When $value is not none, standard, or large.
     */
    public function maxImagePreview(string $value): static
    {
        if (!in_array($value, ['none', 'standard', 'large'], true)) {
            throw SeoInvalidArgumentException::invalidValue('max-image-preview', 'Allowed values are: none, standard, large.');
        }

        return $this->replacePrefixed(self::PREFIX_MAX_IMAGE_PREVIEW, self::PREFIX_MAX_IMAGE_PREVIEW . $value);
    }

    /**
     * Set the maximum video preview length in seconds.
     *
     * @throws SeoInvalidArgumentException When $value is negative.
     */
    public function maxVideoPreview(int $value): static
    {
        $this->assertNonNegative($value, 'max-video-preview');

        return $this->replacePrefixed(self::PREFIX_MAX_VIDEO_PREVIEW, self::PREFIX_MAX_VIDEO_PREVIEW . $value);
    }

    /**
     * Set an unavailable_after directive using the caller-provided date string.
     */
    public function unavailableAfter(string $value): static
    {
        return $this->replacePrefixed(self::PREFIX_UNAVAILABLE_AFTER, self::PREFIX_UNAVAILABLE_AFTER . $value);
    }

    /**
     * Add an arbitrary directive while preserving uniqueness and known exclusivity rules.
     */
    public function add(string $directive): static
    {
        return match ($directive) {
            self::DIRECTIVE_INDEX => $this->index(),
            self::DIRECTIVE_NOINDEX => $this->noIndex(),
            self::DIRECTIVE_FOLLOW => $this->follow(),
            self::DIRECTIVE_NOFOLLOW => $this->noFollow(),
            default => $this->addUniqueReplacingMatchingPrefix($directive),
        };
    }

    /**
     * Remove an exact directive string when present.
     */
    public function remove(string $directive): static
    {
        $this->directives = array_values(array_filter(
            $this->directives,
            static fn (string $existing): bool => $existing !== $directive
        ));

        return $this;
    }

    /**
     * Remove all directives.
     */
    public function clear(): static
    {
        $this->directives = [];

        return $this;
    }

    /**
     * Determine whether an exact directive string exists.
     */
    public function has(string $directive): bool
    {
        return in_array($directive, $this->directives, true);
    }

    /**
     * @return list<string>
     */
    public function toArray(): array
    {
        return $this->directives;
    }

    /**
     * Render directives as a comma-separated robots content string.
     */
    public function build(): string
    {
        return implode(', ', $this->directives);
    }

    /**
     * Render directives as a comma-separated robots content string.
     */
    public function __toString(): string
    {
        return $this->build();
    }

    /**
     * Render an escaped robots meta tag.
     */
    public function toHtml(): string
    {
        $content = htmlspecialchars($this->build(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<meta name="robots" content="' . $content . '">';
    }

    /**
     * @param list<string> $excludedDirectives
     */
    private function addExclusive(string $directive, array $excludedDirectives): static
    {
        foreach ($excludedDirectives as $excludedDirective) {
            $this->remove($excludedDirective);
        }

        return $this->addUnique($directive);
    }

    private function addUniqueReplacingMatchingPrefix(string $directive): static
    {
        foreach ([self::PREFIX_MAX_SNIPPET, self::PREFIX_MAX_IMAGE_PREVIEW, self::PREFIX_MAX_VIDEO_PREVIEW, self::PREFIX_UNAVAILABLE_AFTER] as $prefix) {
            if (str_starts_with($directive, $prefix)) {
                return $this->replacePrefixed($prefix, $directive);
            }
        }

        return $this->addUnique($directive);
    }

    private function replacePrefixed(string $prefix, string $directive): static
    {
        $this->directives = array_values(array_filter(
            $this->directives,
            static fn (string $existing): bool => !str_starts_with($existing, $prefix)
        ));

        return $this->addUnique($directive);
    }

    private function addUnique(string $directive): static
    {
        if (!$this->has($directive)) {
            $this->directives[] = $directive;
        }

        return $this;
    }

    private function assertNonNegative(int $value, string $field): void
    {
        if ($value < 0) {
            throw SeoInvalidArgumentException::invalidValue($field, 'Value must be greater than or equal to 0.');
        }
    }
}
