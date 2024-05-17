<?php

namespace Airalo\Responses;

use Airalo\DTOs\Country;

class PackagesResponse
{
    private string $raw;
    public array $items;

    public function __construct(array $data)
    {
        $this->fillObject($data);
    }

    public function getRawResponse(): string
    {
        return $this->raw;
    }

    public function getItemsCount(): int
    {
        return count($this->items);
    }

    private function fillObject(array $data): void
    {
        foreach ($data['data'] as $item) {
            $this->items[] = new Country($item);
        }
    }
}
