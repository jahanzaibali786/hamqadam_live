<?php

declare(strict_types=1);

namespace App\Dto\Auth;

use App\Enums\Auth\DeviceType;
use App\Support\Dto\ArrayData;
use Illuminate\Http\Request;

readonly class DeviceData extends ArrayData
{
    public function __construct(
        public ?string $deviceName,
        public DeviceType $deviceType,
        public ?string $deviceId,
        public ?string $ipAddress,
        public ?string $userAgent,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            deviceName: $request->string('device_name')->toString() ?: null,
            deviceType: DeviceType::tryFrom($request->string('device_type')->toString()) ?? DeviceType::Unknown,
            deviceId: $request->string('device_id')->toString() ?: null,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );
    }
}

