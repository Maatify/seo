# Phase 13G: Person JSON-LD Builder

The `PersonJsonLdBuilder` is part of the SEO module's JSON-LD suite, offering a framework-agnostic way to generate `Person` schema markup.

## Usage Example

```php
use Maatify\Seo\Web\JsonLd\Builder\PersonJsonLdBuilder;

$builder = new PersonJsonLdBuilder();
$builder->setName('John Doe')
        ->setUrl('https://example.com/johndoe')
        ->setDescription('A software engineer')
        ->setJobTitle('Senior Developer')
        ->setImage('https://example.com/johndoe.jpg')
        ->setWorksFor('Acme Corp')
        ->setEmail('john@example.com')
        ->setTelephone('+1-555-555-1234')
        ->setSameAs([
            'https://twitter.com/johndoe',
            'https://linkedin.com/in/johndoe'
        ])
        ->addSameAs('https://github.com/johndoe')
        ->setPostalAddress(
            '123 Main St',
            'Anytown',
            'CA',
            '90210',
            'USA'
        );

// Generate JSON string
$jsonLdString = $builder->toJson();

// Output example payload:
// {
//     "@context": "https://schema.org",
//     "@type": "Person",
//     "name": "John Doe",
//     ...
// }
```

## Available Methods

- `setName(string $name): static`
- `setUrl(string $url): static`
- `setImage(string|array $image): static`
- `setDescription(string $description): static`
- `setJobTitle(string $jobTitle): static`
- `setWorksFor(string|array $worksFor): static` - Strings are converted to an `Organization` array with the provided string as `name`.
- `setSameAs(array $sameAs): static` - Accepts an array of strings.
- `addSameAs(string $url): static` - Appends a URL string to the existing `sameAs` array.
- `setEmail(string $email): static`
- `setTelephone(string $telephone): static`
- `setAddress(array $address): static` - Sets the address array. Will set `@type` to `PostalAddress` if omitted.
- `setPostalAddress(?string $streetAddress, ?string $addressLocality, ?string $addressRegion, ?string $postalCode, ?string $addressCountry): static` - Helper to quickly build a `PostalAddress` array.
