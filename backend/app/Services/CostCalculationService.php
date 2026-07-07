<?php

namespace App\Services;

use App\Models\BeritaAcara;
use App\Models\Formula;
use App\Models\InstrumentPrice;

class CostCalculationService
{
    /**
     * Resolve formula variable values from a berita_acara's data and instrument prices.
     *
     * Variable sources:
     * - 'berita_acara' → value from $beritaAcara->form_data['field_{form_field_id}']
     * - 'rekap_analis' → value from $beritaAcara->rekap_analis_data['field_{form_field_id}']
     * - 'instrumen' → price from instrument_prices matching the instrument name
     */
    public function resolveVariables(Formula $formula, BeritaAcara $beritaAcara): array
    {
        $values = [];

        // Get selected instruments from "Metode/Instrumen" checkbox field
        // Search in both form_data and rekap_analis_data
        $selectedInstruments = [];
        $formData = $beritaAcara->form_data ?? [];
        $rekapData = $beritaAcara->rekap_analis_data ?? [];

        // Find the checkbox field that contains instrument selections
        // It stores an array like ["Analisis Sukrosa (Luff Shrool)", "XRF"]
        foreach ([$formData, $rekapData] as $data) {
            foreach ($data as $fieldKey => $fieldValue) {
                if (is_array($fieldValue) && !empty($fieldValue)) {
                    // Check if any value matches an instrument name
                    foreach ($fieldValue as $v) {
                        if (is_string($v) && InstrumentPrice::where('name', $v)->exists()) {
                            $selectedInstruments = array_merge($selectedInstruments, $fieldValue);
                            break 2;
                        }
                    }
                }
            }
        }
        $selectedInstruments = array_unique($selectedInstruments);

        // Index instruments by their order (0-based)
        $instrumentPrices = [];
        foreach ($selectedInstruments as $idx => $instrumentName) {
            $price = InstrumentPrice::where('name', $instrumentName)->value('price_regular');
            $instrumentPrices[$idx] = [
                'name' => $instrumentName,
                'price' => (float) ($price ?? 0),
            ];
        }

        foreach ($formula->variables as $var) {
            $key = $var['key'];
            $source = $var['source'] ?? 'berita_acara';
            $formFieldId = $var['form_field_id'] ?? null;

            if ($source === 'instrumen') {
                // Resolve instrument price from selected instruments
                $instrumentId = $var['instrument_id'] ?? null;
                $label = $var['label'] ?? $key;

                if ($instrumentId) {
                    // Specific instrument ID assigned
                    $price = InstrumentPrice::where('id', $instrumentId)->value('price_regular');
                    $values[$key] = (float) ($price ?? 0);
                } else {
                    // Match by order: "Instrumen 1" → index 0, "Instrumen 2" → index 1, etc.
                    $orderMatch = preg_match('/(\d+)$/', $label, $matches);
                    if ($orderMatch) {
                        $idx = (int) $matches[1] - 1; // 1-based to 0-based
                        $values[$key] = $instrumentPrices[$idx]['price'] ?? 0;
                    } else {
                        // Try matching by name directly
                        $price = InstrumentPrice::where('name', $label)->value('price_regular');
                        $values[$key] = (float) ($price ?? 0);
                    }
                }
            } else {
                // Form field variables: resolve from form_data or rekap_analis_data
                $dataKey = 'field_' . $formFieldId;
                if ($source === 'rekap_analis') {
                    $rawValue = $beritaAcara->rekap_analis_data[$dataKey] ?? null;
                } else {
                    $rawValue = $beritaAcara->form_data[$dataKey] ?? null;
                }
                // Try numeric conversion, fallback to 0
                $values[$key] = is_numeric($rawValue) ? (float) $rawValue : (float) ($beritaAcara->{$key} ?? 0);
            }
        }

        return $values;
    }

    /**
     * Evaluate a formula expression with given variable values.
     * Uses PHP's math functions safely.
     */
    public function evaluate(string $expression, array $values): float
    {
        // Replace variable keys with their numeric values
        $expr = $expression;

        // Sort keys by length (longest first) to avoid partial replacements
        // e.g. "jumlah_sampel" should be replaced before "jumlah"
        $sortedKeys = array_keys($values);
        usort($sortedKeys, fn($a, $b) => strlen($b) - strlen($a));

        foreach ($sortedKeys as $key) {
            $value = $values[$key];
            $expr = str_replace($key, (string) $value, $expr);
        }

        // Validate: only allow numbers, operators, parentheses, dots, spaces, and math functions
        $sanitized = preg_replace('/\s+/', '', $expr);
        if (!preg_match('/^[\d\+\-\*\/\%\.\(\)]+$/', $sanitized)) {
            throw new \InvalidArgumentException("Rumus mengandung karakter yang tidak valid: {$expr}");
        }

        // Evaluate using PHP's math engine
        // Suppress warnings from DivisionByZeroError etc
        $result = @eval('return ' . $sanitized . ';');

        if ($result === false || !is_numeric($result)) {
            throw new \RuntimeException('Gagal menghitung rumus. Periksa format rumus.');
        }

        return (float) $result;
    }

    /**
     * Calculate the total cost for a berita_acara using a given formula.
     *
     * @return array{ biaya: float, resolved_values: array }
     */
    public function calculate(BeritaAcara $beritaAcara, Formula $formula): array
    {
        $values = $this->resolveVariables($formula, $beritaAcara);
        $biaya = $this->evaluate($formula->expression, $values);

        return [
            'biaya' => round($biaya, 2),
            'resolved_values' => $values,
        ];
    }
}
