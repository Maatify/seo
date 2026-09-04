<?php
$file = 'docs/roadmap/SEO_LIBRARY_ENHANCEMENT_ROADMAP.md';
$content = file_get_contents($file);

$oldPhase13OGoal = "## Goal

Fully reflect the structured-data gaps that currently exist in the library. This section addresses existing deferred requirements (ProductGroup, product variant structured-data support, richer schema / CI validation direction) and newly identified enhancements (first-class AggregateOffer support) while completing partially implemented features (Product typed-field completeness, semantic structured-data validation).";

$newPhase13OGoal = "## Goal

Fully reflect the structured-data gaps that currently exist in the library. This section addresses existing deferred requirements (ProductGroup, product variant structured-data support, richer schema / CI validation direction) and newly identified enhancements (first-class AggregateOffer support) while completing partially implemented features (Product typed-field completeness, structured-data validation foundation / generic structural validation layer) and addressing outstanding requirements (deep Schema.org semantic validation).";

$content = str_replace($oldPhase13OGoal, $newPhase13OGoal, $content);


$oldPhase13PGoal = "## Goal

Provide deep schema-type semantic validation, distinguishing between Schema.org correctness and Google eligibility. This covers a partially implemented feature that requires completion and a richer schema validation direction planned historically.";

$newPhase13PGoal = "## Goal

Provide deep schema-type semantic validation, distinguishing between Schema.org correctness and Google eligibility. The structured-data validation foundation / generic structural validation is partially implemented and requires completion, while deep Schema.org semantic validation itself remains outstanding (including Product, Offer, AggregateOffer, ProductGroup, and schema-type relationship/property validation).";

$content = str_replace($oldPhase13PGoal, $newPhase13PGoal, $content);

file_put_contents($file, $content);
