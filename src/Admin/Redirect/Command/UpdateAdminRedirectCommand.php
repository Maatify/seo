<?php

declare(strict_types=1);

namespace Maatify\Seo\Admin\Redirect\Command;

use Maatify\Seo\Exception\SeoInvalidArgumentException;

final readonly class UpdateAdminRedirectCommand
{
    public function __construct(public int $id, public ?string $targetEntityType, public ?string $targetEntityId, public int $httpStatus)
    {
        if ($this->id < 1) throw SeoInvalidArgumentException::invalidId('id');
        if ($this->httpStatus !== 301 && $this->httpStatus !== 410) throw SeoInvalidArgumentException::invalidHttpStatus($this->httpStatus);
        if ($this->httpStatus === 301 && ($this->targetEntityType === null || trim($this->targetEntityType) === '')) throw SeoInvalidArgumentException::emptyField('targetEntityType');
        if ($this->httpStatus === 301 && ($this->targetEntityId === null || trim($this->targetEntityId) === '')) throw SeoInvalidArgumentException::emptyField('targetEntityId');
    }
}
