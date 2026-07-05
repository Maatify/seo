<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\JsonLd\Builder;

final class ArticleJsonLdBuilder extends AbstractJsonLdBuilder
{
    private const TYPE_ARTICLE = 'Article';
    private const TYPE_BLOG_POSTING = 'BlogPosting';
    private const TYPE_NEWS_ARTICLE = 'NewsArticle';

    /** @var array<int, string> */
    private const SUPPORTED_TYPES = [
        self::TYPE_ARTICLE,
        self::TYPE_BLOG_POSTING,
        self::TYPE_NEWS_ARTICLE,
    ];

    public function __construct(string $type = self::TYPE_ARTICLE)
    {
        parent::__construct([
            '@context' => 'https://schema.org',
            '@type' => self::normalizeType($type),
        ]);
    }

    public static function article(): self
    {
        return new self(self::TYPE_ARTICLE);
    }

    public static function blogPosting(): self
    {
        return new self(self::TYPE_BLOG_POSTING);
    }

    public static function newsArticle(): self
    {
        return new self(self::TYPE_NEWS_ARTICLE);
    }

    public function setType(string $type): static
    {
        return $this->set('@type', self::normalizeType($type));
    }

    public function asArticle(): static
    {
        return $this->setType(self::TYPE_ARTICLE);
    }

    public function asBlogPosting(): static
    {
        return $this->setType(self::TYPE_BLOG_POSTING);
    }

    public function asNewsArticle(): static
    {
        return $this->setType(self::TYPE_NEWS_ARTICLE);
    }

    public function setHeadline(string $headline): static
    {
        return $this->set('headline', $headline);
    }

    public function setDescription(string $description): static
    {
        return $this->set('description', $description);
    }

    public function setUrl(string $url): static
    {
        return $this->set('url', $url);
    }

    /** @param string|array<int, string> $image */
    public function setImage(string|array $image): static
    {
        return $this->set('image', $image);
    }

    /** @param array<int, string> $images */
    public function setImages(array $images): static
    {
        return $this->setImage($images);
    }

    /** @param string|array<string, mixed> $author */
    public function setAuthor(string|array $author): static
    {
        if (is_string($author)) {
            $author = [
                '@type' => 'Person',
                'name' => $author,
            ];
        }

        return $this->set('author', $author);
    }

    /** @param string|array<string, mixed> $publisher */
    public function setPublisher(string|array $publisher): static
    {
        if (is_string($publisher)) {
            $publisher = [
                '@type' => 'Organization',
                'name' => $publisher,
            ];
        }

        return $this->set('publisher', $publisher);
    }

    public function setDatePublished(string $datePublished): static
    {
        return $this->set('datePublished', $datePublished);
    }

    public function setDateModified(string $dateModified): static
    {
        return $this->set('dateModified', $dateModified);
    }

    /** @param string|array<string, mixed> $mainEntityOfPage */
    public function setMainEntityOfPage(string|array $mainEntityOfPage): static
    {
        if (is_string($mainEntityOfPage)) {
            $mainEntityOfPage = [
                '@type' => 'WebPage',
                '@id' => $mainEntityOfPage,
            ];
        }

        return $this->set('mainEntityOfPage', $mainEntityOfPage);
    }

    public function setArticleSection(string $articleSection): static
    {
        return $this->set('articleSection', $articleSection);
    }

    /** @param string|array<int, string> $keywords */
    public function setKeywords(string|array $keywords): static
    {
        return $this->set('keywords', $keywords);
    }

    private static function normalizeType(string $type): string
    {
        return in_array($type, self::SUPPORTED_TYPES, true) ? $type : self::TYPE_ARTICLE;
    }
}
