<?php

namespace App\Services\GoogleSheets;

use Illuminate\Support\Collection;

/**
 * Service to read from and write to the Google Sheet used for fee/income tracking.
 *
 * Implement this using Google Sheets API v4 (e.g. google/apiclient or direct REST).
 * Design: docs/GOOGLE_SHEETS_FEE_SYNC_DESIGN.md
 *
 * Required Composer package: composer require google/apiclient
 * Setup: Google Cloud project → enable Sheets API → create service account →
 *       download JSON → store path in GOOGLE_SHEETS_CREDENTIALS_PATH.
 *       Share the spreadsheet with the service account email (Editor).
 */
class GoogleSheetsFeeSyncService
{
    public function __construct(
        protected string $spreadsheetId,
        protected string $range,
        protected array $columnMap,
        protected string $credentialsPath
    ) {
    }

    /**
     * Read the income sheet and return rows as structured data for comparison.
     *
     * @return Collection<int, array{admission_number: string, student_name: string, votehead: string, invoiced: float|null, paid: float|null, balance: float|null, term: int|null, year: int|null, row_index: int}>
     */
    public function readIncomeSheetRows(): Collection
    {
        // TODO: Use Google Sheets API spreadsheets.values.get
        // $client = new \Google\Client();
        // $client->setAuthConfig($this->credentialsPath);
        // $client->addScope(\Google\Service\Sheets::SPREADSHEETS);
        // $service = new \Google\Service\Sheets($client);
        // $response = $service->spreadsheets_values->get($this->spreadsheetId, $this->range);
        // Then map $response->getValues() to rows using $this->columnMap and return Collection.
        return collect();
    }

    /**
     * Update specific cells in the sheet (e.g. after applying system values or when syncing on invoice/payment change).
     *
     * @param  array<int, array<string, mixed>>  $updates  Each item: row_index (1-based), column_key (e.g. 'paid'), value
     */
    public function updateCells(array $updates): void
    {
        // TODO: Build ValueRange for updated cells, call spreadsheets.values.update (valueInputOption=USER_ENTERED).
        // Used by: (1) "Apply to sheet" after approval, (2) listener when invoice/payment changes.
    }

    /**
     * Check whether the API is configured and credentials are valid (e.g. one-time health check).
     */
    public function isConfigured(): bool
    {
        return $this->spreadsheetId !== ''
            && $this->credentialsPath !== ''
            && file_exists($this->credentialsPath);
    }
}
