<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation\Profile\Internal;

final class RobotsTxtParser
{
    /**
     * @return list<array{name: string, value: string, line: int}>
     */
    public static function parse(string $content): array
    {
        $lines = preg_split('/\r\n|\n|\r/', $content);
        if ($lines === false) {
            return [];
        }

        $records = [];
        foreach ($lines as $lineIndex => $line) {
            $separator = strpos($line, ':');
            if ($separator === false) {
                continue;
            }

            $name = strtolower(trim(substr($line, 0, $separator)));
            if (!in_array($name, ['user-agent', 'allow', 'disallow', 'sitemap'], true)) {
                continue;
            }

            $value = substr($line, $separator + 1);
            if (in_array($name, ['user-agent', 'allow', 'disallow'], true)) {
                $commentStart = strpos($value, '#');
                if ($commentStart !== false) {
                    $value = substr($value, 0, $commentStart);
                }

                $value = trim($value, " \t");
            } else {
                // Preserve the existing Sitemap whitespace policy; RFC WS handling is scoped above.
                $value = trim($value, ' ');
            }

            $records[] = [
                'name' => $name,
                'value' => $value,
                'line' => $lineIndex + 1,
            ];
        }

        return $records;
    }
}
