<?php

namespace Airalo\DTOs;

use Airalo\DTOs\Traits\HasImage;

class Country
{
    use HasImage;

    public string $slug;
    public string $countryCode;
    public string $title;
    public ?Image $image;
    public ?string $url;
    public array $operators;

    public function __construct($data)
    {
        $this->slug = $data['slug'] ?? null;
        $this->countryCode = $data['country_code'] ?? null;
        $this->title = $data['title'] ?? null;
        $this->image = isset($data['image']) ? new Image($data['image']) : null;
        $this->url = $data['url'] ?? null;
        $operators = $data['operators'] ?? null;
        foreach ($operators as $operator) {
            $this->operators[] = new Operator($operator);
        }
    }
}