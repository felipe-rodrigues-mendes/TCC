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
        $imageData = null;

        if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
            $imageData = @file_get_contents($imagePath);
        } elseif ($mime === 'image/png') {
            $imageData = $this->createJpegImagePayloadFromPng(
                $imagePath,
                (int)($imageInfo[0] ?? 0),
                (int)($imageInfo[1] ?? 0)
            );
        }

        if (!is_string($imageData) || $imageData === '') {
            return false;
        }

        $this->images[] = [
            'data' => $imageData,
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
        foreach ($this->images as $index => $image) {
            $resourceName = 'Im' . ($index + 1);
            $objectNumber = 7 + $index;
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

            $imageObjects[] = "<< /Type /XObject /Subtype /Image /Width " . $image['pixel_width'] .
                " /Height " . $image['pixel_height'] .
                " /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length " .
                strlen($image['data']) . " >>\nstream\n" . $image['data'] . "\nendstream";
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
