<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'Maatify\\Seo\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $path = __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

use Maatify\Seo\Web\JsonLd\Builder\EventJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\FAQPageJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\HowToJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\ItemListJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\WebPageJsonLdBuilder;

function assertSameValue13H(string $label, mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "Assertion failed: {$label}\nExpected:\n" . var_export($expected, true) . "\nActual:\n" . var_export($actual, true) . "\n");
        exit(1);
    }
}

// 1. FAQPageJsonLdBuilder
$faqBuilder = new FAQPageJsonLdBuilder();
assertSameValue13H('faq schema defaults', [
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => [],
], $faqBuilder->toArray());

$faqSchema = $faqBuilder
    ->addQuestion('What is JSON-LD?', 'JSON-LD is a method of encoding Linked Data using JSON.')
    ->addQuestionArray([
        'name' => 'Why use it?',
        'acceptedAnswer' => [
            'text' => 'It helps search engines understand your content better.'
        ]
    ])
    ->toArray();

assertSameValue13H('faq schema questions added', [
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => [
        [
            '@type' => 'Question',
            'name' => 'What is JSON-LD?',
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => 'JSON-LD is a method of encoding Linked Data using JSON.',
            ],
        ],
        [
            'name' => 'Why use it?',
            'acceptedAnswer' => [
                'text' => 'It helps search engines understand your content better.',
                '@type' => 'Answer',
            ],
            '@type' => 'Question',
        ],
    ],
], $faqSchema);

$faqBuilder->clearQuestions();
assertSameValue13H('faq schema cleared', [], $faqBuilder->get('mainEntity'));

$faqBuilder->setMainEntity([
    [
        'name' => 'Is this a test?',
        'acceptedAnswer' => [
            'text' => 'Yes, it is.'
        ]
    ]
]);
assertSameValue13H('faq schema setMainEntity', [
    [
        'name' => 'Is this a test?',
        'acceptedAnswer' => [
            'text' => 'Yes, it is.',
            '@type' => 'Answer'
        ],
        '@type' => 'Question'
    ]
], $faqBuilder->get('mainEntity'));


// 2. HowToJsonLdBuilder
$howToBuilder = new HowToJsonLdBuilder();
assertSameValue13H('howto schema defaults', [
    '@context' => 'https://schema.org',
    '@type' => 'HowTo',
    'step' => [],
], $howToBuilder->toArray());

$howToSchema = $howToBuilder
    ->setName('How to tie a tie')
    ->setDescription('A step-by-step guide to tying a tie.')
    ->setImage('https://example.com/tie.jpg')
    ->setTotalTime('PT5M')
    ->setEstimatedCost(['currency' => 'USD', 'value' => '0.00'])
    ->addSupply('Tie')
    ->addTool('Mirror')
    ->addStep('Drape the tie', 'Drape the tie around your neck.')
    ->addStep('Cross the wide end', 'Cross the wide end over the narrow end.')
    ->toArray();

assertSameValue13H('howto schema full', [
    '@context' => 'https://schema.org',
    '@type' => 'HowTo',
    'step' => [
        [
            '@type' => 'HowToStep',
            'name' => 'Drape the tie',
            'text' => 'Drape the tie around your neck.',
        ],
        [
            '@type' => 'HowToStep',
            'name' => 'Cross the wide end',
            'text' => 'Cross the wide end over the narrow end.',
        ],
    ],
    'name' => 'How to tie a tie',
    'description' => 'A step-by-step guide to tying a tie.',
    'image' => 'https://example.com/tie.jpg',
    'totalTime' => 'PT5M',
    'estimatedCost' => [
        'currency' => 'USD',
        'value' => '0.00',
    ],
    'supply' => [
        [
            '@type' => 'HowToSupply',
            'name' => 'Tie',
        ],
    ],
    'tool' => [
        [
            '@type' => 'HowToTool',
            'name' => 'Mirror',
        ],
    ],
], $howToSchema);

$howToBuilder->clearSteps();
assertSameValue13H('howto schema cleared', [], $howToBuilder->get('step'));

$howToBuilder->setSteps([
    ['name' => 'New step 1']
]);
assertSameValue13H('howto schema setSteps', [
    [
        'name' => 'New step 1',
        '@type' => 'HowToStep'
    ]
], $howToBuilder->get('step'));


// 3. EventJsonLdBuilder
$eventBuilder = new EventJsonLdBuilder();
assertSameValue13H('event schema defaults', [
    '@context' => 'https://schema.org',
    '@type' => 'Event',
], $eventBuilder->toArray());

$eventSchema = $eventBuilder
    ->setName('Maatify Developer Conference')
    ->setDescription('A conference for developers.')
    ->setStartDate('2023-10-01T09:00:00Z')
    ->setEndDate('2023-10-03T17:00:00Z')
    ->setEventStatus('https://schema.org/EventScheduled')
    ->setEventAttendanceMode('https://schema.org/OfflineEventAttendanceMode')
    ->setLocation('Conference Center')
    ->setImage('https://example.com/conference.jpg')
    ->setOrganizer('Maatify Organization')
    ->setPerformer('John Doe')
    ->setOffers([
        ['@type' => 'Offer', 'price' => '99.00', 'priceCurrency' => 'USD']
    ])
    ->toArray();

assertSameValue13H('event schema full', [
    '@context' => 'https://schema.org',
    '@type' => 'Event',
    'name' => 'Maatify Developer Conference',
    'description' => 'A conference for developers.',
    'startDate' => '2023-10-01T09:00:00Z',
    'endDate' => '2023-10-03T17:00:00Z',
    'eventStatus' => 'https://schema.org/EventScheduled',
    'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
    'location' => [
        '@type' => 'Place',
        'name' => 'Conference Center',
    ],
    'image' => 'https://example.com/conference.jpg',
    'organizer' => [
        '@type' => 'Organization',
        'name' => 'Maatify Organization',
    ],
    'performer' => [
        '@type' => 'Person',
        'name' => 'John Doe',
    ],
    'offers' => [
        [
            '@type' => 'Offer',
            'price' => '99.00',
            'priceCurrency' => 'USD',
        ],
    ],
], $eventSchema);


// 4. ItemListJsonLdBuilder
$itemListBuilder = new ItemListJsonLdBuilder();
assertSameValue13H('itemlist schema defaults', [
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'itemListElement' => [],
], $itemListBuilder->toArray());

$itemListSchema = $itemListBuilder
    ->setName('Top 3 PHP Frameworks')
    ->setDescription('A list of the top PHP frameworks.')
    ->addItem('Laravel')
    ->addItem(['@type' => 'ListItem', 'item' => 'Symfony'], 'Symfony Framework')
    ->toArray();

assertSameValue13H('itemlist schema full', [
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'itemListElement' => [
        [
            '@type' => 'ListItem',
            'position' => 1,
            'item' => 'Laravel',
        ],
        [
            '@type' => 'ListItem',
            'item' => 'Symfony',
            'position' => 2,
            'name' => 'Symfony Framework',
        ],
    ],
    'name' => 'Top 3 PHP Frameworks',
    'description' => 'A list of the top PHP frameworks.',
], $itemListSchema);

$itemListBuilder->clearItems();
assertSameValue13H('itemlist schema cleared', [], $itemListBuilder->get('itemListElement'));

$itemListBuilder->setItems([
    'Zend Framework',
    ['@type' => 'ListItem', 'item' => 'Slim']
]);
assertSameValue13H('itemlist schema setItems', [
    [
        '@type' => 'ListItem',
        'position' => 1,
        'item' => 'Zend Framework',
    ],
    [
        '@type' => 'ListItem',
        'item' => 'Slim',
        'position' => 2,
    ],
], $itemListBuilder->get('itemListElement'));


// 5. WebPageJsonLdBuilder
$webPageBuilder = new WebPageJsonLdBuilder();
assertSameValue13H('webpage schema defaults', [
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
], $webPageBuilder->toArray());

$webPageSchema = $webPageBuilder
    ->setName('About Us - Maatify')
    ->setUrl('https://example.com/about')
    ->setDescription('Learn more about Maatify.')
    ->setIsPartOf('https://example.com')
    ->setAbout('Maatify Company')
    ->setBreadcrumb('https://example.com/about#breadcrumb')
    ->setPrimaryImageOfPage('https://example.com/about.jpg')
    ->setDatePublished('2023-01-01T12:00:00Z')
    ->setDateModified('2023-05-01T12:00:00Z')
    ->toArray();

assertSameValue13H('webpage schema full', [
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    'name' => 'About Us - Maatify',
    'url' => 'https://example.com/about',
    'description' => 'Learn more about Maatify.',
    'isPartOf' => [
        '@type' => 'WebSite',
        'url' => 'https://example.com',
    ],
    'about' => [
        '@type' => 'Thing',
        'name' => 'Maatify Company',
    ],
    'breadcrumb' => [
        '@type' => 'BreadcrumbList',
        '@id' => 'https://example.com/about#breadcrumb',
    ],
    'primaryImageOfPage' => [
        '@type' => 'ImageObject',
        'url' => 'https://example.com/about.jpg',
    ],
    'datePublished' => '2023-01-01T12:00:00Z',
    'dateModified' => '2023-05-01T12:00:00Z',
], $webPageSchema);


echo "Phase 13H Content JSON-LD builder tests passed.\n";
