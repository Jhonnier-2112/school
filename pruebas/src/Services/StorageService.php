<?php

namespace App\Services;

class StorageService {
    public static function upload(array $file, string $subFolder, array $config): string {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception('Error al recibir el archivo subido.');
        }

        // Limit size: 10MB
        if ($file['size'] > 10 * 1024 * 1024) {
            throw new \Exception('El archivo no debe exceder 10MB.');
        }

        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExts)) {
            throw new \Exception('Formato no permitido. Solo se aceptan JPG, PNG, WEBP y PDF.');
        }

        $targetDir = $config['upload_dir'] . '/' . $subFolder;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $fileName = time() . '_' . substr(bin2hex(random_bytes(4)), 0, 8) . '.' . $ext;
        $destination = $targetDir . '/' . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new \Exception('No se pudo guardar el archivo en el servidor.');
        }

        return $config['base_url'] . '/uploads/' . $subFolder . '/' . $fileName;
    }
}
