<?php

namespace App\Http\Requests;

use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class SupplierImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'strategy' => ['nullable', 'in:skip,overwrite,duplicate,reject'],
            'payload' => ['nullable', 'string', 'required_without:import_file'],
            'import_file' => ['nullable', 'file', 'mimes:json,txt,csv,xls,xlsx'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validatedPayload(): array
    {
        if ($this->hasFile('import_file')) {
            /** @var UploadedFile $file */
            $file = $this->file('import_file');

            return $this->parseUploadedFile($file);
        }

        $rawPayload = trim((string) $this->input('payload'));

        if ($rawPayload === '') {
            throw ValidationException::withMessages([
                'payload' => 'Please provide a JSON or CSV payload, or upload a file.',
            ]);
        }

        return $this->parseStringPayload($rawPayload);
    }

    /**
     * @return array<string, mixed>
     */
    private function parseUploadedFile(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $content = (string) $file->get();

        return match ($extension) {
            'csv' => $this->parseCsvPayload($content),
            'xls' => $this->parseExcelXmlPayload($content),
            'xlsx' => $this->parseXlsxPayload($file),
            default => $this->parseStringPayload($content),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function parseStringPayload(string $rawPayload): array
    {
        $rawPayload = preg_replace('/^\xEF\xBB\xBF/', '', trim($rawPayload)) ?? trim($rawPayload);
        $decoded = json_decode($rawPayload, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        if (str_contains($rawPayload, '<Workbook')) {
            return $this->parseExcelXmlPayload($rawPayload);
        }

        $csvPayload = $this->parseCsvPayload($rawPayload);

        if ($csvPayload['layups'] !== []) {
            return $csvPayload;
        }

        throw ValidationException::withMessages([
            'payload' => 'Payload must be valid JSON, CSV, or an Excel-compatible spreadsheet export.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function parseCsvPayload(string $content): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($content)) ?: [];

        if ($lines === []) {
            return ['layups' => []];
        }

        $delimiter = substr_count($lines[0], ';') > substr_count($lines[0], ',') ? ';' : ',';
        $rows = array_map(fn (string $line): array => str_getcsv($line, $delimiter), $lines);

        return $this->buildPayloadFromTabularRows($rows);
    }

    /**
     * @return array<string, mixed>
     */
    private function parseExcelXmlPayload(string $content): array
    {
        $document = new DOMDocument();
        $loaded = @$document->loadXML($content);

        if (! $loaded) {
            throw ValidationException::withMessages([
                'import_file' => 'Unable to read the Excel file. Use the provided Excel export format or upload CSV/JSON.',
            ]);
        }

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('ss', 'urn:schemas-microsoft-com:office:spreadsheet');

        $rows = [];

        foreach ($xpath->query('//ss:Worksheet/ss:Table/ss:Row') ?: [] as $rowNode) {
            $row = [];

            foreach ($xpath->query('./ss:Cell/ss:Data', $rowNode) ?: [] as $cellNode) {
                $row[] = trim($cellNode->textContent);
            }

            if ($row !== []) {
                $rows[] = $row;
            }
        }

        return $this->buildPayloadFromTabularRows($rows);
    }

    /**
     * @return array<string, mixed>
     */
    private function parseXlsxPayload(UploadedFile $file): array
    {
        $zip = new ZipArchive();
        $opened = $zip->open($file->getRealPath());

        if ($opened !== true) {
            throw ValidationException::withMessages([
                'import_file' => 'Unable to open the uploaded .xlsx file.',
            ]);
        }

        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');

        if (is_string($sharedStringsXml)) {
            $sharedDocument = new DOMDocument();
            if (@$sharedDocument->loadXML($sharedStringsXml)) {
                $sharedXPath = new DOMXPath($sharedDocument);
                $sharedXPath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

                foreach ($sharedXPath->query('//x:si') ?: [] as $stringNode) {
                    $text = '';
                    foreach ($sharedXPath->query('.//x:t', $stringNode) ?: [] as $textNode) {
                        $text .= $textNode->textContent;
                    }
                    $sharedStrings[] = $text;
                }
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if (! is_string($sheetXml)) {
            throw ValidationException::withMessages([
                'import_file' => 'The uploaded .xlsx file does not contain a readable first worksheet.',
            ]);
        }

        $sheetDocument = new DOMDocument();
        if (! @$sheetDocument->loadXML($sheetXml)) {
            throw ValidationException::withMessages([
                'import_file' => 'Unable to parse the uploaded .xlsx worksheet.',
            ]);
        }

        $xpath = new DOMXPath($sheetDocument);
        $xpath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $rows = [];

        foreach ($xpath->query('//x:sheetData/x:row') ?: [] as $rowNode) {
            $row = [];

            foreach ($xpath->query('./x:c', $rowNode) ?: [] as $cellNode) {
                $type = $cellNode->attributes?->getNamedItem('t')?->nodeValue;
                $valueNode = $xpath->query('./x:v', $cellNode)->item(0);
                $inlineNode = $xpath->query('./x:is/x:t', $cellNode)->item(0);

                if ($inlineNode) {
                    $value = $inlineNode->textContent;
                } elseif ($valueNode) {
                    $value = $valueNode->textContent;
                    if ($type === 's') {
                        $value = $sharedStrings[(int) $value] ?? $value;
                    }
                } else {
                    $value = '';
                }

                $row[] = trim((string) $value);
            }

            if ($row !== []) {
                $rows[] = $row;
            }
        }

        return $this->buildPayloadFromTabularRows($rows);
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     * @return array<string, mixed>
     */
    private function buildPayloadFromTabularRows(array $rows): array
    {
        if ($rows === []) {
            return ['layups' => []];
        }

        $headers = array_map(fn (string $header): string => strtolower(trim($header)), array_shift($rows) ?: []);
        $layups = [];

        foreach ($rows as $row) {
            if (array_filter($row, fn ($value) => trim((string) $value) !== '') === []) {
                continue;
            }

            $mappedRow = [];
            foreach ($headers as $index => $header) {
                $mappedRow[$header] = trim((string) ($row[$index] ?? ''));
            }

            $layupName = $mappedRow['layup_name'] ?? $mappedRow['name'] ?? '';

            if ($layupName === '') {
                continue;
            }

            $layups[$layupName] ??= [
                'name' => $layupName,
                'layers' => [],
            ];

            if (($mappedRow['layer_order'] ?? '') === '') {
                continue;
            }

            $layups[$layupName]['layers'][] = [
                'layer_order' => (int) ($mappedRow['layer_order'] ?? 0),
                'thickness' => (float) ($mappedRow['thickness'] ?? 0),
                'width' => (float) ($mappedRow['width'] ?? 0),
                'angle' => (float) ($mappedRow['angle'] ?? 0),
            ];
        }

        return [
            'layups' => array_values($layups),
        ];
    }
}
