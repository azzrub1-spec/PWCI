<?php

declare(strict_types=1);

// Los materiales de niveles se guardan fuera de la raíz pública del servidor.
// En XAMPP: C:/xampp/konnekt_archivos/niveles.
$documentRoot = (string) ($_SERVER['DOCUMENT_ROOT'] ?? '');

if ($documentRoot === '') {
    $documentRoot = dirname(__DIR__) . '/public';
}

return dirname(rtrim($documentRoot, '/\\')) . '/konnekt_archivos/niveles';
