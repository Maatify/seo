<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\Contract;

interface HostSearchContextInterface
{
    public function getInternalSearchUrlTemplate(): ?string;
}
