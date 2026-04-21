<?php

/**
 * Gerador simples de PDF textual sem dependências externas.
 */
class SimplePdf {
    private array $lines = [];
    private array $rectangles = [];
    private array $strokedRectangles = [];
    private array $images = [];

    public function addLine(string $text, int $x, int $y, int $size = 12, bool $bold = false): void {
        $this->lines[] = [
            'text' => $this->sanitizeText($text),
            'x' => $x,
            'y' => $y,
            'size' => $size,
            'font' => $bold ? 'F2' : 'F1',
        ];
    }

    public function addFilledRect(int $x, int $y, int $width, int $height): void {
        $this->rectangles[] = [
            'x' => $x,
            'y' => $y,
            'width' => $width,
            'height' => $height,
        ];
    }

    public function addStrokedRect(
        int $x,
        int $y,
        int $width,
        int $height,
        float $lineWidth = 1.0,
        int $red = 0,
        int $green = 0,
        int $blue = 0
    ): void {
        if ($width <= 0 || $height <= 0) {
            return;
        }

        $this->strokedRectangles[] = [
            'x' => $x,
            'y' => $y,
            'width' => $width,
            'height' => $height,
            'line_width' => $lineWidth > 0 ? $lineWidth : 1.0,
            'red' => $this->sanitizeColorChannel($red),
            'green' => $this->sanitizeColorChannel($green),
            'blue' => $this->sanitizeColorChannel($blue),
        ];
    }

    public function addImage(string $imagePath, int $x, int $y, int $width, int $height): bool {
        if (!is_file($imagePath) || !is_readable($imagePath)) {
            return false;
        }

        $imageInfo = @getimagesize($imagePath);
        if ($imageInfo === false) {
            return false;
        }

        $mime = strtolower($imageInfo['mime'] ?? '');
        $imagePayload = null;

        if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
            $jpegData = @file_get_contents($imagePath);
            if (is_string($jpegData) && $jpegData !== '') {
                $imagePayload = [
                    'data' => $jpegData,
                    'filter' => 'DCTDecode',
                    'color_space' => 'DeviceRGB',
                    'bits_per_component' => 8,
                ];
            }
        } elseif ($mime === 'image/png') {
            $imagePayload = $this->createPngImagePayload(
                $imagePath,
                (int)($imageInfo[0] ?? 0),
                (int)($imageInfo[1] ?? 0)
            );

            if ($imagePayload === null) {
                $jpegData = $this->createJpegImagePayloadFromPng(
                    $imagePath,
                    (int)($imageInfo[0] ?? 0),
                    (int)($imageInfo[1] ?? 0)
                );

                if (is_string($jpegData) && $jpegData !== '') {
                    $imagePayload = [
                        'data' => $jpegData,
                        'filter' => 'DCTDecode',
                        'color_space' => 'DeviceRGB',
                        'bits_per_component' => 8,
                    ];
                }
            }
        }

        if (!is_array($imagePayload) || !isset($imagePayload['data']) || !is_string($imagePayload['data']) || $imagePayload['data'] === '') {
            return false;
        }

        $this->images[] = [
            'data' => $imagePayload['data'],
            'filter' => (string)($imagePayload['filter'] ?? 'DCTDecode'),
            'color_space' => (string)($imagePayload['color_space'] ?? 'DeviceRGB'),
            'bits_per_component' => (int)($imagePayload['bits_per_component'] ?? 8),
            'alpha_data' => isset($imagePayload['alpha_data']) && is_string($imagePayload['alpha_data'])
                ? $imagePayload['alpha_data']
                : null,
            'pixel_width' => (int)($imageInfo[0] ?? 0),
            'pixel_height' => (int)($imageInfo[1] ?? 0),
            'x' => $x,
            'y' => $y,
            'width' => $width,
            'height' => $height,
        ];

        return true;
    }

    public function addWrappedText(string $text, int $x, int $y, int $maxChars = 80, int $size = 12, bool $bold = false, int $lineHeight = 16): int {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');
        if ($text === '') {
            return $y;
        }

        $lines = wordwrap($text, $maxChars, "\n", true);
        foreach (explode("\n", $lines) as $line) {
            $this->addLine($line, $x, $y, $size, $bold);
            $y -= $lineHeight;
        }

        return $y;
    }

    public function addCenteredLine(string $text, int $centerX, int $y, int $size = 12, bool $bold = false): void {
        $text = trim($text);
        $approxWidth = (int) round(strlen($this->sanitizeText($text)) * ($size * 0.52));
        $x = max(20, (int) round($centerX - ($approxWidth / 2)));
        $this->addLine($text, $x, $y, $size, $bold);
    }

    public function output(string $filename = 'documento.pdf'): void {
        // Evita bytes residuais (whitespace/BOM/warnings) antes do binário do PDF.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $content = '';

        foreach ($this->rectangles as $rectangle) {
            $content .= "0 0 0 rg\n";
            $content .= sprintf("%d %d %d %d re f\n", $rectangle['x'], $rectangle['y'], $rectangle['width'], $rectangle['height']);
        }

        foreach ($this->strokedRectangles as $rectangle) {
            $content .= sprintf(
                "%.3F %.3F %.3F RG\n",
                $rectangle['red'] / 255,
                $rectangle['green'] / 255,
                $rectangle['blue'] / 255
            );
            $content .= sprintf("%.2F w\n", $rectangle['line_width']);
            $content .= sprintf(
                "%d %d %d %d re S\n",
                $rectangle['x'],
                $rectangle['y'],
                $rectangle['width'],
                $rectangle['height']
            );
        }

        $imageObjects = [];
        $imageResourceEntries = [];
        $nextImageObjectNumber = 7;
        foreach ($this->images as $index => $image) {
            $resourceName = 'Im' . ($index + 1);
            $objectNumber = $nextImageObjectNumber;
            $nextImageObjectNumber++;
            $alphaObjectNumber = null;
            if (isset($image['alpha_data']) && is_string($image['alpha_data']) && $image['alpha_data'] !== '') {
                $alphaObjectNumber = $nextImageObjectNumber;
                $nextImageObjectNumber++;
            }
            $imageResourceEntries[] = sprintf('/%s %d 0 R', $resourceName, $objectNumber);

            $content .= "q\n";
            $content .= sprintf(
                "%d 0 0 %d %d %d cm\n",
                $image['width'],
                $image['height'],
                $image['x'],
                $image['y']
            );
            $content .= sprintf("/%s Do\n", $resourceName);
            $content .= "Q\n";

            $imageObjects[] = $this->buildImageObjectStream($image, $alphaObjectNumber);
            if ($alphaObjectNumber !== null) {
                $imageObjects[] = $this->buildAlphaMaskObjectStream($image);
            }
        }

        foreach ($this->lines as $line) {
            $content .= "BT\n";
            $content .= sprintf("/%s %d Tf\n", $line['font'], $line['size']);
            $content .= sprintf("1 0 0 1 %d %d Tm\n", $line['x'], $line['y']);
            $content .= sprintf("(%s) Tj\n", $this->escapePdfText($line['text']));
            $content .= "ET\n";
        }

        $objects = [];
        $objects[] = "<< /Type /Catalog /Pages 2 0 R >>";
        $objects[] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
        $resources = "<< /Font << /F1 4 0 R /F2 5 0 R >>";
        if (!empty($imageResourceEntries)) {
            $resources .= " /XObject << " . implode(' ', $imageResourceEntries) . " >>";
        }
        $resources .= " >>";

        $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources " . $resources . " /Contents 6 0 R >>";
        $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>";
        $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>";
        $objects[] = "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream";
        foreach ($imageObjects as $imageObject) {
            $objects[] = $imageObject;
        }

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1) . " 0 obj\n" . $object . "\nendobj\n";
        }

        $xrefPosition = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefPosition . "\n%%EOF";

        if (headers_sent()) {
            throw new RuntimeException('Não foi possível enviar o PDF porque os headers já foram enviados.');
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
    }

    private function escapePdfText(string $text): string {
        $text = str_replace(["\r", "\n"], ' ', $text);

        return str_replace(
            ['\\', '(', ')'],
            ['\\\\', '\\(', '\\)'],
            $text
        );
    }

    private function sanitizeText(string $text): string {
        $normalized = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
        if ($normalized === false) {
            $normalized = $text;
        }

        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $normalized) ?? '';
    }

    private function sanitizeColorChannel(int $value): int {
        if ($value < 0) {
            return 0;
        }

        if ($value > 255) {
            return 255;
        }

        return $value;
    }

    private function buildImageObjectStream(array $image, ?int $alphaObjectNumber = null): string {
        $dictionary = "<< /Type /XObject /Subtype /Image /Width " . (int)$image['pixel_width'] .
            " /Height " . (int)$image['pixel_height'] .
            " /ColorSpace /" . (string)$image['color_space'] .
            " /BitsPerComponent " . (int)$image['bits_per_component'] .
            " /Filter /" . (string)$image['filter'];

        if ($alphaObjectNumber !== null) {
            $dictionary .= " /SMask " . $alphaObjectNumber . " 0 R";
        }

        $dictionary .= " /Length " . strlen((string)$image['data']) . " >>";

        return $dictionary . "\nstream\n" . (string)$image['data'] . "\nendstream";
    }

    private function buildAlphaMaskObjectStream(array $image): string {
        $alphaData = (string)($image['alpha_data'] ?? '');
        $dictionary = "<< /Type /XObject /Subtype /Image /Width " . (int)$image['pixel_width'] .
            " /Height " . (int)$image['pixel_height'] .
            " /ColorSpace /DeviceGray /BitsPerComponent 8 /Filter /FlateDecode /Length " .
            strlen($alphaData) . " >>";

        return $dictionary . "\nstream\n" . $alphaData . "\nendstream";
    }

    private function createPngImagePayload(string $imagePath, int $imageWidth, int $imageHeight): ?array {
        if (
            $imageWidth <= 0 ||
            $imageHeight <= 0 ||
            !function_exists('gzuncompress') ||
            !function_exists('gzcompress')
        ) {
            return null;
        }

        $binary = @file_get_contents($imagePath);
        if (!is_string($binary) || $binary === '') {
            return null;
        }

        if (substr($binary, 0, 8) !== "\x89PNG\x0D\x0A\x1A\x0A") {
            return null;
        }

        $width = 0;
        $height = 0;
        $bitDepth = 0;
        $colorType = 0;
        $compressionMethod = 0;
        $filterMethod = 0;
        $interlaceMethod = 0;
        $transparentColorKey = null;
        $idatData = '';
        $offset = 8;
        $binaryLength = strlen($binary);

        while ($offset + 8 <= $binaryLength) {
            $lengthData = substr($binary, $offset, 4);
            if (strlen($lengthData) !== 4) {
                return null;
            }

            $chunkLength = unpack('Nlen', $lengthData);
            if (!is_array($chunkLength) || !isset($chunkLength['len'])) {
                return null;
            }

            $length = (int)$chunkLength['len'];
            $offset += 4;
            $chunkType = substr($binary, $offset, 4);
            $offset += 4;

            if ($offset + $length + 4 > $binaryLength) {
                return null;
            }

            $chunkData = substr($binary, $offset, $length);
            $offset += $length;

            // CRC (nao validado para manter parser enxuto).
            $offset += 4;

            if ($chunkType === 'IHDR') {
                if (strlen($chunkData) !== 13) {
                    return null;
                }

                $ihdr = unpack('Nwidth/Nheight/Cbit_depth/Ccolor_type/Ccompression/Cfilter/Cinterlace', $chunkData);
                if (!is_array($ihdr)) {
                    return null;
                }

                $width = (int)($ihdr['width'] ?? 0);
                $height = (int)($ihdr['height'] ?? 0);
                $bitDepth = (int)($ihdr['bit_depth'] ?? 0);
                $colorType = (int)($ihdr['color_type'] ?? 0);
                $compressionMethod = (int)($ihdr['compression'] ?? 0);
                $filterMethod = (int)($ihdr['filter'] ?? 0);
                $interlaceMethod = (int)($ihdr['interlace'] ?? 0);
            } elseif ($chunkType === 'IDAT') {
                $idatData .= $chunkData;
            } elseif ($chunkType === 'tRNS') {
                if ($colorType === 2 && strlen($chunkData) >= 6) {
                    $trns = unpack('nred/ngreen/nblue', substr($chunkData, 0, 6));
                    if (is_array($trns)) {
                        $transparentColorKey = [
                            'red' => $this->normalizeTrnsSample((int)($trns['red'] ?? 0), $bitDepth),
                            'green' => $this->normalizeTrnsSample((int)($trns['green'] ?? 0), $bitDepth),
                            'blue' => $this->normalizeTrnsSample((int)($trns['blue'] ?? 0), $bitDepth),
                        ];
                    }
                }
            } elseif ($chunkType === 'IEND') {
                break;
            }
        }

        if (
            $width <= 0 ||
            $height <= 0 ||
            $idatData === '' ||
            $bitDepth !== 8 ||
            !in_array($colorType, [2, 6], true) ||
            $compressionMethod !== 0 ||
            $filterMethod !== 0 ||
            $interlaceMethod !== 0
        ) {
            return null;
        }

        $inflated = @gzuncompress($idatData);
        if (!is_string($inflated) || $inflated === '') {
            return null;
        }

        $bytesPerPixel = $colorType === 6 ? 4 : 3;
        $stride = $width * $bytesPerPixel;
        if ($stride <= 0) {
            return null;
        }

        $expectedLength = ($stride + 1) * $height;
        if (strlen($inflated) < $expectedLength) {
            return null;
        }

        $cursor = 0;
        $previousLine = str_repeat("\x00", $stride);
        $rgbData = '';
        $alphaData = '';
        $hasTransparency = false;

        for ($y = 0; $y < $height; $y++) {
            $filterType = ord($inflated[$cursor]);
            $cursor++;

            $scanline = substr($inflated, $cursor, $stride);
            if (strlen($scanline) !== $stride) {
                return null;
            }
            $cursor += $stride;

            $decodedLine = $this->decodePngScanline($scanline, $filterType, $previousLine, $bytesPerPixel);
            if ($decodedLine === null) {
                return null;
            }

            if ($colorType === 6) {
                $rgbLine = '';
                $alphaLine = '';
                for ($i = 0; $i < $stride; $i += 4) {
                    $rgbLine .= $decodedLine[$i] . $decodedLine[$i + 1] . $decodedLine[$i + 2];
                    $alphaByte = $decodedLine[$i + 3];
                    $alphaLine .= $alphaByte;

                    if (!$hasTransparency && $alphaByte !== "\xFF") {
                        $hasTransparency = true;
                    }
                }

                $rgbData .= $rgbLine;
                $alphaData .= $alphaLine;
            } else {
                if (is_array($transparentColorKey)) {
                    $alphaLine = '';
                    for ($i = 0; $i < $stride; $i += 3) {
                        $red = ord($decodedLine[$i]);
                        $green = ord($decodedLine[$i + 1]);
                        $blue = ord($decodedLine[$i + 2]);

                        if (
                            $red === (int)$transparentColorKey['red'] &&
                            $green === (int)$transparentColorKey['green'] &&
                            $blue === (int)$transparentColorKey['blue']
                        ) {
                            $alphaLine .= "\x00";
                            $hasTransparency = true;
                        } else {
                            $alphaLine .= "\xFF";
                        }
                    }

                    $alphaData .= $alphaLine;
                }

                $rgbData .= $decodedLine;
            }

            $previousLine = $decodedLine;
        }

        $compressedRgb = gzcompress($rgbData, 9);
        if (!is_string($compressedRgb) || $compressedRgb === '') {
            return null;
        }

        $payload = [
            'data' => $compressedRgb,
            'filter' => 'FlateDecode',
            'color_space' => 'DeviceRGB',
            'bits_per_component' => 8,
        ];

        if (($colorType === 6 || ($colorType === 2 && is_array($transparentColorKey))) && $hasTransparency) {
            $compressedAlpha = gzcompress($alphaData, 9);
            if (is_string($compressedAlpha) && $compressedAlpha !== '') {
                $payload['alpha_data'] = $compressedAlpha;
            }
        }

        return $payload;
    }

    private function normalizeTrnsSample(int $sample, int $bitDepth): int {
        if ($bitDepth >= 8) {
            if ($sample <= 255) {
                return $sample;
            }

            return ($sample >> ($bitDepth - 8)) & 0xFF;
        }

        return $sample;
    }

    private function decodePngScanline(string $scanline, int $filterType, string $previousLine, int $bytesPerPixel): ?string {
        $length = strlen($scanline);
        if ($length !== strlen($previousLine)) {
            return null;
        }

        if ($filterType < 0 || $filterType > 4) {
            return null;
        }

        $decoded = str_repeat("\x00", $length);

        for ($i = 0; $i < $length; $i++) {
            $raw = ord($scanline[$i]);
            $left = $i >= $bytesPerPixel ? ord($decoded[$i - $bytesPerPixel]) : 0;
            $up = ord($previousLine[$i]);
            $upLeft = $i >= $bytesPerPixel ? ord($previousLine[$i - $bytesPerPixel]) : 0;

            if ($filterType === 0) {
                $value = $raw;
            } elseif ($filterType === 1) {
                $value = ($raw + $left) & 0xFF;
            } elseif ($filterType === 2) {
                $value = ($raw + $up) & 0xFF;
            } elseif ($filterType === 3) {
                $value = ($raw + intdiv($left + $up, 2)) & 0xFF;
            } else {
                $value = ($raw + $this->paethPredictor($left, $up, $upLeft)) & 0xFF;
            }

            $decoded[$i] = chr($value);
        }

        return $decoded;
    }

    private function paethPredictor(int $left, int $up, int $upLeft): int {
        $p = $left + $up - $upLeft;
        $distanceLeft = abs($p - $left);
        $distanceUp = abs($p - $up);
        $distanceUpLeft = abs($p - $upLeft);

        if ($distanceLeft <= $distanceUp && $distanceLeft <= $distanceUpLeft) {
            return $left;
        }

        if ($distanceUp <= $distanceUpLeft) {
            return $up;
        }

        return $upLeft;
    }

    private function createJpegImagePayloadFromPng(string $imagePath, int $imageWidth, int $imageHeight): ?string {
        if (
            !function_exists('imagecreatefrompng') ||
            !function_exists('imagecreatetruecolor') ||
            !function_exists('imagecopy') ||
            !function_exists('imagejpeg') ||
            !function_exists('imagedestroy')
        ) {
            return null;
        }

        $source = @imagecreatefrompng($imagePath);
        if ($source === false) {
            return null;
        }

        $canvas = imagecreatetruecolor($imageWidth, $imageHeight);
        if ($canvas === false) {
            imagedestroy($source);
            return null;
        }

        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $imageWidth, $imageHeight, $white);
        imagecopy($canvas, $source, 0, 0, 0, 0, $imageWidth, $imageHeight);

        ob_start();
        imagejpeg($canvas, null, 90);
        $jpegData = ob_get_clean();

        imagedestroy($canvas);
        imagedestroy($source);

        if (!is_string($jpegData) || $jpegData === '') {
            return null;
        }

        return $jpegData;
    }
}
