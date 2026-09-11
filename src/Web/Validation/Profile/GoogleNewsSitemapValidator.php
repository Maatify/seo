<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation\Profile;

use Maatify\Seo\Web\Validation\DTO\SeoCompanionValidationResultDTO;
use Maatify\Seo\Web\Validation\DTO\SeoValidationContextDTO;
use Maatify\Seo\Web\Validation\Input\Sitemap\SitemapNewsValidationInputDTO;
use Maatify\Seo\Web\Validation\Input\Sitemap\SitemapUrlValidationInputDTO;
use Maatify\Seo\Web\Validation\Input\Sitemap\SitemapValidationDocumentDTO;
use Maatify\Seo\Web\Validation\Profile\Internal\SitemapValidationSupport;

final class GoogleNewsSitemapValidator
{
    public function validate(
        SitemapValidationDocumentDTO $document,
        ?SeoValidationContextDTO $context = null,
    ): SeoCompanionValidationResultDTO {
        $diagnostics = [];
        if ($document->type !== 'urlset') {
            return SitemapValidationSupport::result($diagnostics);
        }

        $totalNews = 0;
        foreach ($document->entries as $entry) {
            if ($entry instanceof SitemapUrlValidationInputDTO) {
                $totalNews += count($entry->news);
            }
        }
        if ($totalNews > 1000) {
            $diagnostics[] = SitemapValidationSupport::diagnostic(
                'google_news_document_count_exceeds_limit',
                'warning',
                'The Sitemap contains more than 1,000 News entries.',
                'news',
                'sitemap_document',
            );
        }

        foreach ($document->entries as $entryIndex => $entry) {
            if (!$entry instanceof SitemapUrlValidationInputDTO) {
                continue;
            }

            if (count($entry->news) > 1) {
                $diagnostics[] = SitemapValidationSupport::diagnostic(
                    'google_news_multiple_entries_per_url',
                    'warning',
                    'Google recommends no more than one News entry per URL.',
                    'news',
                    'sitemap_url',
                    $entryIndex,
                );
            }

            foreach ($entry->news as $newsIndex => $news) {
                $this->validateNews($news, $entryIndex, $newsIndex, $context, $diagnostics);
            }
        }

        return SitemapValidationSupport::result($diagnostics);
    }

    /** @param list<\Maatify\Seo\Web\Validation\DTO\SeoCompanionDiagnosticDTO> $diagnostics */
    private function validateNews(
        SitemapNewsValidationInputDTO $news,
        int $entryIndex,
        int $newsIndex,
        ?SeoValidationContextDTO $context,
        array &$diagnostics,
    ): void {
        $publicationName = $news->publicationName;
        if (SitemapValidationSupport::isMissing($publicationName)) {
            $diagnostics[] = SitemapValidationSupport::diagnostic(
                'google_news_publication_name_missing',
                'warning',
                'A Google News entry requires a non-empty publication name.',
                'name',
                'sitemap_news',
                $entryIndex,
                $newsIndex,
            );
        } elseif ($publicationName !== null && preg_match('/\([^)]*\)/', $publicationName) === 1) {
            $diagnostics[] = SitemapValidationSupport::diagnostic(
                'google_news_publication_name_parenthetical',
                'warning',
                'The Google News publication name should omit parenthetical content.',
                'name',
                'sitemap_news',
                $entryIndex,
                $newsIndex,
            );
        }

        $publicationLanguage = $news->publicationLanguage;
        if (SitemapValidationSupport::isMissing($publicationLanguage)) {
            $diagnostics[] = SitemapValidationSupport::diagnostic(
                'google_news_language_missing',
                'warning',
                'A Google News entry requires a non-empty language.',
                'language',
                'sitemap_news',
                $entryIndex,
                $newsIndex,
            );
        } elseif ($publicationLanguage !== null && !SitemapValidationSupport::isNewsLanguage($publicationLanguage)) {
            $diagnostics[] = SitemapValidationSupport::diagnostic(
                'google_news_language_invalid',
                'warning',
                'The Google News language is outside the fixed language contract.',
                'language',
                'sitemap_news',
                $entryIndex,
                $newsIndex,
            );
        }

        $publicationDate = $news->publicationDate;
        if (SitemapValidationSupport::isMissing($publicationDate)) {
            $diagnostics[] = SitemapValidationSupport::diagnostic(
                'google_news_publication_date_missing',
                'warning',
                'A Google News entry requires a non-empty publication date.',
                'publication_date',
                'sitemap_news',
                $entryIndex,
                $newsIndex,
            );
        } elseif ($publicationDate !== null && !SitemapValidationSupport::isNewsPublicationDate($publicationDate)) {
            $diagnostics[] = SitemapValidationSupport::diagnostic(
                'google_news_publication_date_invalid',
                'warning',
                'The Google News publication date is outside the fixed lexical contract.',
                'publication_date',
                'sitemap_news',
                $entryIndex,
                $newsIndex,
            );
        }

        if (SitemapValidationSupport::isMissing($news->title)) {
            $diagnostics[] = SitemapValidationSupport::diagnostic(
                'google_news_title_missing',
                'warning',
                'A Google News entry requires a non-empty title.',
                'title',
                'sitemap_news',
                $entryIndex,
                $newsIndex,
            );
        } else {
            $titleState = SitemapValidationSupport::indexedEvidence(
                $context,
                'google_news.title_content_conformance',
                $entryIndex,
                $newsIndex,
            );
            $diagnostics[] = SitemapValidationSupport::diagnostic(
                'google_news_title_content_evidence',
                $titleState === 'nonconforming' ? 'warning' : 'info',
                'Google News title/content conformance is represented only by caller-supplied evidence.',
                'title',
                'sitemap_news',
                $entryIndex,
                $newsIndex,
                $titleState,
            );
        }

        if (!SitemapValidationSupport::isMissing($publicationDate)) {
            $originalState = SitemapValidationSupport::indexedEvidence(
                $context,
                'google_news.original_publication',
                $entryIndex,
                $newsIndex,
            );
            $diagnostics[] = SitemapValidationSupport::diagnostic(
                'google_news_original_publication_evidence',
                $originalState === 'not_original' ? 'warning' : 'info',
                'Google News original-publication status is represented only by caller-supplied evidence.',
                'publication_date',
                'sitemap_news',
                $entryIndex,
                $newsIndex,
                $originalState,
            );
        }

        if (!SitemapValidationSupport::isMissing($publicationName)) {
            $nameState = SitemapValidationSupport::indexedEvidence(
                $context,
                'google_news.publication_name_match',
                $entryIndex,
                $newsIndex,
            );
            $diagnostics[] = SitemapValidationSupport::diagnostic(
                'google_news_name_exact_match_evidence',
                $nameState === 'mismatched' ? 'warning' : 'info',
                'Google News publication-name matching is represented only by caller-supplied evidence.',
                'name',
                'sitemap_news',
                $entryIndex,
                $newsIndex,
                $nameState,
            );
        }

        $freshnessState = SitemapValidationSupport::indexedEvidence(
            $context,
            'google_news.freshness',
            $entryIndex,
            $newsIndex,
        );
        $diagnostics[] = SitemapValidationSupport::diagnostic(
            'google_news_freshness_evidence',
            $freshnessState === 'outside_window' ? 'warning' : 'info',
            'Google News freshness is represented only by caller-supplied evidence.',
            null,
            'sitemap_news',
            $entryIndex,
            $newsIndex,
            $freshnessState,
        );
    }
}
