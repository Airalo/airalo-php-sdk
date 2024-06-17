<?php

namespace Airalo\Tests\Mock;

use Airalo\Airalo;
use Airalo\Helpers\EasyAccess;

class AiraloMock
{
    private $packages;
    private $orders;
    private $topups;
    private $instructions;
    private $vouchers;

    public function __construct()
    {
        $this->packages = [];
        $this->orders = [];
        $this->topups = [];
        $this->simUsage = [];
        $this->vouchers = [];
        $this->instructions = [];
    }

    /**
     * @param array $packages
     * @return AiraloMock
     */
    public function setPackages(array $packages): AiraloMock
    {
        $this->packages = $packages;

        return $this;
    }

    /**
     * @param array $orders
     * @return AiraloMock
     */
    public function setOrders(array $orders): AiraloMock
    {
        $this->orders = $orders;

        return $this;
    }

    /**
     * @param array $simUsage
     * @return AiraloMock
     */
    public function setSimUsage(array $usage): AiraloMock
    {
        $this->simUsage = $usage;

        return $this;
    }

    /**
     * @param array $topups
     * @return AiraloMock
     */
    public function setTopups(array $topups): AiraloMock
    {
        $this->topups = $topups;

        return $this;
    }

    /**
     * @param array $instructions
     * @return AiraloMock
     */
    public function setInstructions(array $instructions): AiraloMock
    {
        $this->instructions = $instructions;

        return $this;
    }

    /**
     * @param boolean $flat
     * @param mixed $limit
     * @param mixed $page
     * @return EasyAccess|null
     */
    public function getAllPackages(bool $flat = false, $limit = null, $page = null): ?EasyAccess
    {
        return new EasyAccess($this->packages);
    }

    /**
     * @param boolean $flat
     * @param mixed $limit
     * @param mixed $page
     * @return EasyAccess|null
     */
    public function getSimPackages(bool $flat = false, $limit = null, $page = null): ?EasyAccess
    {
        return new EasyAccess(array_filter($this->packages, function ($package) {
            return isset($package['simOnly']) && $package['simOnly'] == true;
        }));
    }

    /**
     * @param boolean $flat
     * @param mixed $limit
     * @param mixed $page
     * @return EasyAccess|null
     */
    public function getLocalPackages(bool $flat = false, $limit = null, $page = null): ?EasyAccess
    {
        return new EasyAccess(array_filter($this->packages, function ($package) {
            return isset($package['type']) && $package['type'] == 'local';
        }));
    }

    /**
     * @param boolean $flat
     * @param mixed $limit
     * @param mixed $page
     * @return EasyAccess|null
     */
    public function getGlobalPackages(bool $flat = false, $limit = null, $page = null): ?EasyAccess
    {
        return new EasyAccess(array_filter($this->packages, function ($package) {
            return isset($package['type']) && $package['type'] == 'global';
        }));
    }

    /**
     * @param string $countryCode
     * @param boolean $flat
     * @param mixed $limit
     * @return EasyAccess|null
     */
    public function getCountryPackages(string $countryCode, bool $flat = false, $limit = null): ?EasyAccess
    {
        $countryCode = strtoupper($countryCode);

        return new EasyAccess(array_filter($this->packages, function ($package) use ($countryCode) {
            return isset($package['country']) && $package['country'] === $countryCode;
        }));
    }

    /**
     * @param string $packageId
     * @param integer $quantity
     * @param string|null $description
     * @return EasyAccess|null
     */
    public function order(string $packageId, int $quantity, ?string $description = null): ?EasyAccess
    {
        $order = [
            'package_id' => $packageId,
            'quantity' => $quantity,
            'type' => 'sim',
            'description' => $description ?? 'Order placed via AiraloMock',
        ];

        return new EasyAccess(!empty($this->orders) ? $this->orders : $order);
    }

    /**
     * @param array $packages
     * @param string|null $description
     * @return EasyAccess|null
     */
    public function orderBulk(array $packages, ?string $description = null): ?EasyAccess
    {
        if (empty($packages)) {
            return null;
        }

        $bulkOrder = [
            'packages' => $packages,
            'description' => $description ?? 'Bulk order placed via AiraloMock',
        ];

        return new EasyAccess(!empty($this->orders) ? $this->orders : $bulkOrder);
    }

    /**
     * @param string $packageId
     * @param string $iccid
     * @param string|null $description
     * @return EasyAccess|null
     */
    public function topup(string $packageId, string $iccid, ?string $description = null): ?EasyAccess
    {
        $topup = [
            'package_id' => $packageId,
            'iccid' => $iccid,
            'description' => $description ?? 'Topup placed via AiraloMock',
        ];

        return new EasyAccess(!empty($this->topups) ? $this->topups : $topup);
    }


    /**
     * @param string $iccid
     * @return EasyAccess|null
     */
    public function simUsage(string $iccid): ?EasyAccess
    {
        $usage = [
            'iccid' => $iccid,
        ];

        return new EasyAccess(!empty($this->simUsage) ? $this->simUsage : $usage);
    }

    /**
     * @param string $iccid
     * @return EasyAccess|null
     */
    public function getSimInstructions(string $packageId, string $iccid, ?string $description = null): ?EasyAccess
    {
        $topup = [
            'package_id' => $packageId,
            'iccid' => $iccid,
            'description' => $description ?? 'Topup placed via AiraloMock',
        ];

        return new EasyAccess(!empty($this->topups) ? $this->topups : $topup);
    }

    /**
     * @param int $usageLimit
     * @param int $amount
     * @param int $quantity
     * @param ?bool $isPaid
     * @param ?string $voucherCode
     * @return EasyAccess|null
     */
    public function voucher(int $usageLimit, int $amount, int $quantity, ?bool $isPaid = false, string $voucherCode = null): ?EasyAccess
    {
        $voucher = [
            'voucher_code' => $voucherCode,
            'usage_limit' => $usageLimit,
            'amount' => $amount,
            'quantity' => $quantity,
            'is_paid' => $isPaid,
        ];

        return new EasyAccess(!empty($this->vouchers) ? $this->vouchers : $voucher);
    }
}
