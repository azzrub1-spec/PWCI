<?php

header('Content-Type: application/json; charset=utf-8');
http_response_code(200);

echo json_encode([
    'success' => true,
    'message' => 'Servidor PHP funcionando correctamente'
], JSON_UNESCAPED_UNICODE);
