<?php

namespace App\Services;

use InvalidArgumentException;
use Picqer\Barcode\Renderers\SvgRenderer;
use Picqer\Barcode\Types\TypeCode128;

class BarcodePrintService
{
    public function generateSvg(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            throw new InvalidArgumentException(
                'Không thể tạo hình cho barcode rỗng.'
            );
        }

        $barcode = (new TypeCode128())->getBarcode($value);

        $renderer = new SvgRenderer();

        return $renderer->render(
            $barcode,
            $barcode->getWidth() * 2,
            50
        );
    }
}
