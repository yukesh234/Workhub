<?php
function Response(int $statusCode , bool $success, string $message, mixed $data = null){
    http_response_code($statusCode);
    header('Content-Type: application/json');

    echo json_encode([
        "success" => $success,
        "message" => $message,
        "data" => $data
    ]);

    exit();
}