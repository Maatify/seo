<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\JsonLd\Builder;

use Maatify\Seo\Web\JsonLd\Builder\Concerns\HasTypedValueNormalization;

final class RecipeJsonLdBuilder extends AbstractJsonLdBuilder
{
    use HasTypedValueNormalization;

    public function __construct()
    {
        parent::__construct([
            '@context' => 'https://schema.org',
            '@type' => 'Recipe',
        ]);
    }

    public function setName(string $name): static { return $this->set('name', $name); }
    public function setDescription(string $description): static { return $this->set('description', $description); }
    /** @param string|array<int, string>|array<string, mixed> $image */
    public function setImage(string|array $image): static { return $this->set('image', $image); }
    /** @param string|array<string, mixed> $author */
    public function setAuthor(string|array $author): static { return $this->set('author', $this->normalizeTypedValue($author, 'Person', 'name')); }
    public function setDatePublished(string $datePublished): static { return $this->set('datePublished', $datePublished); }
    public function setPrepTime(string $prepTime): static { return $this->set('prepTime', $prepTime); }
    public function setCookTime(string $cookTime): static { return $this->set('cookTime', $cookTime); }
    public function setTotalTime(string $totalTime): static { return $this->set('totalTime', $totalTime); }
    /** @param string|array<int, string> $recipeYield */
    public function setRecipeYield(string|array $recipeYield): static { return $this->set('recipeYield', $recipeYield); }
    public function setRecipeCategory(string $recipeCategory): static { return $this->set('recipeCategory', $recipeCategory); }
    public function setRecipeCuisine(string $recipeCuisine): static { return $this->set('recipeCuisine', $recipeCuisine); }
    /** @param array<int, string> $ingredients */
    public function setRecipeIngredient(array $ingredients): static { return $this->set('recipeIngredient', array_values($ingredients)); }
    public function addRecipeIngredient(string $ingredient): static { return $this->appendValue('recipeIngredient', $ingredient); }
    /** @param array<int, string|array<string, mixed>> $instructions */
    public function setRecipeInstructions(array $instructions): static
    {
        return $this->set('recipeInstructions', array_map([$this, 'normalizeInstruction'], array_values($instructions)));
    }
    /** @param string|array<string, mixed> $instruction */
    public function addRecipeInstruction(string|array $instruction): static { return $this->appendValue('recipeInstructions', $this->normalizeInstruction($instruction)); }
    /** @param array<string, mixed> $nutrition */
    public function setNutrition(array $nutrition): static { return $this->set('nutrition', $this->defaultTypedValue($nutrition, 'NutritionInformation')); }
    /** @param array<string, mixed> $aggregateRating */
    public function setAggregateRating(array $aggregateRating): static { return $this->set('aggregateRating', $this->defaultTypedValue($aggregateRating, 'AggregateRating')); }

    /**
     * @param string|array<string, mixed> $instruction
     * @return array<string, mixed>
     */
    private function normalizeInstruction(string|array $instruction): array
    {
        if (is_string($instruction)) { return ['@type' => 'HowToStep', 'text' => $instruction]; }
        return $this->defaultTypedValue($instruction, 'HowToStep');
    }

    private function appendValue(string $key, mixed $value): static
    {
        $values = $this->get($key);
        if (!is_array($values)) { $values = []; }
        $values[] = $value;
        return $this->set($key, $values);
    }
}
