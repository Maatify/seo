<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation\DTO;

use Maatify\Seo\Exception\SeoInvalidArgumentException;

final readonly class SeoValidationIssueDTO implements \JsonSerializable
{
    public const SEVERITY_ERROR = 'error';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_INFO = 'info';

    public function __construct(
        public string $code,
        public string $severity,
        public string $message,
        public ?string $field = null,
    ) {
        if ($this->code === '') {
            throw SeoInvalidArgumentException::emptyField('code');
        }

        if (!in_array($this->severity, [self::SEVERITY_ERROR, self::SEVERITY_WARNING, self::SEVERITY_INFO], true)) {
            throw SeoInvalidArgumentException::invalidValue('severity', 'Expected error, warning, or info.');
        }

        if ($this->message === '') {
            throw SeoInvalidArgumentException::emptyField('message');
        }
    }

    /**
     * @return array{code: string, severity: string, message: string, field: string|null}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @return array{code: string, severity: string, message: string, field: string|null}
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'severity' => $this->severity,
            'message' => $this->message,
            'field' => $this->field,
        ];
    }
}
