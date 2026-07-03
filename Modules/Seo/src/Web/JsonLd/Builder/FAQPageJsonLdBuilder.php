<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\JsonLd\Builder;

final class FAQPageJsonLdBuilder extends AbstractJsonLdBuilder
{
    public function __construct()
    {
        parent::__construct([
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => [],
        ]);
    }

    public function addQuestion(string $name, string $acceptedAnswerText): static
    {
        return $this->addQuestionArray([
            '@type' => 'Question',
            'name' => $name,
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $acceptedAnswerText,
            ],
        ]);
    }

    /** @param array<string, mixed> $question */
    public function addQuestionArray(array $question): static
    {
        $questions = $this->get('mainEntity');
        if (!is_array($questions)) {
            $questions = [];
        }

        $questions[] = $this->normalizeQuestion($question);

        return $this->setMainEntity($questions);
    }

    /** @param array<int, array<string, mixed>> $questions */
    public function setMainEntity(array $questions): static
    {
        return $this->set('mainEntity', array_map([$this, 'normalizeQuestion'], array_values($questions)));
    }

    public function clearQuestions(): static
    {
        return $this->set('mainEntity', []);
    }

    /**
     * @param array<string, mixed> $question
     * @return array<string, mixed>
     */
    private function normalizeQuestion(array $question): array
    {
        if (!isset($question['@type'])) {
            $question['@type'] = 'Question';
        }

        if (isset($question['acceptedAnswer']) && is_array($question['acceptedAnswer']) && !isset($question['acceptedAnswer']['@type'])) {
            $question['acceptedAnswer']['@type'] = 'Answer';
        }

        return $question;
    }
}
