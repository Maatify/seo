<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation;

use Maatify\Seo\Exception\SeoInvalidArgumentException;

final class SeoValidationPreset
{
    /**
     * @return array{validationOptions: array{requireCanonical: bool}, scoreOptions: array{errorPenalty: int, warningPenalty: int, infoPenalty: int, healthyMinimumScore: int}}
     */
    public static function minimal(): array
    {
        return [
            'validationOptions' => [
                'requireCanonical' => false,
            ],
            'scoreOptions' => self::defaultScoreOptions(),
        ];
    }

    /**
     * @return array{validationOptions: array{requireCanonical: bool, titleMinLength: int, titleMaxLength: int, descriptionMinLength: int, descriptionMaxLength: int}, scoreOptions: array{errorPenalty: int, warningPenalty: int, infoPenalty: int, healthyMinimumScore: int}}
     */
    public static function standard(): array
    {
        return [
            'validationOptions' => [
                'requireCanonical' => true,
                'titleMinLength' => 10,
                'titleMaxLength' => 60,
                'descriptionMinLength' => 50,
                'descriptionMaxLength' => 160,
            ],
            'scoreOptions' => self::defaultScoreOptions(),
        ];
    }

    /**
     * @return array{validationOptions: array{requireCanonical: bool, titleMinLength: int, titleMaxLength: int, descriptionMinLength: int, descriptionMaxLength: int}, scoreOptions: array{errorPenalty: int, warningPenalty: int, infoPenalty: int, healthyMinimumScore: int}}
     */
    public static function strict(): array
    {
        return [
            'validationOptions' => [
                'requireCanonical' => true,
                'titleMinLength' => 20,
                'titleMaxLength' => 60,
                'descriptionMinLength' => 80,
                'descriptionMaxLength' => 155,
            ],
            'scoreOptions' => [
                'errorPenalty' => 30,
                'warningPenalty' => 10,
                'infoPenalty' => 0,
                'healthyMinimumScore' => 90,
            ],
        ];
    }

    /**
     * @return array{validationOptions: array<string, bool|int>, scoreOptions: array{errorPenalty: int, warningPenalty: int, infoPenalty: int, healthyMinimumScore: int}}
     */
    public static function for(string $preset): array
    {
        return match ($preset) {
            'minimal' => self::minimal(),
            'standard' => self::standard(),
            'strict' => self::strict(),
            default => throw SeoInvalidArgumentException::invalidValue('preset', 'Expected minimal, standard, or strict.'),
        };
    }

    /**
     * @return array{errorPenalty: int, warningPenalty: int, infoPenalty: int, healthyMinimumScore: int}
     */
    private static function defaultScoreOptions(): array
    {
        return [
            'errorPenalty' => 25,
            'warningPenalty' => 5,
            'infoPenalty' => 0,
            'healthyMinimumScore' => 80,
        ];
    }
}
