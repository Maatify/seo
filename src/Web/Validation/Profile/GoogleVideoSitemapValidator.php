<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation\Profile;

use Maatify\Seo\Web\Validation\DTO\SeoCompanionValidationResultDTO;
use Maatify\Seo\Web\Validation\DTO\SeoValidationContextDTO;
use Maatify\Seo\Web\Validation\Input\Sitemap\SitemapUrlValidationInputDTO;
use Maatify\Seo\Web\Validation\Input\Sitemap\SitemapValidationDocumentDTO;
use Maatify\Seo\Web\Validation\Input\Sitemap\SitemapVideoValidationInputDTO;
use Maatify\Seo\Web\Validation\Profile\Internal\SitemapValidationSupport;

final class GoogleVideoSitemapValidator
{
    public function validate(
        SitemapValidationDocumentDTO $document,
        ?SeoValidationContextDTO $context = null,
    ): SeoCompanionValidationResultDTO {
        $diagnostics = [];
        if ($document->type !== 'urlset') {
            return SitemapValidationSupport::result($diagnostics);
        }

        foreach ($document->entries as $entryIndex => $entry) {
            if (!$entry instanceof SitemapUrlValidationInputDTO) {
                continue;
            }

            foreach ($entry->videos as $videoIndex => $video) {
                $this->validateVideo($video, $entry, $entryIndex, $videoIndex, $context, $diagnostics);
            }
        }

        return SitemapValidationSupport::result($diagnostics);
    }

    /** @param list<\Maatify\Seo\Web\Validation\DTO\SeoCompanionDiagnosticDTO> $diagnostics */
    private function validateVideo(
        SitemapVideoValidationInputDTO $video,
        SitemapUrlValidationInputDTO $parent,
        int $entryIndex,
        int $videoIndex,
        ?SeoValidationContextDTO $context,
        array &$diagnostics,
    ): void {
        if (SitemapValidationSupport::isMissing($video->title)) {
            $diagnostics[] = SitemapValidationSupport::diagnostic(
                'google_video_title_missing',
                'warning',
                'A Google Video entry requires a non-empty title.',
                'title',
                'sitemap_video',
                $entryIndex,
                $videoIndex,
            );
        }

        $description = $video->description;
        if (SitemapValidationSupport::isMissing($description)) {
            $diagnostics[] = SitemapValidationSupport::diagnostic(
                'google_video_description_missing',
                'warning',
                'A Google Video entry requires a non-empty description.',
                'description',
                'sitemap_video',
                $entryIndex,
                $videoIndex,
            );
        } elseif ($description !== null && strlen($description) > 2048) {
            $diagnostics[] = SitemapValidationSupport::diagnostic(
                'google_video_description_length_exceeds_measure_boundary',
                'warning',
                'The supplied description exceeds the conservative 2,048-byte measurement boundary.',
                'description',
                'sitemap_video',
                $entryIndex,
                $videoIndex,
            );
        }

        if (SitemapValidationSupport::isMissing($video->thumbnailLoc)) {
            $diagnostics[] = SitemapValidationSupport::diagnostic(
                'google_video_thumbnail_loc_missing',
                'warning',
                'A Google Video entry requires a non-empty thumbnail location.',
                'thumbnail_loc',
                'sitemap_video',
                $entryIndex,
                $videoIndex,
            );
        } elseif (!SitemapValidationSupport::isAbsoluteUrl($video->thumbnailLoc, true)) {
            $diagnostics[] = SitemapValidationSupport::diagnostic(
                'google_video_thumbnail_loc_invalid_url',
                'warning',
                'The thumbnail location is not a valid absolute URL under the Google Video lexical profile.',
                'thumbnail_loc',
                'sitemap_video',
                $entryIndex,
                $videoIndex,
            );
        }

        $contentMissing = SitemapValidationSupport::isMissing($video->contentLoc);
        $playerMissing = SitemapValidationSupport::isMissing($video->playerLoc);
        if ($contentMissing && $playerMissing) {
            $diagnostics[] = SitemapValidationSupport::diagnostic(
                'google_video_content_or_player_loc_missing',
                'warning',
                'A Google Video entry requires a content or player location.',
                'content_loc',
                'sitemap_video',
                $entryIndex,
                $videoIndex,
            );
        }

        $contentUsable = $this->validateMediaLocation(
            $video->contentLoc,
            'content_loc',
            'google_video_content_loc_invalid_url',
            $entryIndex,
            $videoIndex,
            $diagnostics,
        );
        $playerUsable = $this->validateMediaLocation(
            $video->playerLoc,
            'player_loc',
            'google_video_player_loc_invalid_url',
            $entryIndex,
            $videoIndex,
            $diagnostics,
        );

        if (!$contentMissing && $this->isDataUrl($video->contentLoc)) {
            $diagnostics[] = SitemapValidationSupport::diagnostic(
                'google_video_data_url_unsupported',
                'warning',
                'Google does not support a data URL for video content.',
                'content_loc',
                'sitemap_video',
                $entryIndex,
                $videoIndex,
            );
            $contentUsable = false;
        }

        if (!$playerMissing && $this->isDataUrl($video->playerLoc)) {
            $diagnostics[] = SitemapValidationSupport::diagnostic(
                'google_video_data_url_unsupported',
                'warning',
                'Google does not support a data URL for a video player.',
                'player_loc',
                'sitemap_video',
                $entryIndex,
                $videoIndex,
            );
            $playerUsable = false;
        }

        if ($contentMissing && !$playerMissing) {
            $diagnostics[] = SitemapValidationSupport::diagnostic(
                'google_video_content_loc_preference',
                'info',
                'Google recommends content_loc when a direct media location is available.',
                'content_loc',
                'sitemap_video',
                $entryIndex,
                $videoIndex,
            );
        }

        if ($contentUsable && $video->contentLoc === $this->nonEmptyValue($parent->loc)) {
            $diagnostics[] = SitemapValidationSupport::diagnostic(
                'google_video_media_loc_equals_parent_loc',
                'warning',
                'The content location must not equal the parent Sitemap page location.',
                'content_loc',
                'sitemap_video',
                $entryIndex,
                $videoIndex,
            );
        }

        if ($playerUsable && $video->playerLoc === $this->nonEmptyValue($parent->loc)) {
            $diagnostics[] = SitemapValidationSupport::diagnostic(
                'google_video_media_loc_equals_parent_loc',
                'warning',
                'The player location must not equal the parent Sitemap page location.',
                'player_loc',
                'sitemap_video',
                $entryIndex,
                $videoIndex,
            );
        }

        if ($video->duration !== null && ($video->duration < 1 || $video->duration > 28800)) {
            $diagnostics[] = SitemapValidationSupport::diagnostic(
                'google_video_duration_out_of_range',
                'warning',
                'Video duration must be within the inclusive 1..28,800 second range.',
                'duration',
                'sitemap_video',
                $entryIndex,
                $videoIndex,
            );
        }

        if ($video->publicationDate !== null
            && trim($video->publicationDate) !== ''
            && !SitemapValidationSupport::isVideoPublicationDate($video->publicationDate)
        ) {
            $diagnostics[] = SitemapValidationSupport::diagnostic(
                'google_video_publication_date_invalid',
                'warning',
                'The video publication date is outside the fixed Google Video lexical contract.',
                'publication_date',
                'sitemap_video',
                $entryIndex,
                $videoIndex,
            );
        }

        $relevanceState = SitemapValidationSupport::indexedEvidence($context, 'google_video.relevance', $entryIndex, $videoIndex);
        $diagnostics[] = SitemapValidationSupport::diagnostic(
            'google_video_relevance_context',
            $relevanceState === 'irrelevant' ? 'warning' : 'info',
            'Video relevance is represented only by caller-supplied evidence.',
            null,
            'sitemap_video',
            $entryIndex,
            $videoIndex,
            $relevanceState,
        );

        if (!SitemapValidationSupport::isMissing($video->title)) {
            $titleState = SitemapValidationSupport::indexedEvidence($context, 'google_video.title_host_page_match', $entryIndex, $videoIndex);
            $diagnostics[] = SitemapValidationSupport::diagnostic(
                'google_video_title_host_page_match',
                $titleState === 'differs' ? 'warning' : 'info',
                'Video title host-page consistency is represented only by caller-supplied evidence.',
                'title',
                'sitemap_video',
                $entryIndex,
                $videoIndex,
                $titleState,
            );
        }

        if (!SitemapValidationSupport::isMissing($video->description)) {
            $descriptionState = SitemapValidationSupport::indexedEvidence($context, 'google_video.description_host_page_match', $entryIndex, $videoIndex);
            $diagnostics[] = SitemapValidationSupport::diagnostic(
                'google_video_description_host_page_match',
                $descriptionState === 'differs' ? 'warning' : 'info',
                'Video description host-page consistency is represented only by caller-supplied evidence.',
                'description',
                'sitemap_video',
                $entryIndex,
                $videoIndex,
                $descriptionState,
            );
        }
    }

    /** @param list<\Maatify\Seo\Web\Validation\DTO\SeoCompanionDiagnosticDTO> $diagnostics */
    private function validateMediaLocation(
        ?string $value,
        string $field,
        string $invalidCode,
        int $entryIndex,
        int $videoIndex,
        array &$diagnostics,
    ): bool {
        if (SitemapValidationSupport::isMissing($value) || $this->isDataUrl($value)) {
            return false;
        }

        $valid = SitemapValidationSupport::isAbsoluteUrl($value, true);
        if (!$valid) {
            $diagnostics[] = SitemapValidationSupport::diagnostic(
                $invalidCode,
                'warning',
                "The {$field} value is not a valid absolute URL under the Google Video lexical profile.",
                $field,
                'sitemap_video',
                $entryIndex,
                $videoIndex,
            );
        }

        return $valid;
    }

    private function isDataUrl(?string $value): bool
    {
        return $value !== null && strncasecmp($value, 'data:', 5) === 0;
    }

    private function nonEmptyValue(?string $value): ?string
    {
        return SitemapValidationSupport::isMissing($value) ? null : $value;
    }
}
