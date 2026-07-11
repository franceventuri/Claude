<?php

function h($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function money(float $value): string
{
    return number_format($value, 2, ',', '.') . ' €';
}

function num($value, int $decimals = 2): string
{
    return number_format((float) $value, $decimals, ',', '.');
}

function dateIt(?string $isoDate): string
{
    if (!$isoDate) return '';
    $ts = strtotime($isoDate);
    if (!$ts) return '';
    return date('d/m/Y', $ts);
}

function todayIso(): string
{
    return date('Y-m-d');
}

function statusLabel(string $status): string
{
    $map = [
        'bozza' => 'Bozza',
        'inviato' => 'Inviato',
        'accettato' => 'Accettato',
        'rifiutato' => 'Rifiutato',
        'scaduto' => 'Scaduto',
    ];
    return $map[$status] ?? ucfirst($status);
}

function statusClass(string $status): string
{
    $map = [
        'bozza' => 'badge-grey',
        'inviato' => 'badge-blue',
        'accettato' => 'badge-green',
        'rifiutato' => 'badge-red',
        'scaduto' => 'badge-orange',
    ];
    return $map[$status] ?? 'badge-grey';
}

function isQuoteExpired(array $quote): bool
{
    if (in_array($quote['status'], ['accettato', 'rifiutato'], true)) {
        return false;
    }
    $issue = strtotime($quote['issue_date']);
    $expiry = strtotime('+' . (int) $quote['validity_days'] . ' days', $issue);
    return $expiry < strtotime('today');
}

/** Calcola i totali di un preventivo dato l'elenco delle righe. */
function calcQuoteTotals(array $items, string $discountType, float $discountValue, float $vatRate): array
{
    $subtotal = 0.0;
    $totalCost = 0.0;
    foreach ($items as $it) {
        if (!empty($it['optional'])) {
            continue; // le voci opzionali non entrano nel totale
        }
        $lineGross = (float) $it['quantity'] * (float) $it['unit_price'];
        $lineNet = $lineGross * (1 - ((float) ($it['discount_percent'] ?? 0) / 100));
        $subtotal += $lineNet;
        $totalCost += (float) $it['quantity'] * (float) ($it['unit_cost'] ?? 0);
    }

    $discountAmount = $discountType === 'percent'
        ? $subtotal * ($discountValue / 100)
        : $discountValue;
    $discountAmount = min($discountAmount, $subtotal);

    $taxable = $subtotal - $discountAmount;
    $vatAmount = $taxable * ($vatRate / 100);
    $total = $taxable + $vatAmount;
    $margin = $taxable - $totalCost;
    $marginPct = $taxable > 0 ? ($margin / $taxable) * 100 : 0;

    return [
        'subtotal' => round($subtotal, 2),
        'discount_amount' => round($discountAmount, 2),
        'taxable' => round($taxable, 2),
        'vat_amount' => round($vatAmount, 2),
        'total' => round($total, 2),
        'total_cost' => round($totalCost, 2),
        'margin' => round($margin, 2),
        'margin_pct' => round($marginPct, 1),
    ];
}

function nextQuoteNumber(PDO $pdo, int $companyId, string $prefix): string
{
    $year = (int) date('Y');
    $stmt = $pdo->prepare('SELECT next_quote_seq, quote_seq_year FROM companies WHERE id = ?');
    $stmt->execute([$companyId]);
    $row = $stmt->fetch();
    $seq = (int) $row['next_quote_seq'];
    if ((int) $row['quote_seq_year'] !== $year) {
        $seq = 1;
    }
    $number = sprintf('%s-%d-%03d', $prefix, $year, $seq);

    $upd = $pdo->prepare('UPDATE companies SET next_quote_seq = ?, quote_seq_year = ? WHERE id = ?');
    $upd->execute([$seq + 1, $year, $companyId]);

    return $number;
}

function generateShareToken(): string
{
    return bin2hex(random_bytes(24));
}
