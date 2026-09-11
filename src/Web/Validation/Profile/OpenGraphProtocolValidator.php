<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation\Profile;

use Maatify\Seo\Web\Validation\DTO\SeoCompanionValidationResultDTO;
use Maatify\Seo\Web\Validation\DTO\SeoValidationContextDTO;
use Maatify\Seo\Web\Validation\Profile\Internal\OpenGraphValidationSupport;
use Maatify\Seo\Web\Validation\SeoMetaValidator;

final class OpenGraphProtocolValidator
{
    /**
     * @param array<string, mixed>|object $meta
     * @param array<string, mixed> $options
     */
    public function validate(
        array|object $meta,
        array $options = [],
        ?SeoValidationContextDTO $context = null,
    ): SeoCompanionValidationResultDTO {
        $legacy = SeoMetaValidator::validate($meta, $options);

        return OpenGraphValidationSupport::build($meta, $legacy, $context);
    }
}
