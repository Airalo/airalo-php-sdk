<?php

namespace Airalo\DTOs;

class Image
{

    public ?int $width;

    public ?int $height;

    public ?string $url;

    public function __construct($data)
    {
        $this->width = $data['width'] ?? null;
        $this->height = $data['height'] ?? null;
        $this->url = $data['url'] ?? null;
    }
}