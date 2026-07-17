<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Google\Service\Sheets as GoogleSheets;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Facades\Log;
use Throwable;

class GoogleSheetsService
{
    protected ?GoogleSheets $service = null;

    public function isConfigured(): bool
    {
        return config('google_sheets.enabled')
            && config('google_sheets.spreadsheet_id')
            && file_exists(config('google_sheets.credentials_path'));
    }

    /**
     * Tambahkan satu baris ke sheet tujuan. $sheetKey mengacu ke key di config('google_sheets.sheets').
     */
    public function appendRow(string $sheetKey, array $values): bool
    {
        if (! $this->isConfigured()) {
            Log::channel('google_sheets')->warning("Google Sheets belum dikonfigurasi, skip sync sheet [{$sheetKey}].");

            return false;
        }

        $sheetName = config("google_sheets.sheets.{$sheetKey}");

        if (! $sheetName) {
            Log::channel('google_sheets')->error("Sheet key [{$sheetKey}] tidak dikenal di konfigurasi.");

            return false;
        }

        try {
            $service = $this->getService();
            $range = "{$sheetName}!A1";

            $body = new ValueRange(['values' => [$values]]);

            $service->spreadsheets_values->append(
                config('google_sheets.spreadsheet_id'),
                $range,
                $body,
                ['valueInputOption' => 'USER_ENTERED']
            );

            Log::channel('google_sheets')->info("Berhasil sync ke sheet [{$sheetName}].");

            return true;
        } catch (Throwable $e) {
            Log::channel('google_sheets')->error("Gagal sync ke sheet [{$sheetName}]: {$e->getMessage()}");

            return false;
        }
    }

    protected function getService(): GoogleSheets
    {
        if ($this->service) {
            return $this->service;
        }

        $client = new GoogleClient();
        $client->setApplicationName(config('app.name'));
        $client->setScopes([GoogleSheets::SPREADSHEETS]);
        $client->setAuthConfig(config('google_sheets.credentials_path'));
        $client->setAccessType('offline');

        return $this->service = new GoogleSheets($client);
    }
}
