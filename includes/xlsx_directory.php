<?php
declare(strict_types=1);

function directory_xml(string $content): SimpleXMLElement
{
    $previous = libxml_use_internal_errors(true);
    $xml = simplexml_load_string($content, SimpleXMLElement::class, LIBXML_NONET | LIBXML_COMPACT);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    if (!$xml instanceof SimpleXMLElement) {
        throw new RuntimeException('El archivo Excel contiene una estructura XML inválida.');
    }

    return $xml;
}

function directory_normalize(string $value): string
{
    $value = strtr($value, [
        'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
        'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
        'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
        'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
        'ñ' => 'n',
        'Á' => 'a', 'À' => 'a', 'Ä' => 'a', 'Â' => 'a',
        'É' => 'e', 'È' => 'e', 'Ë' => 'e', 'Ê' => 'e',
        'Í' => 'i', 'Ì' => 'i', 'Ï' => 'i', 'Î' => 'i',
        'Ó' => 'o', 'Ò' => 'o', 'Ö' => 'o', 'Ô' => 'o',
        'Ú' => 'u', 'Ù' => 'u', 'Ü' => 'u', 'Û' => 'u',
        'Ñ' => 'n',
    ]);

    return strtolower(trim(preg_replace('/\s+/', ' ', $value) ?? $value));
}

function directory_clean_contract_number(string $value): string
{
    $cleaned = preg_replace('/^\s*n[uú]mero\s+de\s+contrato\s*[:#-]?\s*/iu', '', $value);
    return trim(ltrim($cleaned ?? $value, " \t\n\r\0\x0B:"));
}

function directory_column_name(string $cellReference): string
{
    if (!preg_match('/^([A-Z]+)/', $cellReference, $matches)) {
        return '';
    }

    return $matches[1];
}

function directory_cell_value(SimpleXMLElement $cell, array $sharedStrings): string
{
    $type = (string) $cell['t'];
    if ($type === 'e') {
        return '';
    }
    if ($type === 's') {
        return $sharedStrings[(int) $cell->v] ?? '';
    }
    if ($type === 'inlineStr' && isset($cell->is)) {
        $cell->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $parts = [];
        foreach ($cell->xpath('.//x:t') as $text) {
            $parts[] = (string) $text;
        }

        return implode('', $parts);
    }

    return isset($cell->v) ? (string) $cell->v : '';
}

function directory_parse_excel_date(string $value): ?string
{
    $value = trim($value);
    $value = preg_replace('/\s*[-–—]+\s*$/u', '', $value) ?? $value;
    $value = trim($value, " \t\n\r\0\x0B.;");
    if ($value === '') {
        return null;
    }

    if (is_numeric($value)) {
        $days = (int) floor((float) $value);
        if ($days < 1 || $days > 100000) {
            return null;
        }

        return (new DateTimeImmutable('1899-12-30'))->modify('+' . $days . ' days')->format('Y-m-d');
    }

    foreach (['!d/m/Y', '!d/m/y', '!Y-m-d', '!d-m-Y', '!d-m-y'] as $format) {
        $date = DateTimeImmutable::createFromFormat($format, $value);
        $errors = DateTimeImmutable::getLastErrors();
        if ($date && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
            return $date->format('Y-m-d');
        }
    }

    $normalized = directory_normalize($value);
    $months = [
        'enero' => 1,
        'febrero' => 2,
        'marzo' => 3,
        'abril' => 4,
        'mayo' => 5,
        'junio' => 6,
        'julio' => 7,
        'agosto' => 8,
        'septiembre' => 9,
        'setiembre' => 9,
        'octubre' => 10,
        'noviembre' => 11,
        'diciembre' => 12,
    ];
    $textualDatePatterns = [
        '/^([0-9]{1,2})\s+de\s+([a-z]+)\s+de\s+([0-9]{4})$/',
        '/^([a-z]+)\s+([0-9]{1,2})\s+de\s+([0-9]{4})$/',
        '/^([0-9]{1,2})\s+([a-z]+)\s+([0-9]{4})$/',
        '/^([0-9]{1,2})\s+de\s+([a-z]+)\s+([0-9]{4})$/',
    ];
    foreach ($textualDatePatterns as $index => $pattern) {
        if (!preg_match($pattern, $normalized, $matches)) {
            continue;
        }

        if ($index === 1) {
            $month = $months[$matches[1]] ?? null;
            $day = (int) $matches[2];
        } else {
            $day = (int) $matches[1];
            $month = $months[$matches[2]] ?? null;
        }
        $year = (int) $matches[3];
        if ($month !== null && checkdate($month, $day, $year)) {
            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }
    }

    return null;
}

function directory_find_header(array $rows): array
{
    foreach ($rows as $rowNumber => $row) {
        if ($rowNumber > 30) {
            break;
        }
        $normalized = [];
        foreach ($row as $column => $value) {
            $normalized[$column] = directory_normalize($value);
        }

        $find = static function (callable $matches) use ($normalized): ?string {
            foreach ($normalized as $column => $header) {
                if ($matches($header)) {
                    return $column;
                }
            }

            return null;
        };
        $columns = [
            'email' => $find(static fn (string $value): bool => str_contains($value, 'correo electronico institucional')),
            'first_name' => $find(static fn (string $value): bool => $value === 'nombres'),
            'last_name' => $find(static fn (string $value): bool => $value === 'apellidos'),
            'document_number' => $find(static fn (string $value): bool => str_contains($value, 'documento de identidad')),
            'role_name' => $find(static fn (string $value): bool => $value === 'rol'),
            'contract_end_date' => $find(static fn (string $value): bool => str_contains($value, 'fecha de terminacion de contrato')),
        ];

        if ($columns['email'] && $columns['first_name'] && $columns['last_name'] && $columns['document_number'] && $columns['role_name']) {
            $optional = [
                'phone' => static fn (string $value): bool => str_contains($value, 'numero celular'),
                'personal_email' => static fn (string $value): bool => $value === 'correo personal',
                'affiliation_type' => static fn (string $value): bool => str_contains($value, 'tipo de vinculacion'),
                'job_title' => static fn (string $value): bool => $value === 'cargo',
                'supervisor' => static fn (string $value): bool => str_contains($value, 'supervisor') || str_contains($value, 'jefe inmediato'),
                'contract_number' => static fn (string $value): bool => $value === 'numero de contrato',
                'vpn_access' => static fn (string $value): bool => $value === 'vpn',
                'extension_ip' => static fn (string $value): bool => str_contains($value, 'numero ext'),
            ];
            foreach ($optional as $key => $matcher) {
                $columns[$key] = $find($matcher);
            }

            return ['row' => $rowNumber, 'columns' => $columns];
        }
    }

    throw new RuntimeException('No se encontró el encabezado esperado en la hoja.');
}

function directory_key(string $value): string
{
    return preg_replace('/[^a-z0-9]/', '', directory_normalize($value)) ?? '';
}

function read_directory_workbook(string $path): array
{
    if (!class_exists(ZipArchive::class)) {
        throw new RuntimeException('PHP necesita la extensión ZipArchive para leer archivos .xlsx.');
    }

    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        throw new RuntimeException('No se pudo abrir el archivo como un libro Excel .xlsx.');
    }

    try {
        $totalUncompressed = 0;
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $stat = $zip->statIndex($index);
            $totalUncompressed += (int) ($stat['size'] ?? 0);
            if ($totalUncompressed > 25 * 1024 * 1024) {
                throw new RuntimeException('El archivo Excel excede el tamaño permitido al descomprimirse.');
            }
        }

        $workbookContent = $zip->getFromName('xl/workbook.xml');
        $relationshipsContent = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($workbookContent === false || $relationshipsContent === false) {
            throw new RuntimeException('El archivo no contiene la estructura de un libro Excel .xlsx.');
        }

        $mainNamespace = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $relationshipNamespace = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
        $workbook = directory_xml($workbookContent);
        $workbook->registerXPathNamespace('x', $mainNamespace);
        $relationships = directory_xml($relationshipsContent);
        $relationshipTargets = [];
        foreach ($relationships->Relationship as $relationship) {
            if ((string) $relationship['TargetMode'] !== 'External') {
                $relationshipTargets[(string) $relationship['Id']] = (string) $relationship['Target'];
            }
        }

        $sharedStrings = [];
        $sharedContent = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedContent !== false) {
            $sharedXml = directory_xml($sharedContent);
            $sharedXml->registerXPathNamespace('x', $mainNamespace);
            foreach ($sharedXml->xpath('//x:si') as $item) {
                $item->registerXPathNamespace('x', $mainNamespace);
                $parts = [];
                foreach ($item->xpath('.//x:t') as $text) {
                    $parts[] = (string) $text;
                }
                $sharedStrings[] = implode('', $parts);
            }
        }

        $requestedSheets = ['Administrativos' => 'Administrativo', 'Instructores' => 'Instructor'];
        $records = ['Administrativos' => [], 'Instructores' => []];
        $report = [
            'skipped_unclassified' => 0,
            'skipped_incomplete' => 0,
            'missing_institutional_email' => 0,
            'invalid_contract_dates' => 0,
        ];
        $foundSheets = [];

        foreach ($workbook->xpath('//x:sheets/x:sheet') as $sheetInfo) {
            $sheetName = (string) $sheetInfo['name'];
            if (!isset($requestedSheets[$sheetName])) {
                continue;
            }

            $relationshipId = (string) $sheetInfo->attributes($relationshipNamespace)['id'];
            $target = $relationshipTargets[$relationshipId] ?? '';
            $target = str_replace('\\', '/', ltrim($target, '/'));
            if ($target === '' || str_contains($target, '..')) {
                throw new RuntimeException('La ruta de una hoja del libro no es válida.');
            }
            $sheetPath = str_starts_with($target, 'xl/') ? $target : 'xl/' . $target;
            $sheetContent = $zip->getFromName($sheetPath);
            if ($sheetContent === false) {
                throw new RuntimeException('No se pudo leer la hoja ' . $sheetName . '.');
            }

            $worksheet = directory_xml($sheetContent);
            $worksheet->registerXPathNamespace('x', $mainNamespace);
            $rows = [];
            foreach ($worksheet->xpath('//x:sheetData/x:row') as $rowElement) {
                $rowNumber = (int) $rowElement['r'];
                $rowElement->registerXPathNamespace('x', $mainNamespace);
                $rowValues = [];
                foreach ($rowElement->xpath('./x:c') as $cell) {
                    $column = directory_column_name((string) $cell['r']);
                    if ($column !== '') {
                        $rowValues[$column] = directory_cell_value($cell, $sharedStrings);
                    }
                }
                $rows[$rowNumber] = $rowValues;
            }

            $header = directory_find_header($rows);
            $columns = $header['columns'];
            $expectedRole = $requestedSheets[$sheetName];
            foreach ($rows as $rowNumber => $row) {
                if ($rowNumber <= $header['row']) {
                    continue;
                }

                $hasIdentityData = trim($row[$columns['email']] ?? '') !== ''
                    || trim($row[$columns['first_name']] ?? '') !== ''
                    || trim($row[$columns['last_name']] ?? '') !== ''
                    || trim($row[$columns['document_number']] ?? '') !== '';
                if (!$hasIdentityData) {
                    continue;
                }

                $sourceRole = trim($row[$columns['role_name']] ?? '');
                $role = directory_normalize($sourceRole);
                if ($role !== strtolower($expectedRole)) {
                    $report['skipped_unclassified']++;
                    continue;
                }

                $firstName = trim($row[$columns['first_name']] ?? '');
                $lastName = trim($row[$columns['last_name']] ?? '');
                $documentNumber = trim($row[$columns['document_number']] ?? '');
                if ($firstName === '' || $lastName === '' || $documentNumber === '') {
                    $report['skipped_incomplete']++;
                    continue;
                }

                $email = strtolower(trim($row[$columns['email']] ?? ''));
                if ($email === '') {
                    $email = null;
                    $report['missing_institutional_email']++;
                }
                $jobTitle = trim($row[$columns['job_title']] ?? '');
                $contractorRole = directory_normalize($jobTitle) === 'contratista';
                if ($contractorRole) {
                    $jobTitle = $sourceRole;
                }
                $classificationField = $sheetName === 'Administrativos' ? 'dependency' : 'area';
                $contractEnd = null;
                $invalidContractDate = false;
                if ($columns['contract_end_date']) {
                    $rawDate = $row[$columns['contract_end_date']] ?? '';
                    $contractEnd = directory_parse_excel_date($rawDate);
                    if (trim($rawDate) !== '' && $contractEnd === null) {
                        $report['invalid_contract_dates']++;
                        $invalidContractDate = true;
                    }
                }

                $record = [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'specialty' => $jobTitle,
                    'document_number' => $documentNumber,
                    'phone' => trim($row[$columns['phone']] ?? ''),
                    'personal_email' => trim($row[$columns['personal_email']] ?? ''),
                    'affiliation_type' => trim($row[$columns['affiliation_type']] ?? ''),
                    'job_title' => $jobTitle,
                    $classificationField => $contractorRole ? '' : $expectedRole,
                    'supervisor' => $sheetName === 'Administrativos' ? trim($row[$columns['supervisor']] ?? '') : '',
                    'contract_number' => directory_clean_contract_number(trim($row[$columns['contract_number']] ?? '')),
                    'contract_end_date' => $contractEnd,
                    'contract_end_date_raw' => $invalidContractDate ? trim($row[$columns['contract_end_date']] ?? '') : null,
                    'vpn_access' => trim($row[$columns['vpn_access']] ?? ''),
                    'extension_ip' => trim($row[$columns['extension_ip']] ?? ''),
                    'source_row' => $rowNumber,
                    'duplicate_in_source' => false,
                    'duplicate_with_other_category' => false,
                    'invalid_contract_date' => $invalidContractDate,
                ];
                foreach ([
                    'first_name' => 100,
                    'last_name' => 100,
                    'email' => 190,
                    'specialty' => 150,
                    'document_number' => 50,
                    'phone' => 40,
                    'personal_email' => 190,
                    'affiliation_type' => 100,
                    'job_title' => 150,
                    $classificationField => 50,
                    'supervisor' => 150,
                    'contract_number' => 80,
                    'contract_end_date_raw' => 50,
                    'vpn_access' => 100,
                    'extension_ip' => 100,
                ] as $field => $maxLength) {
                    if (mb_strlen((string) $record[$field]) > $maxLength) {
                        throw new RuntimeException('La fila ' . $rowNumber . ' de ' . $sheetName . ' supera el tamaño permitido en un campo.');
                    }
                }
                $records[$sheetName][] = $record;
            }
            $foundSheets[$sheetName] = true;
        }

        foreach (array_keys($requestedSheets) as $requiredSheet) {
            if (!isset($foundSheets[$requiredSheet])) {
                throw new RuntimeException('Falta la hoja requerida ' . $requiredSheet . ' en el archivo.');
            }
        }

        foreach (['Administrativos', 'Instructores'] as $sheetName) {
            $emailCounts = [];
            $documentCounts = [];
            foreach ($records[$sheetName] as $record) {
                $emailKey = directory_key((string) ($record['email'] ?? ''));
                $documentKey = directory_key($record['document_number']);
                if ($emailKey !== '') {
                    $emailCounts[$emailKey] = ($emailCounts[$emailKey] ?? 0) + 1;
                }
                if ($documentKey !== '') {
                    $documentCounts[$documentKey] = ($documentCounts[$documentKey] ?? 0) + 1;
                }
            }
            foreach ($records[$sheetName] as &$record) {
                $emailKey = directory_key((string) ($record['email'] ?? ''));
                $documentKey = directory_key($record['document_number']);
                $record['duplicate_in_source'] =
                    ($emailKey !== '' && $emailCounts[$emailKey] > 1)
                    || ($documentKey !== '' && $documentCounts[$documentKey] > 1);
            }
            unset($record);
        }

        $otherCategoryKeys = [];
        foreach (['Administrativos', 'Instructores'] as $sheetName) {
            $otherCategoryKeys[$sheetName] = ['email' => [], 'document' => []];
            foreach ($records[$sheetName] as $record) {
                $emailKey = directory_key((string) ($record['email'] ?? ''));
                $documentKey = directory_key($record['document_number']);
                if ($emailKey !== '') {
                    $otherCategoryKeys[$sheetName]['email'][$emailKey] = true;
                }
                if ($documentKey !== '') {
                    $otherCategoryKeys[$sheetName]['document'][$documentKey] = true;
                }
            }
        }
        foreach (['Administrativos' => 'Instructores', 'Instructores' => 'Administrativos'] as $sheetName => $otherSheet) {
            foreach ($records[$sheetName] as &$record) {
                $emailKey = directory_key((string) ($record['email'] ?? ''));
                $documentKey = directory_key($record['document_number']);
                $record['duplicate_with_other_category'] =
                    ($emailKey !== '' && isset($otherCategoryKeys[$otherSheet]['email'][$emailKey]))
                    || ($documentKey !== '' && isset($otherCategoryKeys[$otherSheet]['document'][$documentKey]));
            }
            unset($record);
        }

        return ['records' => $records, 'report' => $report];
    } finally {
        $zip->close();
    }
}
