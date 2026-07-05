<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\DTO\Redirect;

use Maatify\Seo\Shared\DTO\RedirectDTO;

final readonly class RedirectDecisionDTO implements \JsonSerializable
{
    public function __construct(
        public bool $shouldRedirect,
        public ?int $httpStatus,
        public ?string $targetEntityType,
        public ?string $targetEntityId,
        public ?string $targetUrl,
        public ?RedirectDTO $redirect,
    ) {
    }

    public static function noRedirect(): self
    {
        return new self(false, null, null, null, null, null);
    }

    public static function gone(RedirectDTO $redirect): self
    {
        return new self(true, 410, null, null, null, $redirect);
    }

    public static function permanent(RedirectDTO $redirect, ?string $targetUrl): self
    {
        return new self(true, 301, $redirect->targetEntityType, $redirect->targetEntityId, $targetUrl, $redirect);
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'should_redirect' => $this->shouldRedirect,
            'http_status' => $this->httpStatus,
            'target_entity_type' => $this->targetEntityType,
            'target_entity_id' => $this->targetEntityId,
            'target_url' => $this->targetUrl,
            'redirect' => $this->redirect?->jsonSerialize(),
        ];
    }
}
