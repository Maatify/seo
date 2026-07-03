<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\JsonLd\Builder;

final class EventJsonLdBuilder extends AbstractJsonLdBuilder
{
    public function __construct()
    {
        parent::__construct([
            '@context' => 'https://schema.org',
            '@type' => 'Event',
        ]);
    }

    public function setName(string $name): static { return $this->set('name', $name); }
    public function setDescription(string $description): static { return $this->set('description', $description); }
    public function setStartDate(string $startDate): static { return $this->set('startDate', $startDate); }
    public function setEndDate(string $endDate): static { return $this->set('endDate', $endDate); }
    public function setEventStatus(string $eventStatus): static { return $this->set('eventStatus', $eventStatus); }
    public function setEventAttendanceMode(string $eventAttendanceMode): static { return $this->set('eventAttendanceMode', $eventAttendanceMode); }
    /** @param string|array<string, mixed> $location */
    public function setLocation(string|array $location): static { return $this->set('location', $this->normalizeTypedValue($location, 'Place')); }
    /** @param string|array<int|string, mixed> $image */
    public function setImage(string|array $image): static { return $this->set('image', $image); }
    /** @param string|array<string, mixed> $organizer */
    public function setOrganizer(string|array $organizer): static { return $this->set('organizer', $this->normalizeTypedValue($organizer, 'Organization')); }
    /** @param string|array<string, mixed> $performer */
    public function setPerformer(string|array $performer): static { return $this->set('performer', $this->normalizeTypedValue($performer, 'Person')); }
    /** @param array<string, mixed>|array<int, array<string, mixed>> $offers */
    public function setOffers(array $offers): static { return $this->set('offers', $offers); }

    /**
     * @param string|array<string, mixed> $value
     * @return array<string, mixed>
     */
    private function normalizeTypedValue(string|array $value, string $type): array
    {
        if (is_string($value)) {
            return ['@type' => $type, 'name' => $value];
        }
        if (!isset($value['@type'])) { $value['@type'] = $type; }
        return $value;
    }
}
