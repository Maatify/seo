<?php

declare(strict_types=1);

namespace Maatify\Seo\Admin\DTO;

final readonly class SeoMetadataImportResultDTO implements \JsonSerializable
{
    /** @param list<string> $errors */
    public function __construct(
        public int $created = 0,
        public int $updated = 0,
        public int $skipped = 0,
        public int $failed = 0,
        public array $errors = [],
        public bool $dryRun = false,
    ) {
    }

    /** @return array{created:int, updated:int, skipped:int, failed:int, errors:list<string>, dry_run:bool} */
    public function toArray(): array
    {
        return $this->jsonSerialize();
    }

    /** @return array{created:int, updated:int, skipped:int, failed:int, errors:list<string>, dry_run:bool} */
    public function jsonSerialize(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'failed' => $this->failed,
            'errors' => $this->errors,
            'dry_run' => $this->dryRun,
        ];
    }
}
