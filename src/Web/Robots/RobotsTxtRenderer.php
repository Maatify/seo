<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Robots;

use Maatify\Seo\Web\Robots\DTO\RobotsRuleDTO;
use Maatify\Seo\Web\Robots\DTO\RobotsTxtDTO;

final class RobotsTxtRenderer
{
    public function render(RobotsTxtDTO $robotsTxt): string
    {
        $lines = [];

        foreach ($robotsTxt->comments as $comment) {
            $comment = trim($comment);
            if ($comment !== '') {
                $lines[] = "# {$comment}";
            }
        }

        if (count($lines) > 0 && (count($robotsTxt->rules) > 0 || count($robotsTxt->sitemaps) > 0)) {
            $lines[] = '';
        }

        $ruleBlocks = [];
        foreach ($robotsTxt->rules as $rule) {
            $ruleBlocks[] = $this->renderRule($rule);
        }

        if (count($ruleBlocks) > 0) {
            $lines[] = implode("\n\n", $ruleBlocks);
        }

        if (count($robotsTxt->sitemaps) > 0) {
            if (count($lines) > 0 && end($lines) !== '') {
                $lines[] = '';
            }
            foreach ($robotsTxt->sitemaps as $sitemap) {
                $lines[] = "Sitemap: {$sitemap}";
            }
        }

        return trim(implode("\n", $lines)) . "\n";
    }

    public function renderRule(RobotsRuleDTO $rule): string
    {
        $lines = [];

        foreach ($rule->comments as $comment) {
            $comment = trim($comment);
            if ($comment !== '') {
                $lines[] = "# {$comment}";
            }
        }

        $lines[] = "User-agent: {$rule->userAgent}";

        if ($rule->crawlDelay !== null) {
            $lines[] = "Crawl-delay: {$rule->crawlDelay}";
        }

        foreach ($rule->allow as $allow) {
            $lines[] = "Allow: {$allow}";
        }

        foreach ($rule->disallow as $disallow) {
            $lines[] = "Disallow: {$disallow}";
        }

        return implode("\n", $lines);
    }
}
