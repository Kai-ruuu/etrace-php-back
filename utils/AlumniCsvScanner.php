<?php

class AlumniCsvScanner
{
    private string $filePath;
    private array $rows = [];

    public function __construct(string $filePath)
    {
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException("File not found: $filePath");
        }

        if (strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) !== 'csv') {
            throw new InvalidArgumentException("File must be a CSV: $filePath");
        }

        $this->filePath = $filePath;
        $this->parse();
    }

    private function parse(): void
    {
        $handle = fopen($this->filePath, 'r');

        $headers = array_map('trim', fgetcsv($handle));

        while (($row = fgetcsv($handle)) !== false) {
            $this->rows[] = array_combine($headers, array_map('trim', $row));
        }

        fclose($handle);
    }

    public function getTotal(): int
    {
        return count($this->rows);
    }

    public function getMaleCount(): int
    {
        return count(array_filter($this->rows, fn($row) => strtolower($row['Gender'] ?? '') === 'male'));
    }

    public function getFemaleCount(): int
    {
        return count(array_filter($this->rows, fn($row) => strtolower($row['Gender'] ?? '') === 'female'));
    }

    public function getSummary(): array
    {
        return [
            'total'  => $this->getTotal(),
            'male'   => $this->getMaleCount(),
            'female' => $this->getFemaleCount(),
        ];
    }
}