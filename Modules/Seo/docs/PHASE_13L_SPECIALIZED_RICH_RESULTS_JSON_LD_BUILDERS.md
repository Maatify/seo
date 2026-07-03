# Phase 13L: Specialized Rich Results JSON-LD Builders

This document details the usage of the Phase 13L Specialized Rich Results JSON-LD builders in the Maatify SEO module. These builders are framework-neutral and standalone.

## Table of Contents
1. [Recipe JSON-LD Builder](#recipe-json-ld-builder)
2. [JobPosting JSON-LD Builder](#jobposting-json-ld-builder)
3. [Course JSON-LD Builder](#course-json-ld-builder)
4. [SoftwareApplication JSON-LD Builder](#softwareapplication-json-ld-builder)

## Recipe JSON-LD Builder
Generates `Recipe` structured data for culinary recipes.

```php
use Maatify\Seo\Web\JsonLd\Builder\RecipeJsonLdBuilder;

$builder = new RecipeJsonLdBuilder();
$builder->setName('Classic Chocolate Chip Cookies')
    ->setDescription('A delicious, chewy chocolate chip cookie recipe.')
    ->setImage('https://example.com/images/cookie.jpg')
    ->setAuthor('Jane Doe') // Automatically normalized to an array with @type 'Person'
    ->setDatePublished('2023-05-10')
    ->setPrepTime('PT15M')
    ->setCookTime('PT10M')
    ->setTotalTime('PT25M')
    ->setRecipeYield('24 cookies')
    ->setRecipeCategory('Dessert')
    ->setRecipeCuisine('American')
    ->setRecipeIngredient(['1 cup flour', '1/2 cup sugar'])
    ->addRecipeIngredient('1 cup chocolate chips')
    ->setRecipeInstructions([
        'Mix ingredients.',
        ['@type' => 'HowToStep', 'text' => 'Bake at 350 degrees.']
    ]) // Strings are converted to HowToStep arrays
    ->addRecipeInstruction('Let cool for 5 minutes.')
    ->setNutrition(['calories' => '200 calories'])
    ->setAggregateRating(['ratingValue' => '4.8', 'reviewCount' => '150']);

$jsonLdArray = $builder->toArray();
```

## JobPosting JSON-LD Builder
Generates `JobPosting` structured data for open positions.

```php
use Maatify\Seo\Web\JsonLd\Builder\JobPostingJsonLdBuilder;

$builder = new JobPostingJsonLdBuilder();
$builder->setTitle('Senior Software Engineer')
    ->setDescription('We are looking for an experienced software engineer to join our team.')
    ->setDatePosted('2023-11-01')
    ->setValidThrough('2023-12-31')
    ->setEmploymentType('FULL_TIME')
    ->setHiringOrganization('Acme Corp') // Automatically normalized to an array with @type 'Organization'
    ->setJobLocation('San Francisco, CA') // Automatically normalized to an array with @type 'Place'
    ->setBaseSalary([
        'currency' => 'USD',
        'value' => ['@type' => 'QuantitativeValue', 'value' => 120000, 'unitText' => 'YEAR']
    ])
    ->setApplicantLocationRequirements('US') // Automatically normalized to an array with @type 'Country'
    ->setJobLocationType('TELECOMMUTE')
    ->setDirectApply(true);

$jsonLdArray = $builder->toArray();
```

## Course JSON-LD Builder
Generates `Course` structured data for educational courses.

```php
use Maatify\Seo\Web\JsonLd\Builder\CourseJsonLdBuilder;

$builder = new CourseJsonLdBuilder();
$builder->setName('Introduction to PHP Programming')
    ->setDescription('A comprehensive guide to learning PHP from scratch.')
    ->setProvider('Maatify University') // Automatically normalized to an array with @type 'Organization'
    ->setCourseCode('CS101')
    ->setEducationalCredentialAwarded('Certificate of Completion')
    ->setHasCourseInstance(['courseMode' => 'online', 'startDate' => '2023-09-01'])
    ->addCourseInstance(['courseMode' => 'onsite', 'location' => 'Campus'])
    ->setOffers([['@type' => 'Offer', 'price' => '199.99', 'priceCurrency' => 'USD']])
    ->setAggregateRating(['ratingValue' => '4.5', 'reviewCount' => '102']);

$jsonLdArray = $builder->toArray();
```

## SoftwareApplication JSON-LD Builder
Generates `SoftwareApplication` structured data for downloadable apps or web applications.

```php
use Maatify\Seo\Web\JsonLd\Builder\SoftwareApplicationJsonLdBuilder;

$builder = new SoftwareApplicationJsonLdBuilder();
$builder->setName('Productivity Master')
    ->setDescription('An application to help you manage your tasks efficiently.')
    ->setApplicationCategory('UtilitiesApplication')
    ->setOperatingSystem(['Windows', 'macOS', 'Linux'])
    ->setSoftwareVersion('2.5.1')
    ->setOffers([['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD']])
    ->setAggregateRating(['ratingValue' => '4.9', 'reviewCount' => '500'])
    ->setAuthor('John Smith') // Automatically normalized to an array with @type 'Person'
    ->setPublisher('Tech Solutions') // Automatically normalized to an array with @type 'Organization'
    ->setDownloadUrl('https://example.com/download/productivity-master')
    ->setScreenshot('https://example.com/images/screenshot.png');

$jsonLdArray = $builder->toArray();
```
