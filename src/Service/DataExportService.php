<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\StreamedResponse;

class DataExportService
{
    /**
     * Exports an array of entities to a CSV file
     */
    public function exportToCsv(array $data, string $filename, array $headers, callable $rowCallback): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($data, $headers, $rowCallback) {
            $handle = fopen('php://output', 'w+');
            
            // Add BOM for Excel UTF-8 compatibility
            fputs($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            
            // Write headers
            fputcsv($handle, $headers);
            
            // Write rows
            foreach ($data as $item) {
                fputcsv($handle, $rowCallback($item));
            }
            
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '.csv"');

        return $response;
    }
}
