<?php

declare(strict_types=1);

namespace Utils;

use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;

class BasicExportService
{
    public const FORMAT_CSV = 'csv';
    public const FORMAT_XLSX = 'xlsx';
    public const FORMAT_ODS = 'ods';
    public const FLUSH_THRESHOLD = 1000;
    private const DEFAULT = 'default';

    public function __construct(
        protected readonly array $exportConfig
    ) {
    }

    public function getLimit(string $exportName, string $format = self::DEFAULT): int
    {
        $exportLimitsConfig = $this->getExportConfig($exportName, 'limits');

        if (array_key_exists($format, $exportLimitsConfig)) {
            return $exportLimitsConfig[$format];
        }

        return $exportLimitsConfig[self::DEFAULT];
    }

    private function getExportConfig(string $exportName, string $configName): array
    {
        if (array_key_exists($exportName, $this->exportConfig)) {
            if (array_key_exists($configName, $this->exportConfig[$exportName])) {
                return $this->exportConfig[$exportName][$configName];
            }
        }

        return $this->exportConfig[self::DEFAULT][$configName];
    }

    public function getHeader($exportableTable): array
    {
        $headers = [];

        foreach ($exportableTable as $row) {
            foreach ($row as $key => $value) {
                $headers[$key] = true;
            }
        }

        return array_keys($headers);
    }

    public function getTheStyle(bool $isBold = false, bool $isItalic = false): Style
    {
        $style = new Style();
        $style->setFontColor(Color::BLACK);
        if ($isBold) {
            $style->setFontBold();
        }
        if ($isItalic) {
            $style->setFontItalic();
        }

        return $style;
    }
}
