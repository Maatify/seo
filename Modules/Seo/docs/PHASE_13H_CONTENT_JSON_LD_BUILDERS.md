# Phase 13H: Content JSON-LD Builders

This document covers the batch of content-focused JSON-LD builders implemented in Phase 13H. These builders allow generating standardized schema.org entities for rich snippets and structured data on content pages.

The Phase 13H batch includes:
- `FAQPageJsonLdBuilder`
- `HowToJsonLdBuilder`
- `EventJsonLdBuilder`
- `ItemListJsonLdBuilder`
- `WebPageJsonLdBuilder`

All builders are strictly framework-neutral, independent of any HTTP or template engine layer, and are designed to return arrays or JSON strings for rendering.

## 1. FAQPageJsonLdBuilder

Builds `FAQPage` schema.org markup with a list of `Question` and `Answer` entities.

### Usage

```php
use Maatify\Seo\Web\JsonLd\Builder\FAQPageJsonLdBuilder;

$faqBuilder = new FAQPageJsonLdBuilder();

$schema = $faqBuilder
    ->addQuestion('What is JSON-LD?', 'JSON-LD is a method of encoding Linked Data using JSON.')
    ->addQuestionArray([
        'name' => 'Why use it?',
        'acceptedAnswer' => [
            'text' => 'It helps search engines understand your content better.'
        ]
    ])
    ->toArray();

echo json_encode($schema, JSON_UNESCAPED_SLASHES);
```

### Methods
- `addQuestion(string $name, string $acceptedAnswerText): static`
- `addQuestionArray(array $question): static`
- `setMainEntity(array $questions): static`
- `clearQuestions(): static`


## 2. HowToJsonLdBuilder

Builds `HowTo` schema.org markup with steps, supplies, and tools.

### Usage

```php
use Maatify\Seo\Web\JsonLd\Builder\HowToJsonLdBuilder;

$howToBuilder = new HowToJsonLdBuilder();

$schema = $howToBuilder
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
```

### Methods
- `setName(string $name): static`
- `setDescription(string $description): static`
- `setImage(string|array $image): static`
- `setTotalTime(string $totalTime): static`
- `setEstimatedCost(string|array $estimatedCost): static`
- `addSupply(string|array $supply): static`
- `addTool(string|array $tool): static`
- `addStep(string $name, ?string $text = null, ?string $url = null, string|array|null $image = null): static`
- `setSteps(array $steps): static`
- `clearSteps(): static`


## 3. EventJsonLdBuilder

Builds `Event` schema.org markup with dates, location, and offers.

### Usage

```php
use Maatify\Seo\Web\JsonLd\Builder\EventJsonLdBuilder;

$eventBuilder = new EventJsonLdBuilder();

$schema = $eventBuilder
    ->setName('Maatify Developer Conference')
    ->setDescription('A conference for developers.')
    ->setStartDate('2023-10-01T09:00:00Z')
    ->setEndDate('2023-10-03T17:00:00Z')
    ->setEventStatus('https://schema.org/EventScheduled')
    ->setEventAttendanceMode('https://schema.org/OfflineEventAttendanceMode')
    ->setLocation('Conference Center') // auto-normalized to Place
    ->setImage('https://example.com/conference.jpg')
    ->setOrganizer('Maatify Organization') // auto-normalized to Organization
    ->setPerformer('John Doe') // auto-normalized to Person
    ->setOffers([
        ['@type' => 'Offer', 'price' => '99.00', 'priceCurrency' => 'USD']
    ])
    ->toArray();
```

### Methods
- `setName(string $name): static`
- `setDescription(string $description): static`
- `setStartDate(string $startDate): static`
- `setEndDate(string $endDate): static`
- `setEventStatus(string $eventStatus): static`
- `setEventAttendanceMode(string $eventAttendanceMode): static`
- `setLocation(string|array $location): static`
- `setImage(string|array $image): static`
- `setOrganizer(string|array $organizer): static`
- `setPerformer(string|array $performer): static`
- `setOffers(array $offers): static`


## 4. ItemListJsonLdBuilder

Builds `ItemList` schema.org markup with ordered list items. The `position` is automatically deterministic by insertion order.

### Usage

```php
use Maatify\Seo\Web\JsonLd\Builder\ItemListJsonLdBuilder;

$itemListBuilder = new ItemListJsonLdBuilder();

$schema = $itemListBuilder
    ->setName('Top 3 PHP Frameworks')
    ->setDescription('A list of the top PHP frameworks.')
    ->addItem('Laravel')
    ->addItem(['@type' => 'ListItem', 'item' => 'Symfony'], 'Symfony Framework')
    ->toArray();
```

### Methods
- `setName(string $name): static`
- `setDescription(string $description): static`
- `addItem(string|array $item, ?string $name = null): static`
- `setItems(array $items): static`
- `clearItems(): static`


## 5. WebPageJsonLdBuilder

Builds generic `WebPage` schema.org markup for pages that do not fit a more specific type.

### Usage

```php
use Maatify\Seo\Web\JsonLd\Builder\WebPageJsonLdBuilder;

$webPageBuilder = new WebPageJsonLdBuilder();

$schema = $webPageBuilder
    ->setName('About Us - Maatify')
    ->setUrl('https://example.com/about')
    ->setDescription('Learn more about Maatify.')
    ->setIsPartOf('https://example.com') // auto-normalized to WebSite
    ->setAbout('Maatify Company') // auto-normalized to Thing
    ->setBreadcrumb('https://example.com/about#breadcrumb') // auto-normalized to BreadcrumbList
    ->setPrimaryImageOfPage('https://example.com/about.jpg') // auto-normalized to ImageObject
    ->setDatePublished('2023-01-01T12:00:00Z')
    ->setDateModified('2023-05-01T12:00:00Z')
    ->toArray();
```

### Methods
- `setName(string $name): static`
- `setUrl(string $url): static`
- `setDescription(string $description): static`
- `setIsPartOf(string|array $website): static`
- `setAbout(string|array $about): static`
- `setBreadcrumb(string|array $breadcrumb): static`
- `setPrimaryImageOfPage(string|array $image): static`
- `setDatePublished(string $datePublished): static`
- `setDateModified(string $dateModified): static`
