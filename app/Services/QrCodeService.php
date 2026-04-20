<?php

namespace App\Services;

use App\Models\Tenant;
use App\Support\TenantUrl;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;

class QrCodeService
{
    /**
     * Build QR code image (PNG or SVG) for the given URL/data.
     */
    public function build(string $data, bool $forcePng = false): \Endroid\QrCode\Writer\Result\ResultInterface
    {
        $writer = $forcePng || extension_loaded('gd') ? new PngWriter : new SvgWriter;

        $builder = new Builder(
            writer: $writer,
            data: $data,
            encoding: new Encoding('UTF-8'),
            size: 280,
            margin: 10,
            errorCorrectionLevel: ErrorCorrectionLevel::Medium
        );

        return $builder->build();
    }

    /**
     * Build public queue URL for an office (uses APP_URL for real-time / cross-device).
     */
    public function queueOfficeUrl(string $officeSlug, ?Tenant $tenant = null): string
    {
        return TenantUrl::forPath($tenant, route('queue.office', ['slug' => $officeSlug], false));
    }
}
