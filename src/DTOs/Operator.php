<?php

namespace Airalo\DTOs;

use Airalo\DTOs\Traits\HasImage;

class Operator
{
    use HasImage;

    public int $id;
    public ?string $style;
    public ?string $gradientStart;
    public ?string $gradientEnd;
    public ?string $type;
    public ?bool $isPrepaid;
    public ?string $title;
    public ?string $esimType;
    public ?string $warning;
    public ?string $apnType;
    public ?string $apnValue;
    public ?bool $isRoaming;
    public ?array $info;
    public ?Image $image;
    public ?string $planType;
    public ?string $activationPolicy;
    public ?bool $isKycVerify;
    public ?bool $rechargeability;
    public ?string $otherInfo;
    public ?array $coverages;
    public array $packages;

    public function __construct($data) {
        $this->id = $data['id'] ?? null;
        $this->style = $data['style'] ?? null;
        $this->gradientStart = $data['gradient_start'] ?? null;
        $this->gradientEnd = $data['gradient_end'] ?? null;
        $this->type = $data['type'] ?? null;
        $this->isPrepaid = $data['is_prepaid'] ?? null;
        $this->title = $data['title'] ?? null;
        $this->esimType = $data['esim_type'] ?? null;
        $this->warning = $data['warning'] ?? null;
        $this->apnType = $data['apn_type'] ?? null;
        $this->apnValue = $data['apn_value'] ?? null;
        $this->isRoaming = $data['is_roaming'] ?? null;
        $this->info = $data['info'] ?? null;
        $this->image = isset($data['image']) ? new Image($data['image']): null;
        $this->planType = $data['plan_type'] ?? null;
        $this->activationPolicy = $data['activation_policy'] ?? null;
        $this->isKycVerify = $data['is_kyc_verify'] ?? null;
        $this->rechargeability = $data['rechargeability'] ?? null;
        $this->otherInfo = $data['other_info'] ?? null;
        $this->coverages = $data['coverages'] ?? null;
        $packages = $data['packages'] ?? null;

        foreach ($packages as $package) {
            $this->packages[] = new Package($package);
        }
    }

    public function image()
    {
        return $this->getImageUrl();
    }
}