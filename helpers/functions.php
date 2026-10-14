<?php
/**
 * General utility helpers
 */

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function formatNumber(float $num, int $dec = 0): string {
    return number_format($num, $dec, ',', '.');
}

function formatRupiah(float $num): string {
    return 'Rp ' . number_format($num, 0, ',', '.');
}

function formatDate(string $date, string $fmt = 'd M Y'): string {
    if (empty($date)) return '-';
    return (new DateTime($date))->format($fmt);
}

/**
 * Build pagination links preserving current GET params.
 */
function paginationLinks(int $currentPage, int $totalPages, array $params = []): string {
    if ($totalPages <= 1) return '';
    $base = '?' . http_build_query(array_merge($params, ['page' => 0]));
    $html = '<nav aria-label="Pagination"><ul class="pagination mb-0">';

    // Previous
    $prevDisabled = $currentPage <= 1 ? ' disabled' : '';
    $prevHref     = $currentPage > 1
        ? '?' . http_build_query(array_merge($params, ['page' => $currentPage - 1]))
        : '#';
    $html .= "<li class=\"page-item$prevDisabled\"><a class=\"page-link\" href=\"$prevHref\">‹</a></li>";

    // Pages
    $start = max(1, $currentPage - 2);
    $end   = min($totalPages, $currentPage + 2);
    if ($start > 1) $html .= '<li class="page-item disabled"><a class="page-link">…</a></li>';
    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $currentPage ? ' active' : '';
        $href   = '?' . http_build_query(array_merge($params, ['page' => $i]));
        $html  .= "<li class=\"page-item$active\"><a class=\"page-link\" href=\"$href\">$i</a></li>";
    }
    if ($end < $totalPages) $html .= '<li class="page-item disabled"><a class="page-link">…</a></li>';

    // Next
    $nextDisabled = $currentPage >= $totalPages ? ' disabled' : '';
    $nextHref     = $currentPage < $totalPages
        ? '?' . http_build_query(array_merge($params, ['page' => $currentPage + 1]))
        : '#';
    $html .= "<li class=\"page-item$nextDisabled\"><a class=\"page-link\" href=\"$nextHref\">›</a></li>";

    $html .= '</ul></nav>';
    return $html;
}

function sanitizeInt(mixed $val, int $default = 0): int {
    return (int) filter_var($val, FILTER_SANITIZE_NUMBER_INT) ?: $default;
}

function sanitizeString(mixed $val, int $maxLen = 500): string {
    $s = trim((string)($val ?? ''));
    return mb_substr($s, 0, $maxLen);
}

function flashSet(string $key, string $msg, string $type = 'success'): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['_flash'][$key] = ['msg' => $msg, 'type' => $type];
}

function flashGet(string $key): ?array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (isset($_SESSION['_flash'][$key])) {
        $f = $_SESSION['_flash'][$key];
        unset($_SESSION['_flash'][$key]);
        return $f;
    }
    return null;
}

function flashAlert(string $key): string {
    $f = flashGet($key);
    if (!$f) return '';
    $type = in_array($f['type'], ['success','danger','warning','info']) ? $f['type'] : 'info';
    return '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">'
        . e($f['msg'])
        . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
}

function getAllowedSort(string $col, array $allowed, string $default): string {
    return in_array($col, $allowed) ? $col : $default;
}

function getAllowedOrder(string $ord): string {
    return strtoupper($ord) === 'DESC' ? 'DESC' : 'ASC';
}

/**
 * Generate nomor referensi otomatis berdasarkan urutan transaksi mutasi
 * Contoh hasil: REF-2026-016 (untuk IN) atau TRX-2026-016 (untuk OUT)
 */
function getNextReference(PDO $db, string $type = 'IN'): string {
    $year = date('Y');
    $prefix = ($type === 'IN') ? "REF-{$year}-" : "TRX-{$year}-";

    $stmt = $db->prepare("
        SELECT reference
        FROM stock_movements
        WHERE reference LIKE ?
        ORDER BY id DESC
    ");
    $stmt->execute([$prefix . '%']);
    $refs = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $maxNum = 0;
    foreach ($refs as $ref) {
        if (preg_match('#^' . preg_quote($prefix, '#') . '(\d+)$#', $ref, $m)) {
            $num = (int)$m[1];
            if ($num > $maxNum) {
                $maxNum = $num;
            }
        }
    }

    $nextNum = $maxNum + 1;
    return $prefix . str_pad((string)$nextNum, 3, '0', STR_PAD_LEFT);
}
