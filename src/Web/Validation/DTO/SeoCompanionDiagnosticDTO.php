<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation\DTO;

use Maatify\Seo\Exception\SeoInvalidArgumentException;

final readonly class SeoCompanionDiagnosticDTO implements \JsonSerializable
{
    public string $code;
    public string $severity;
    public string $message;
    public ?string $field;
    public string $origin;
    public string $profile;
    public ?string $evidenceState;
    public ?string $relatedLegacyCode;
    public SeoDiagnosticTargetDTO $target;

    public function __construct(
        string $code,
        string $severity,
        string $message,
        ?string $field = null,
        string $origin = '',
        string $profile = '',
        ?string $evidenceState = null,
        ?string $relatedLegacyCode = null,
        ?SeoDiagnosticTargetDTO $target = null,
    ) {
        if ($code === '') {
            throw SeoInvalidArgumentException::emptyField('code');
        }

        if ($message === '') {
            throw SeoInvalidArgumentException::emptyField('message');
        }

        $contract = self::contracts()[$code] ?? null;
        if ($contract === null) {
            throw SeoInvalidArgumentException::invalidValue('code', 'Expected a code fixed by the companion machine contract.');
        }

        if ($contract['severity'] !== null && $severity !== $contract['severity']) {
            throw SeoInvalidArgumentException::invalidValue('severity', 'The severity does not match the fixed code contract.');
        }

        if (!in_array($severity, ['error', 'warning', 'info'], true)) {
            throw SeoInvalidArgumentException::invalidValue('severity', 'Expected error, warning, or info.');
        }

        if ($contract['fields'] === null) {
            if ($field !== null) {
                throw SeoInvalidArgumentException::invalidValue('field', 'This diagnostic is document-level and requires a null field.');
            }
        } elseif (!in_array($field, $contract['fields'], true)) {
            throw SeoInvalidArgumentException::invalidValue('field', 'The field does not match the fixed code contract.');
        }

        if ($origin !== $contract['origin']) {
            throw SeoInvalidArgumentException::invalidValue('origin', 'The origin does not match the fixed code contract.');
        }

        if ($profile !== $contract['profile']) {
            throw SeoInvalidArgumentException::invalidValue('profile', 'The profile does not match the fixed code contract.');
        }

        if ($contract['states'] === null) {
            if ($evidenceState !== null) {
                throw SeoInvalidArgumentException::invalidValue('evidenceState', 'This diagnostic does not authorize an evidence state.');
            }
        } else {
            if ($evidenceState === null || !array_key_exists($evidenceState, $contract['states'])) {
                throw SeoInvalidArgumentException::invalidValue('evidenceState', 'The evidence state is not authorized for this code.');
            }

            if ($severity !== $contract['states'][$evidenceState]) {
                throw SeoInvalidArgumentException::invalidValue('severity', 'The severity does not match the evidence-state contract.');
            }
        }

        if ($target === null) {
            throw SeoInvalidArgumentException::invalidValue('target', 'A non-null diagnostic target is required.');
        }

        if (!in_array($target->scope, $contract['targetScopes'], true)) {
            throw SeoInvalidArgumentException::invalidValue('target', 'The target scope does not match the fixed code contract.');
        }

        self::assertConditionalTarget($code, $field, $target);

        if ($contract['relatedLegacyCode'] === null && $relatedLegacyCode !== null) {
            throw SeoInvalidArgumentException::invalidValue('relatedLegacyCode', 'New diagnostics do not authorize legacy correlation metadata.');
        }

        if ($contract['relatedLegacyCode'] === 'same_as_code' && $relatedLegacyCode !== $code) {
            throw SeoInvalidArgumentException::invalidValue('relatedLegacyCode', 'A legacy classification must reference its own legacy code.');
        }

        $this->code = $code;
        $this->severity = $severity;
        $this->message = $message;
        $this->field = $field;
        $this->origin = $origin;
        $this->profile = $profile;
        $this->evidenceState = $evidenceState;
        $this->relatedLegacyCode = $relatedLegacyCode;
        $this->target = $target;
    }

    /** @return array{code: string, severity: string, message: string, field: string|null, origin: string, profile: string, evidence_state: string|null, related_legacy_code: string|null, target: array{scope: string, entry_index: int|null, item_index: int|null, line: int|null}} */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'severity' => $this->severity,
            'message' => $this->message,
            'field' => $this->field,
            'origin' => $this->origin,
            'profile' => $this->profile,
            'evidence_state' => $this->evidenceState,
            'related_legacy_code' => $this->relatedLegacyCode,
            'target' => $this->target->toArray(),
        ];
    }

    /** @return array{code: string, severity: string, message: string, field: string|null, origin: string, profile: string, evidence_state: string|null, related_legacy_code: string|null, target: array{scope: string, entry_index: int|null, item_index: int|null, line: int|null}} */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @param list<string>|null $fields
     * @param list<string> $targetScopes
     * @return array{severity: string|null, origin: string, profile: string, fields: list<string>|null, states: array<string, string>|null, targetScopes: list<string>, relatedLegacyCode: string|null}
     */
    private static function ordinary(
        string $severity,
        string $origin,
        string $profile,
        ?array $fields,
        array $targetScopes,
        ?string $relatedLegacyCode = null,
    ): array {
        return [
            'severity' => $severity,
            'origin' => $origin,
            'profile' => $profile,
            'fields' => $fields,
            'states' => null,
            'targetScopes' => $targetScopes,
            'relatedLegacyCode' => $relatedLegacyCode,
        ];
    }

    /**
     * @param array<string, string> $states
     * @param list<string>|null $fields
     * @param list<string> $targetScopes
     * @return array{severity: string|null, origin: string, profile: string, fields: list<string>|null, states: array<string, string>|null, targetScopes: list<string>, relatedLegacyCode: string|null}
     */
    private static function evidence(
        string $origin,
        string $profile,
        ?array $fields,
        array $states,
        array $targetScopes,
    ): array {
        return [
            'severity' => null,
            'origin' => $origin,
            'profile' => $profile,
            'fields' => $fields,
            'states' => $states,
            'targetScopes' => $targetScopes,
            'relatedLegacyCode' => null,
        ];
    }

    /**
     * This private registry mirrors the Audit's complete machine-contract table.
     * It is deliberately not exposed as a public extension or lookup API.
     *
     * @return array<string, array{severity: string|null, origin: string, profile: string, fields: list<string>|null, states: array<string, string>|null, targetScopes: list<string>, relatedLegacyCode: string|null}>
     */
    private static function contracts(): array
    {
        $robotsDocument = ['robots_document'];
        $robotsRule = ['robots_rule'];
        $robotsMeta = ['robots_meta'];
        $sitemapDocument = ['sitemap_document'];
        $sitemapUrl = ['sitemap_url'];
        $sitemapIndex = ['sitemap_index_entry'];
        $sitemapEntry = ['sitemap_url', 'sitemap_index_entry'];
        $sitemapImage = ['sitemap_image'];
        $sitemapVideo = ['sitemap_video'];
        $sitemapNews = ['sitemap_news'];
        $meta = ['meta'];
        $canonical = ['canonical'];
        $hreflangPage = ['hreflang_page'];
        $hreflangLink = ['hreflang_link'];

        return [
            'robots_google_document_size_exceeds_parse_limit' => self::ordinary('warning', 'provider', 'google', ['document'], $robotsDocument),
            'robots_google_document_invalid_utf8' => self::ordinary('warning', 'provider', 'google', ['document'], $robotsDocument),
            'robots_google_present_path_leading_slash' => self::ordinary('warning', 'provider', 'google', ['path'], $robotsRule),
            'robots_google_sitemap_url_not_fully_qualified' => self::ordinary('warning', 'provider', 'google', ['sitemap'], $robotsRule),
            'robots_rfc9309_leading_wildcard_compatibility' => self::ordinary('warning', 'protocol', 'rfc9309', ['path'], $robotsRule),
            'robots_rfc9309_product_token_invalid' => self::ordinary('error', 'protocol', 'rfc9309', ['user_agent'], $robotsRule),
            'robots_rfc9309_path_pattern_invalid' => self::ordinary('error', 'protocol', 'rfc9309', ['path'], $robotsRule),
            'robots_rfc9309_control_character_invalid' => self::ordinary('error', 'protocol', 'rfc9309', ['user_agent', 'path'], $robotsRule),
            'robots_meta_indexifembedded_without_noindex' => self::ordinary('warning', 'provider', 'google', ['meta'], $robotsMeta),
            'robots_meta_unavailable_after_missing' => self::ordinary('warning', 'provider', 'google', ['meta'], $robotsMeta),
            'robots_meta_unavailable_after_recognizability' => self::evidence('provider', 'google', ['meta'], ['recognized' => 'info', 'unrecognized' => 'warning', 'unknown' => 'info'], $robotsMeta),

            'sitemap_url_count_exceeds_limit' => self::ordinary('error', 'protocol', 'sitemaps', ['urlset'], $sitemapDocument),
            'sitemap_index_count_exceeds_limit' => self::ordinary('error', 'protocol', 'sitemaps', ['sitemapindex'], $sitemapDocument),
            'sitemap_document_size_exceeds_boundary' => self::ordinary('error', 'protocol', 'sitemaps', null, $sitemapDocument),
            'sitemap_loc_missing' => self::ordinary('error', 'protocol', 'sitemaps', ['loc', 'sitemap'], $sitemapEntry),
            'sitemap_loc_invalid_uri_iri' => self::ordinary('error', 'protocol', 'sitemaps', ['loc', 'sitemap'], $sitemapEntry),
            'sitemap_lastmod_invalid_lexical' => self::ordinary('error', 'protocol', 'sitemaps', ['lastmod'], $sitemapEntry),
            'sitemap_changefreq_invalid' => self::ordinary('error', 'protocol', 'sitemaps', ['changefreq'], $sitemapUrl),
            'sitemap_priority_out_of_range' => self::ordinary('error', 'protocol', 'sitemaps', ['priority'], $sitemapUrl),
            'sitemap_loc_length_exceeds_measure_boundary' => self::ordinary('warning', 'protocol', 'sitemaps', ['loc'], $sitemapUrl),
            'sitemap_location_scope_violation' => self::ordinary('error', 'protocol', 'sitemaps', ['loc', 'sitemap'], $sitemapEntry),

            'google_sitemap_priority_ignored' => self::ordinary('info', 'provider', 'google', ['priority'], $sitemapUrl),
            'google_sitemap_changefreq_ignored' => self::ordinary('info', 'provider', 'google', ['changefreq'], $sitemapUrl),
            'google_sitemap_lastmod_accuracy' => self::evidence('provider', 'google', ['lastmod'], ['accurate' => 'info', 'inaccurate' => 'warning', 'unknown' => 'info'], $sitemapUrl),
            'google_sitemap_host_context' => self::evidence('provider', 'google', null, ['verified_host' => 'info', 'unverified_host' => 'warning', 'unknown' => 'info'], $sitemapDocument),

            'google_image_count_exceeds_limit' => self::ordinary('warning', 'provider', 'google', ['image'], $sitemapUrl),
            'google_image_cross_domain_verification' => self::evidence('provider', 'google', ['image'], ['verified' => 'info', 'unverified' => 'warning', 'unknown' => 'info'], $sitemapImage),
            'google_image_crawlability_context' => self::evidence('provider', 'google', ['image'], ['accessible' => 'info', 'inaccessible' => 'warning', 'unknown' => 'info'], $sitemapImage),

            'google_video_title_missing' => self::ordinary('warning', 'provider', 'google', ['title'], $sitemapVideo),
            'google_video_description_missing' => self::ordinary('warning', 'provider', 'google', ['description'], $sitemapVideo),
            'google_video_thumbnail_loc_missing' => self::ordinary('warning', 'provider', 'google', ['thumbnail_loc'], $sitemapVideo),
            'google_video_thumbnail_loc_invalid_url' => self::ordinary('warning', 'provider', 'google', ['thumbnail_loc'], $sitemapVideo),
            'google_video_content_loc_invalid_url' => self::ordinary('warning', 'provider', 'google', ['content_loc'], $sitemapVideo),
            'google_video_player_loc_invalid_url' => self::ordinary('warning', 'provider', 'google', ['player_loc'], $sitemapVideo),
            'google_video_content_or_player_loc_missing' => self::ordinary('warning', 'provider', 'google', ['content_loc'], $sitemapVideo),
            'google_video_description_length_exceeds_measure_boundary' => self::ordinary('warning', 'provider', 'google', ['description'], $sitemapVideo),
            'google_video_duration_out_of_range' => self::ordinary('warning', 'provider', 'google', ['duration'], $sitemapVideo),
            'google_video_publication_date_invalid' => self::ordinary('warning', 'provider', 'google', ['publication_date'], $sitemapVideo),
            'google_video_media_loc_equals_parent_loc' => self::ordinary('warning', 'provider', 'google', ['content_loc', 'player_loc'], $sitemapVideo),
            'google_video_data_url_unsupported' => self::ordinary('warning', 'provider', 'google', ['content_loc', 'player_loc'], $sitemapVideo),
            'google_video_relevance_context' => self::evidence('provider', 'google', null, ['relevant' => 'info', 'irrelevant' => 'warning', 'unknown' => 'info'], $sitemapVideo),
            'google_video_title_host_page_match' => self::evidence('provider', 'google', ['title'], ['matches' => 'info', 'differs' => 'warning', 'unknown' => 'info'], $sitemapVideo),
            'google_video_description_host_page_match' => self::evidence('provider', 'google', ['description'], ['matches' => 'info', 'differs' => 'warning', 'unknown' => 'info'], $sitemapVideo),
            'google_video_content_loc_preference' => self::ordinary('info', 'provider', 'google', ['content_loc'], $sitemapVideo),

            'google_news_multiple_entries_per_url' => self::ordinary('warning', 'provider', 'google', ['news'], $sitemapUrl),
            'google_news_document_count_exceeds_limit' => self::ordinary('warning', 'provider', 'google', ['news'], $sitemapDocument),
            'google_news_publication_name_missing' => self::ordinary('warning', 'provider', 'google', ['name'], $sitemapNews),
            'google_news_language_missing' => self::ordinary('warning', 'provider', 'google', ['language'], $sitemapNews),
            'google_news_publication_date_missing' => self::ordinary('warning', 'provider', 'google', ['publication_date'], $sitemapNews),
            'google_news_title_missing' => self::ordinary('warning', 'provider', 'google', ['title'], $sitemapNews),
            'google_news_publication_date_invalid' => self::ordinary('warning', 'provider', 'google', ['publication_date'], $sitemapNews),
            'google_news_language_invalid' => self::ordinary('warning', 'provider', 'google', ['language'], $sitemapNews),
            'google_news_publication_name_parenthetical' => self::ordinary('warning', 'provider', 'google', ['name'], $sitemapNews),
            'google_news_title_content_evidence' => self::evidence('provider', 'google', ['title'], ['conforming' => 'info', 'nonconforming' => 'warning', 'unknown' => 'info'], $sitemapNews),
            'google_news_original_publication_evidence' => self::evidence('provider', 'google', ['publication_date'], ['original' => 'info', 'not_original' => 'warning', 'unknown' => 'info'], $sitemapNews),
            'google_news_name_exact_match_evidence' => self::evidence('provider', 'google', ['name'], ['matched' => 'info', 'mismatched' => 'warning', 'unknown' => 'info'], $sitemapNews),
            'google_news_freshness_evidence' => self::evidence('provider', 'google', null, ['within_window' => 'info', 'outside_window' => 'warning', 'unknown' => 'info'], $sitemapNews),

            'canonical_relative_provider_best_practice' => self::ordinary('warning', 'provider', 'google', ['href'], $canonical),
            'hreflang_self_reference_missing' => self::ordinary('warning', 'provider', 'google', ['href'], $hreflangPage),
            'hreflang_reciprocal_link_missing' => self::ordinary('warning', 'provider', 'google', ['href'], $hreflangPage),
            'hreflang_alternate_set_inconsistent' => self::ordinary('warning', 'provider', 'google', ['href'], $hreflangPage),
            'hreflang_url_not_fully_qualified' => self::ordinary('warning', 'provider', 'google', ['href'], $hreflangLink),
            'hreflang_tag_invalid_syntax' => self::ordinary('warning', 'provider', 'google', ['hreflang'], $hreflangLink),

            'missing_og_type' => self::ordinary('warning', 'protocol', 'ogp', ['og:type'], $meta),
            'missing_og_url' => self::ordinary('warning', 'protocol', 'ogp', ['og:url'], $meta),
            'title_too_short' => self::ordinary('warning', 'heuristic', 'seo-default', ['title'], $meta, 'same_as_code'),
            'title_too_long' => self::ordinary('warning', 'heuristic', 'seo-default', ['title'], $meta, 'same_as_code'),
            'description_too_short' => self::ordinary('warning', 'heuristic', 'seo-default', ['description'], $meta, 'same_as_code'),
            'description_too_long' => self::ordinary('warning', 'heuristic', 'seo-default', ['description'], $meta, 'same_as_code'),
            'missing_og_title' => self::ordinary('warning', 'protocol', 'ogp', ['og:title'], $meta, 'same_as_code'),
            'missing_og_description' => self::ordinary('warning', 'heuristic', 'seo-default', ['og:description'], $meta, 'same_as_code'),
            'missing_og_image' => self::ordinary('warning', 'protocol', 'ogp', ['og:image'], $meta, 'same_as_code'),
        ];
    }

    private static function assertConditionalTarget(string $code, ?string $field, SeoDiagnosticTargetDTO $target): void
    {
        if (in_array($code, ['sitemap_loc_missing', 'sitemap_loc_invalid_uri_iri', 'sitemap_location_scope_violation'], true)) {
            $expectedScope = $field === 'sitemap' ? 'sitemap_index_entry' : 'sitemap_url';
            if ($target->scope !== $expectedScope) {
                throw SeoInvalidArgumentException::invalidValue('target', 'The target scope must match the diagnostic field.');
            }
        }

        if ($code === 'title_too_short' || $code === 'title_too_long' || $code === 'description_too_short' || $code === 'description_too_long' || str_starts_with($code, 'missing_og_')) {
            if ($target->scope !== 'meta') {
                throw SeoInvalidArgumentException::invalidValue('target', 'Legacy classification records must target meta.');
            }
        }
    }

}
