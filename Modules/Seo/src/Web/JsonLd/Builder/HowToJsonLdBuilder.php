<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\JsonLd\Builder;

final class HowToJsonLdBuilder extends AbstractJsonLdBuilder
{
    public function __construct()
    {
        parent::__construct([
            '@context' => 'https://schema.org',
            '@type' => 'HowTo',
            'step' => [],
        ]);
    }

    public function setName(string $name): static { return $this->set('name', $name); }
    public function setDescription(string $description): static { return $this->set('description', $description); }
    /** @param string|array<int|string, mixed> $image */
    public function setImage(string|array $image): static { return $this->set('image', $image); }
    public function setTotalTime(string $totalTime): static { return $this->set('totalTime', $totalTime); }
    /** @param string|array<string, mixed> $estimatedCost */
    public function setEstimatedCost(string|array $estimatedCost): static { return $this->set('estimatedCost', $estimatedCost); }
    /** @param string|array<string, mixed> $supply */
    public function addSupply(string|array $supply): static { return $this->appendTypedValue('supply', $supply, 'HowToSupply'); }
    /** @param string|array<string, mixed> $tool */
    public function addTool(string|array $tool): static { return $this->appendTypedValue('tool', $tool, 'HowToTool'); }

    /** @param string|array<int|string, mixed>|null $image */
    public function addStep(string $name, ?string $text = null, ?string $url = null, string|array|null $image = null): static
    {
        $step = ['@type' => 'HowToStep', 'name' => $name];
        if ($text !== null) { $step['text'] = $text; }
        if ($url !== null) { $step['url'] = $url; }
        if ($image !== null) { $step['image'] = $image; }

        $steps = $this->get('step');
        if (!is_array($steps)) { $steps = []; }
        $steps[] = $step;

        return $this->setSteps($steps);
    }

    /** @param array<int, array<string, mixed>> $steps */
    public function setSteps(array $steps): static
    {
        return $this->set('step', array_map([$this, 'normalizeStep'], array_values($steps)));
    }

    public function clearSteps(): static { return $this->set('step', []); }

    /** @param string|array<string, mixed> $value */
    private function appendTypedValue(string $key, string|array $value, string $type): static
    {
        if (is_string($value)) { $value = ['@type' => $type, 'name' => $value]; }
        elseif (!isset($value['@type'])) { $value['@type'] = $type; }
        $values = $this->get($key);
        if (!is_array($values)) { $values = []; }
        $values[] = $value;
        return $this->set($key, $values);
    }

    /**
     * @param array<string, mixed> $step
     * @return array<string, mixed>
     */
    private function normalizeStep(array $step): array
    {
        if (!isset($step['@type'])) { $step['@type'] = 'HowToStep'; }
        return $step;
    }
}
