<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation\Profile;

use Maatify\Seo\Shared\Service\Internal\HreflangTagNormalizer;
use Maatify\Seo\Web\Validation\DTO\SeoCompanionDiagnosticDTO;
use Maatify\Seo\Web\Validation\DTO\SeoCompanionValidationResultDTO;
use Maatify\Seo\Web\Validation\DTO\SeoDiagnosticTargetDTO;
use Maatify\Seo\Web\Validation\DTO\SeoValidationContextDTO;
use Maatify\Seo\Web\Validation\Input\Hreflang\HreflangValidationClusterDTO;
use Maatify\Seo\Web\Validation\Profile\Internal\SitemapValidationSupport;

final class GoogleHreflangClusterValidator
{
    public function validate(
        HreflangValidationClusterDTO $cluster,
        ?SeoValidationContextDTO $context = null,
    ): SeoCompanionValidationResultDTO {
        unset($context);

        $diagnostics = [];
        /** @var array<int, list<array{tag: string, url: string}>> $usableLinksByPage */
        $usableLinksByPage = [];
        /** @var array<int, list<array{tag: ?string, url: ?string}>> $invalidLinksByPage */
        $invalidLinksByPage = [];
        /** @var array<string, int> $pageIndexesByUrl */
        $pageIndexesByUrl = [];

        foreach ($cluster->pages as $pageIndex => $page) {
            $pageIndexesByUrl[$page->pageUrl] = $pageIndex;
            $usableLinksByPage[$pageIndex] = [];
            $invalidLinksByPage[$pageIndex] = [];

            foreach ($page->links as $linkIndex => $link) {
                $tagValid = HreflangTagNormalizer::isValidSyntax($link->hreflang);
                $urlValid = SitemapValidationSupport::isAbsoluteUrl($link->url, true);

                if (!$tagValid) {
                    $diagnostics[] = $this->diagnostic(
                        'hreflang_tag_invalid_syntax',
                        'Candidate hreflang syntax does not match the fixed lexical grammar.',
                        'hreflang',
                        'hreflang_link',
                        $pageIndex,
                        $linkIndex,
                    );
                }

                if (!$urlValid) {
                    $diagnostics[] = $this->diagnostic(
                        'hreflang_url_not_fully_qualified',
                        'The alternate URL must be a fully-qualified absolute URL under the common lexical profile.',
                        'href',
                        'hreflang_link',
                        $pageIndex,
                        $linkIndex,
                    );
                }

                if (!$tagValid || !$urlValid) {
                    $invalidLinksByPage[$pageIndex][] = [
                        'tag' => $link->hreflang,
                        'url' => $link->url,
                    ];
                }

                if ($tagValid && $urlValid) {
                    /** @var string $hreflang */
                    $hreflang = $link->hreflang;
                    /** @var string $url */
                    $url = $link->url;
                    $usableLinksByPage[$pageIndex][] = [
                        'tag' => HreflangTagNormalizer::normalize($hreflang),
                        'url' => $url,
                    ];
                }
            }
        }

        foreach ($cluster->pages as $pageIndex => $page) {
            if (
                !$this->hasLinkTo($usableLinksByPage[$pageIndex], $page->pageUrl)
                && !$this->hasInvalidLinkTo($invalidLinksByPage[$pageIndex], $page->pageUrl)
            ) {
                $diagnostics[] = $this->diagnostic(
                    'hreflang_self_reference_missing',
                    'Each supplied hreflang page should include a usable alternate link to itself.',
                    'href',
                    'hreflang_page',
                    $pageIndex,
                );
            }
        }

        /** @var array<string, true> $reportedReciprocalPairs */
        $reportedReciprocalPairs = [];
        foreach ($cluster->pages as $pageIndex => $page) {
            foreach ($usableLinksByPage[$pageIndex] as $link) {
                $targetPageIndex = $pageIndexesByUrl[$link['url']] ?? null;
                if (
                    $targetPageIndex === null
                    || $this->hasLinkTo($usableLinksByPage[$targetPageIndex], $page->pageUrl)
                    || $this->hasInvalidLinkTo($invalidLinksByPage[$targetPageIndex], $page->pageUrl)
                ) {
                    continue;
                }

                $pairKey = $pageIndex . '|' . $targetPageIndex;
                if (isset($reportedReciprocalPairs[$pairKey])) {
                    continue;
                }
                $reportedReciprocalPairs[$pairKey] = true;

                $diagnostics[] = $this->diagnostic(
                    'hreflang_reciprocal_link_missing',
                    'A supplied hreflang relationship must have a usable return link from the target page.',
                    'href',
                    'hreflang_page',
                    $pageIndex,
                );
            }
        }

        $baseline = $this->alternateSet($usableLinksByPage[0] ?? []);
        foreach ($cluster->pages as $pageIndex => $_page) {
            $current = $this->alternateSet($usableLinksByPage[$pageIndex]);
            if ($current === $baseline) {
                continue;
            }

            if ($this->alternateSetMismatchIsExplainedByInvalidLinks(
                $baseline,
                $current,
                $invalidLinksByPage[0] ?? [],
                $invalidLinksByPage[$pageIndex],
            )) {
                continue;
            }

            $diagnostics[] = $this->diagnostic(
                'hreflang_alternate_set_inconsistent',
                'Supplied hreflang pages must represent the same usable alternate set.',
                'href',
                'hreflang_page',
                $pageIndex,
            );
        }

        return SitemapValidationSupport::result($diagnostics);
    }

    /** @param list<array{tag: string, url: string}> $links */
    private function hasLinkTo(array $links, string $url): bool
    {
        foreach ($links as $link) {
            if ($link['url'] === $url) {
                return true;
            }
        }

        return false;
    }

    /** @param list<array{tag: ?string, url: ?string}> $links */
    private function hasInvalidLinkTo(array $links, string $url): bool
    {
        foreach ($links as $link) {
            if ($link['url'] === $url) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<array{tag: string, url: string}> $links
     * @return array<string, array{tag: string, url: string}>
     */
    private function alternateSet(array $links): array
    {
        $set = [];
        foreach ($links as $link) {
            $key = strlen($link['tag']) . ':' . $link['tag'] . '|' . strlen($link['url']) . ':' . $link['url'];
            $set[$key] = $link;
        }
        ksort($set);

        return $set;
    }

    /**
     * @param array<string, array{tag: string, url: string}> $baseline
     * @param array<string, array{tag: string, url: string}> $current
     * @param list<array{tag: ?string, url: ?string}> $invalidBaseline
     * @param list<array{tag: ?string, url: ?string}> $invalidCurrent
     */
    private function alternateSetMismatchIsExplainedByInvalidLinks(
        array $baseline,
        array $current,
        array $invalidBaseline,
        array $invalidCurrent,
    ): bool {
        $missingFromCurrent = array_diff_key($baseline, $current);
        $missingFromBaseline = array_diff_key($current, $baseline);

        return $this->invalidLinksCoverExpectedSet($invalidCurrent, $missingFromCurrent)
            && $this->invalidLinksCoverExpectedSet($invalidBaseline, $missingFromBaseline);
    }

    /**
     * @param list<array{tag: ?string, url: ?string}> $invalidLinks
     * @param array<string, array{tag: string, url: string}> $expectedLinks
     */
    private function invalidLinksCoverExpectedSet(array $invalidLinks, array $expectedLinks): bool
    {
        foreach ($expectedLinks as $expectedLink) {
            $covered = false;
            foreach ($invalidLinks as $invalidLink) {
                if (
                    $invalidLink['tag'] !== null
                    && $invalidLink['url'] === $expectedLink['url']
                    && HreflangTagNormalizer::normalize($invalidLink['tag']) === $expectedLink['tag']
                ) {
                    $covered = true;
                    break;
                }
            }

            if (!$covered) {
                return false;
            }
        }

        return true;
    }

    private function diagnostic(
        string $code,
        string $message,
        string $field,
        string $targetScope,
        int $entryIndex,
        ?int $itemIndex = null,
    ): SeoCompanionDiagnosticDTO {
        return new SeoCompanionDiagnosticDTO(
            code: $code,
            severity: 'warning',
            message: $message,
            field: $field,
            origin: 'provider',
            profile: 'google',
            target: new SeoDiagnosticTargetDTO($targetScope, $entryIndex, $itemIndex),
        );
    }
}
