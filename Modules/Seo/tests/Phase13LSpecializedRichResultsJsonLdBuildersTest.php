<?php

declare(strict_types=1);

// Standalone autoloader for standalone testing
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    spl_autoload_register(function ($class) {
        if (strpos($class, 'Maatify\Seo\\') === 0) {
            $file = __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, 12)) . '.php';
            if (file_exists($file)) {
                require $file;
            }
        }
    });
}

use Maatify\Seo\Web\JsonLd\Builder\RecipeJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\JobPostingJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\CourseJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\SoftwareApplicationJsonLdBuilder;

function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    if ($expected != $actual || (is_array($expected) && is_array($actual) && json_encode($expected) !== json_encode($actual) && $expected !== $actual)) {
        // Let's do a safe array sort for associative arrays if we wanted, but == handles associative array equality ignoring order
    }
    if (is_array($expected) && is_array($actual)) {
        if ($expected != $actual) {
             throw new \RuntimeException("$message\nExpected: " . json_encode($expected) . "\nActual: " . json_encode($actual));
        }
    } else {
        if ($expected !== $actual) {
             throw new \RuntimeException("$message\nExpected: " . (string)$expected . "\nActual: " . (string)$actual);
        }
    }
}

echo "Testing Phase 13L Specialized Rich Results JSON-LD Builders...\n";

// 1. RecipeJsonLdBuilder
$recipe = new RecipeJsonLdBuilder();
$recipe->setName('Pancakes')
    ->setDescription('Delicious pancakes')
    ->setImage('pancakes.jpg')
    ->setAuthor('John Doe')
    ->setDatePublished('2023-01-01')
    ->setPrepTime('PT15M')
    ->setCookTime('PT10M')
    ->setTotalTime('PT25M')
    ->setRecipeYield('4 servings')
    ->setRecipeCategory('Breakfast')
    ->setRecipeCuisine('American')
    ->setRecipeIngredient(['Flour', 'Milk'])
    ->addRecipeIngredient('Eggs')
    ->setRecipeInstructions([
        'Mix ingredients',
        ['@type' => 'HowToStep', 'text' => 'Cook on pan']
    ])
    ->addRecipeInstruction('Serve hot')
    ->setNutrition(['calories' => '250 calories'])
    ->setAggregateRating(['ratingValue' => '5']);

$recipeOutput = $recipe->toArray();

assertSameValue('Recipe', $recipeOutput['@type'] ?? null, 'Recipe @type');
assertSameValue('Pancakes', $recipeOutput['name'] ?? null, 'Recipe name');
assertSameValue('Delicious pancakes', $recipeOutput['description'] ?? null, 'Recipe description');
assertSameValue('pancakes.jpg', $recipeOutput['image'] ?? null, 'Recipe image');
assertSameValue(['@type' => 'Person', 'name' => 'John Doe'], $recipeOutput['author'] ?? null, 'Recipe author normalized');
assertSameValue('2023-01-01', $recipeOutput['datePublished'] ?? null, 'Recipe datePublished');
assertSameValue('PT15M', $recipeOutput['prepTime'] ?? null, 'Recipe prepTime');
assertSameValue('PT10M', $recipeOutput['cookTime'] ?? null, 'Recipe cookTime');
assertSameValue('PT25M', $recipeOutput['totalTime'] ?? null, 'Recipe totalTime');
assertSameValue('4 servings', $recipeOutput['recipeYield'] ?? null, 'Recipe recipeYield');
assertSameValue('Breakfast', $recipeOutput['recipeCategory'] ?? null, 'Recipe recipeCategory');
assertSameValue('American', $recipeOutput['recipeCuisine'] ?? null, 'Recipe recipeCuisine');
assertSameValue(['Flour', 'Milk', 'Eggs'], $recipeOutput['recipeIngredient'] ?? null, 'Recipe ingredients');
assertSameValue([
    ['@type' => 'HowToStep', 'text' => 'Mix ingredients'],
    ['@type' => 'HowToStep', 'text' => 'Cook on pan'],
    ['@type' => 'HowToStep', 'text' => 'Serve hot']
], $recipeOutput['recipeInstructions'] ?? null, 'Recipe instructions normalized');
assertSameValue(['calories' => '250 calories', '@type' => 'NutritionInformation'], $recipeOutput['nutrition'] ?? null, 'Recipe nutrition normalized');
assertSameValue(['ratingValue' => '5', '@type' => 'AggregateRating'], $recipeOutput['aggregateRating'] ?? null, 'Recipe aggregateRating normalized');

// 2. JobPostingJsonLdBuilder
$jobPosting = new JobPostingJsonLdBuilder();
$jobPosting->setTitle('Software Engineer')
    ->setDescription('Develop cool stuff')
    ->setDatePosted('2023-01-01')
    ->setValidThrough('2024-01-01')
    ->setEmploymentType('FULL_TIME')
    ->setHiringOrganization('Tech Corp')
    ->setJobLocation('New York')
    ->setBaseSalary(['currency' => 'USD', 'value' => ['@type' => 'QuantitativeValue', 'value' => 100000]])
    ->setApplicantLocationRequirements('US')
    ->setJobLocationType('TELECOMMUTE')
    ->setDirectApply(true);

$jobPostingOutput = $jobPosting->toArray();

assertSameValue('JobPosting', $jobPostingOutput['@type'] ?? null, 'JobPosting @type');
assertSameValue('Software Engineer', $jobPostingOutput['title'] ?? null, 'JobPosting title');
assertSameValue('Develop cool stuff', $jobPostingOutput['description'] ?? null, 'JobPosting description');
assertSameValue('2023-01-01', $jobPostingOutput['datePosted'] ?? null, 'JobPosting datePosted');
assertSameValue('2024-01-01', $jobPostingOutput['validThrough'] ?? null, 'JobPosting validThrough');
assertSameValue('FULL_TIME', $jobPostingOutput['employmentType'] ?? null, 'JobPosting employmentType');
assertSameValue(['@type' => 'Organization', 'name' => 'Tech Corp'], $jobPostingOutput['hiringOrganization'] ?? null, 'JobPosting hiringOrganization normalized');
assertSameValue(['@type' => 'Place', 'name' => 'New York'], $jobPostingOutput['jobLocation'] ?? null, 'JobPosting jobLocation normalized');
assertSameValue(['currency' => 'USD', 'value' => ['@type' => 'QuantitativeValue', 'value' => 100000], '@type' => 'MonetaryAmount'], $jobPostingOutput['baseSalary'] ?? null, 'JobPosting baseSalary normalized');
assertSameValue(['@type' => 'Country', 'name' => 'US'], $jobPostingOutput['applicantLocationRequirements'] ?? null, 'JobPosting applicantLocationRequirements normalized');
assertSameValue('TELECOMMUTE', $jobPostingOutput['jobLocationType'] ?? null, 'JobPosting jobLocationType');
assertSameValue(true, $jobPostingOutput['directApply'] ?? null, 'JobPosting directApply');

// 3. CourseJsonLdBuilder
$course = new CourseJsonLdBuilder();
$course->setName('Intro to PHP')
    ->setDescription('Learn PHP basics')
    ->setProvider('University')
    ->setCourseCode('CS101')
    ->setEducationalCredentialAwarded('Certificate')
    ->setHasCourseInstance(['courseMode' => 'online'])
    ->addCourseInstance(['courseMode' => 'onsite'])
    ->setOffers([['@type' => 'Offer', 'price' => '100.00']])
    ->setAggregateRating(['ratingValue' => '4.5']);

$courseOutput = $course->toArray();

assertSameValue('Course', $courseOutput['@type'] ?? null, 'Course @type');
assertSameValue('Intro to PHP', $courseOutput['name'] ?? null, 'Course name');
assertSameValue('Learn PHP basics', $courseOutput['description'] ?? null, 'Course description');
assertSameValue(['@type' => 'Organization', 'name' => 'University'], $courseOutput['provider'] ?? null, 'Course provider normalized');
assertSameValue('CS101', $courseOutput['courseCode'] ?? null, 'Course courseCode');
assertSameValue('Certificate', $courseOutput['educationalCredentialAwarded'] ?? null, 'Course credential');
assertSameValue([
    ['courseMode' => 'online', '@type' => 'CourseInstance'],
    ['courseMode' => 'onsite', '@type' => 'CourseInstance']
], $courseOutput['hasCourseInstance'] ?? null, 'Course instances normalized');
assertSameValue([['@type' => 'Offer', 'price' => '100.00']], $courseOutput['offers'] ?? null, 'Course offers');
assertSameValue(['ratingValue' => '4.5', '@type' => 'AggregateRating'], $courseOutput['aggregateRating'] ?? null, 'Course aggregateRating normalized');

// 4. SoftwareApplicationJsonLdBuilder
$software = new SoftwareApplicationJsonLdBuilder();
$software->setName('My App')
    ->setDescription('Best app ever')
    ->setApplicationCategory('UtilitiesApplication')
    ->setOperatingSystem('Android')
    ->setSoftwareVersion('1.0')
    ->setOffers([['@type' => 'Offer', 'price' => '0']])
    ->setAggregateRating(['ratingValue' => '4.8'])
    ->setAuthor('App Dev')
    ->setPublisher('App Studio')
    ->setDownloadUrl('https://example.com/download')
    ->setScreenshot('screenshot.jpg');

$softwareOutput = $software->toArray();

assertSameValue('SoftwareApplication', $softwareOutput['@type'] ?? null, 'SoftwareApplication @type');
assertSameValue('My App', $softwareOutput['name'] ?? null, 'SoftwareApplication name');
assertSameValue('Best app ever', $softwareOutput['description'] ?? null, 'SoftwareApplication description');
assertSameValue('UtilitiesApplication', $softwareOutput['applicationCategory'] ?? null, 'SoftwareApplication category');
assertSameValue('Android', $softwareOutput['operatingSystem'] ?? null, 'SoftwareApplication os');
assertSameValue('1.0', $softwareOutput['softwareVersion'] ?? null, 'SoftwareApplication version');
assertSameValue([['@type' => 'Offer', 'price' => '0']], $softwareOutput['offers'] ?? null, 'SoftwareApplication offers');
assertSameValue(['ratingValue' => '4.8', '@type' => 'AggregateRating'], $softwareOutput['aggregateRating'] ?? null, 'SoftwareApplication aggregateRating');
assertSameValue(['@type' => 'Person', 'name' => 'App Dev'], $softwareOutput['author'] ?? null, 'SoftwareApplication author normalized');
assertSameValue(['@type' => 'Organization', 'name' => 'App Studio'], $softwareOutput['publisher'] ?? null, 'SoftwareApplication publisher normalized');
assertSameValue('https://example.com/download', $softwareOutput['downloadUrl'] ?? null, 'SoftwareApplication downloadUrl');
assertSameValue('screenshot.jpg', $softwareOutput['screenshot'] ?? null, 'SoftwareApplication screenshot');

echo "All Phase 13L Specialized Rich Results tests passed!\n";
