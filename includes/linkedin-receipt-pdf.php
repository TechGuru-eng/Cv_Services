<?php
require_once __DIR__ . '/pdf/vendor/autoload.php';

function linkedin_pdf_text(string $value): string
{
    return iconv('UTF-8', 'Windows-1252//TRANSLIT', preg_replace('/[\x00-\x08\x0b\x0c\x0e-\x1f]/', '', $value));
}

class LinkedInReceiptPdf extends FPDF
{
    public bool $paymentConfirmed = false;
    private function watermark(): void
    {
        $this->SetFont('Helvetica', 'B', $this->paymentConfirmed ? 64 : 47);
        $this->SetTextColor($this->paymentConfirmed ? 228 : 241, $this->paymentConfirmed ? 242 : 232, $this->paymentConfirmed ? 237 : 226);
        $angle = 32 * M_PI / 180;
        $c = cos($angle); $s = sin($angle);
        $x = 105 * $this->k; $y = (297 - 155) * $this->k;
        $this->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm', $c, $s, -$s, $c, $x, $y, -$x, -$y));
        $this->SetXY(20, 148);
        $this->Cell(170, 20, linkedin_pdf_text($this->paymentConfirmed ? 'VALIDÉ' : 'NON VALIDÉ'), 0, 0, 'C');
        $this->_out('Q');
    }

    public function Header(): void
    {
        $this->watermark();
        $this->SetFillColor(10, 102, 194);
        $this->Rect(0, 0, 210, 3, 'F');
        $this->SetXY(20, 17);
        $this->SetFont('Helvetica', 'B', 25);
        $this->SetTextColor(18, 41, 77);
        $this->Cell(0, 12, 'MonCv Pro');
        $this->SetXY(20, 30);
        $this->SetFont('Helvetica', '', 9);
        $this->SetTextColor(10, 102, 194);
        $this->Cell(0, 5, linkedin_pdf_text('AGENCE EN LIGNE · FILIALE DE HIREME'));
        $this->SetXY(110, 21);
        $this->SetFont('Helvetica', '', 9);
        $this->SetTextColor(83, 102, 127);
        $this->Cell(80, 6, 'moncvpro@hiremeguide.com', 0, 0, 'R');
        $this->SetXY(110, 28);
        $this->Cell(80, 6, linkedin_pdf_text('Sangmélima, Cameroun'), 0, 0, 'R');
        $this->SetDrawColor(223, 231, 241);
        $this->Line(20, 43, 190, 43);
        $this->SetY(53);
    }

    public function Footer(): void
    {
        $this->SetY(-45);
        $this->SetDrawColor(223, 231, 241);
        $this->Line(20, $this->GetY(), 190, $this->GetY());
        $this->SetFont('Helvetica', '', 8);
        $this->SetTextColor(83, 102, 127);
        $this->Ln(4);
        $this->SetFont('Helvetica', 'B', 8);
        $this->Cell(0, 6, linkedin_pdf_text('MONCV PRO · SANGMÉLIMA · FILIALE DE HIREME'), 0, 1);
        $this->SetFont('Helvetica', '', 8);
        $this->MultiCell(170, 4, linkedin_pdf_text('Agence en ligne : création de CV, optimisation de profils LinkedIn et conception de lettres de motivation.'));
        $this->Ln(2);
        $this->MultiCell(170, 4, linkedin_pdf_text('HireMe accompagne les chercheurs d’emploi en collectant et diffusant des offres, et aide les employeurs grâce à des campagnes publicitaires payantes.'));
        $this->Cell(130, 7, 'moncvpro@hiremeguide.com');
        $this->Cell(40, 7, 'Page ' . $this->PageNo(), 0, 0, 'R');
    }
}

function linkedin_receipt_pdf(array $order, array $items): string
{
    $pdf = new LinkedInReceiptPdf();
    $pdf->paymentConfirmed = $order['payment_status'] === 'CONFIRMED';
    $pdf->SetMargins(20, 20, 20);
    $pdf->SetAutoPageBreak(true, 53);
    $pdf->SetTitle(linkedin_pdf_text('Reçu de commande ' . $order['reference']));
    $pdf->SetAuthor('MonCv Pro - HireMe');
    $pdf->AddPage();
    $pdf->SetTextColor(18, 41, 77);
    $pdf->SetFont('Helvetica', 'B', 22);
    $pdf->Cell(0, 12, linkedin_pdf_text('Votre reçu de commande'), 0, 1);
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->SetTextColor(83, 102, 127);
    $pdf->Cell(0, 7, linkedin_pdf_text('Référence : ' . $order['reference']), 0, 1);
    $pdf->Cell(0, 7, linkedin_pdf_text('Commande enregistrée le ' . date('d/m/Y à H:i', strtotime($order['created_at']))), 0, 1);
    $pdf->Ln(9);
    $confirmed = $order['payment_status'] === 'CONFIRMED';
    $pdf->SetFillColor(237, 245, 255);
    $pdf->SetTextColor(10, 102, 194);
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->Cell(0, 12, linkedin_pdf_text($confirmed ? '   VALIDÉ · Paiement confirmé par MonCv Pro' : '   NON VALIDÉ · Paiement non confirmé'), 0, 1, 'L', true);
    $pdf->Ln(10);
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->SetTextColor(83, 102, 127);
    $pdf->Cell(125, 10, linkedin_pdf_text('PRESTATION / ARTICLE'));
    $pdf->Cell(45, 10, 'MONTANT', 0, 1, 'R');
    $pdf->SetDrawColor(223, 231, 241);
    foreach ($items as $item) {
        $pdf->SetFont('Helvetica', '', 11);
        $pdf->SetTextColor(18, 41, 77);
        $name = linkedin_pdf_text(preg_replace('/[\r\n]+/', ' ', $item['item_name_snapshot']));
        // Measure using the actual font so long item names wrap without colliding with amounts.
        $words = preg_split('/\s+/', $name);
        $line = '';
        $rows = [];
        foreach ($words as $word) {
            if ($line !== '' && $pdf->GetStringWidth($line . ' ' . $word) > 118) { $rows[] = $line; $line = ''; }
            $line .= ($line === '' ? '' : ' ') . $word;
        }
        $rows[] = $line;
        $height = count($rows) * 7 + 8;
        if ($pdf->GetY() + $height > 230) {
            $pdf->AddPage();
            $pdf->SetFont('Helvetica', '', 11);
            $pdf->SetTextColor(18, 41, 77);
        }
        $top = $pdf->GetY();
        foreach ($rows as $row) $pdf->Cell(125, 7, $row, 0, 1);
        $pdf->SetXY(145, $top);
        $pdf->Cell(45, 7, number_format((float) $item['final_price_snapshot'], 0, ',', ' ') . ' XAF', 0, 1, 'R');
        $pdf->SetY($top + $height);
        $pdf->Line(20, $pdf->GetY() - 4, 190, $pdf->GetY() - 4);
    }
    if ($pdf->GetY() > 153) $pdf->AddPage();
    $pdf->Ln(5);
    if ((float) ($order['discount_amount'] ?? 0) > 0) {
        $pdf->SetTextColor(83, 102, 127);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->Cell(125, 9, linkedin_pdf_text('Remise sur la commande'));
        $pdf->Cell(45, 9, '- ' . number_format((float) $order['discount_amount'], 0, ',', ' ') . ' XAF', 0, 1, 'R');
    }
    $pdf->SetFillColor(18, 41, 77);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Helvetica', 'B', 13);
    $pdf->Cell(115, 16, '   TOTAL DE LA COMMANDE', 0, 0, 'L', true);
    $pdf->Cell(55, 16, number_format((float) $order['total_amount'], 0, ',', ' ') . ' XAF   ', 0, 1, 'R', true);
    $pdf->Ln(14);
    $pdf->SetTextColor(18, 41, 77);
    $pdf->SetFont('Helvetica', 'B', 12);
    $pdf->MultiCell(170, 7, linkedin_pdf_text('Le travail ne commencera qu’après confirmation de votre paiement par notre équipe.'));
    $pdf->Ln(4);
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->SetTextColor(83, 102, 127);
    $pdf->MultiCell(170, 6, linkedin_pdf_text('Ce reçu atteste de l’enregistrement de votre commande. Il ne constitue pas une preuve de paiement. Conservez votre référence pour toute demande de suivi.'));
    $pdf->Ln(10);
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->Cell(0, 7, linkedin_pdf_text('Merci pour votre confiance. L’équipe MonCv Pro.'));
    return $pdf->Output('S');
}
