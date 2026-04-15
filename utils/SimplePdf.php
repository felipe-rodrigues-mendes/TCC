<?php

/**
 * Gerador simples de PDF textual sem dependências externas.
 */
class SimplePdf {
    private array $lines = [];
    private array $rectangles = [];

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
        $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >>";
        $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>";
        $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>";
        $objects[] = "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream";

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
}
