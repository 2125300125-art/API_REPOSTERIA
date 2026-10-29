<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

// Creé este middleware porque en Postman al enviar peticiones PUT con form-data (datos e imágenes),
// PHP no llena automáticamente $_POST ni $_FILES y me marcaba error 422 de campos obligatorios vacíos.
class ParseMultipartFormData
{
    /**
     * Procesa cuerpos multipart/form-data en peticiones PUT, PATCH o DELETE
     * (los cuales PHP no popula automáticamente en $_POST ni $_FILES).
     */
    public function handle(Request $request, Closure $next)
    {
        if (!in_array(strtoupper($request->getRealMethod()), ['PUT', 'PATCH', 'DELETE'], true)) {
            return $next($request);
        }

        $contentType = (string) $request->headers->get('CONTENT_TYPE', '');
        if (!str_starts_with(strtolower($contentType), 'multipart/form-data')) {
            return $next($request);
        }

        if ($request->request->count() > 0 || $request->files->count() > 0) {
            return $next($request);
        }

        if (!preg_match('/boundary=(?:"([^"]+)"|([^\s;]+))/i', $contentType, $matches)) {
            return $next($request);
        }

        $boundary = $matches[1] !== '' ? $matches[1] : $matches[2];
        $rawBody = $request->getContent();

        if ($rawBody === '' || $rawBody === false) {
            return $next($request);
        }

        $parts = explode('--' . $boundary, $rawBody);
        array_shift($parts); // Descartar preámbulo antes del primer boundary

        $inputs = [];
        $files = [];

        foreach ($parts as $part) {
            if ($part === '' || str_starts_with($part, '--')) {
                break;
            }

            // Quitar salto de línea inicial después del boundary
            if (str_starts_with($part, "\r\n")) {
                $part = substr($part, 2);
            } elseif (str_starts_with($part, "\n")) {
                $part = substr($part, 1);
            }

            // Quitar salto de línea final previo al siguiente boundary (binario-seguro)
            if (str_ends_with($part, "\r\n")) {
                $part = substr($part, 0, -2);
            } elseif (str_ends_with($part, "\n")) {
                $part = substr($part, 0, -1);
            }

            $separatorPos = strpos($part, "\r\n\r\n");
            $separatorLen = 4;
            if ($separatorPos === false) {
                $separatorPos = strpos($part, "\n\n");
                $separatorLen = 2;
            }

            if ($separatorPos === false) {
                continue;
            }

            $rawHeaders = substr($part, 0, $separatorPos);
            $body = substr($part, $separatorPos + $separatorLen);

            $headers = [];
            foreach (preg_split("/\r\n|\n/", $rawHeaders) as $headerLine) {
                if (str_contains($headerLine, ':')) {
                    [$hName, $hVal] = explode(':', $headerLine, 2);
                    $headers[strtolower(trim($hName))] = trim($hVal);
                }
            }

            $disposition = $headers['content-disposition'] ?? '';
            if (!preg_match('/name="([^"]+)"/i', $disposition, $nameMatch)) {
                continue;
            }

            $fieldName = $nameMatch[1];
            $hasFilename = preg_match('/filename="([^"]*)"/i', $disposition, $fileMatch) === 1;

            if ($hasFilename) {
                $filename = $fileMatch[1];
                if ($filename === '') {
                    continue;
                }

                $tmpPath = tempnam(sys_get_temp_dir(), 'laravel_put_');
                if ($tmpPath === false) {
                    continue;
                }

                file_put_contents($tmpPath, $body);
                register_shutdown_function(static function () use ($tmpPath): void {
                    if (is_file($tmpPath)) {
                        @unlink($tmpPath);
                    }
                });

                $mimeType = $headers['content-type'] ?? null;
                $uploadedFile = new UploadedFile($tmpPath, $filename, $mimeType, \UPLOAD_ERR_OK, true);
                $this->assignNestedValue($files, $fieldName, $uploadedFile);
            } else {
                $this->assignNestedValue($inputs, $fieldName, $body);
            }
        }

        if ($inputs !== []) {
            $request->request->add($inputs);
        }

        if ($files !== []) {
            $request->files->add($files);
        }

        return $next($request);
    }

    /**
     * Soporta nombres simples ("nombre") y de arreglo ("items[0][id]").
     */
    private function assignNestedValue(array &$target, string $fieldName, mixed $value): void
    {
        if (!str_contains($fieldName, '[')) {
            $target[$fieldName] = $value;
            return;
        }

        $keys = preg_split('/\[|\]/', $fieldName, -1, PREG_SPLIT_NO_EMPTY);
        if (!$keys) {
            $target[$fieldName] = $value;
            return;
        }

        $current = &$target;
        $lastIndex = count($keys) - 1;

        foreach ($keys as $index => $key) {
            if ($index === $lastIndex) {
                if ($key === '') {
                    $current[] = $value;
                } else {
                    $current[$key] = $value;
                }
            } else {
                if (!isset($current[$key]) || !is_array($current[$key])) {
                    $current[$key] = [];
                }
                $current = &$current[$key];
            }
        }
    }
}
