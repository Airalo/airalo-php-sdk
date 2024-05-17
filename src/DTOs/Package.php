<?php

namespace Airalo\DTOs;

class Package
{

    public $id;
    public ?string $type;
    public float $price;
    public float $netPrice;
    public int $amount;
    public int $day;
    public bool $isUnlimited;
    public string $title;

    public string $data;
    public ?string $shortInfo;
    public ?int $voice;
    public ?int $text;

    public function __construct($data) {
        $this->id = $data['id'] ?? null;
        $this->type = $data['type'] ?? null;
        $this->price = $data['price'] ?? null;
        $this->amount = $data['amount'] ?? null;
        $this->day = $data['day'] ?? null;
        $this->isUnlimited = $data['is_unlimited'] ?? null;
        $this->title = $data['title'] ?? null;
        $this->data = $data['data'] ?? null;
        $this->shortInfo = $data['short_info'] ?? null;
        $this->voice = $data['voice'] ?? null;
        $this->text = $data['text'] ?? null;
        $this->netPrice = $data['net_price'] ?? null;
    }
}
