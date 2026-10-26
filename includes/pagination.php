<?php
declare(strict_types=1);

/**
 * Pagination helpers, shared by the administrator lists (Milestone 3-A) and the vacancy search (3-B).
 */

/** Escape the characters that have a special meaning inside a LIKE pattern (\ % _). */
function escape_like(string $text): string
{
    return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $text);
}

/**
 * Work out which rows to show. $rawPage is whatever came from the URL (?page=...): it is never trusted,
 * anything that is not a positive whole number becomes page 1, and a page beyond the last one is clamped.
 */
function paginate(int $total, int $perPage, $rawPage): array
{
    $perPage = max(1, $perPage);
    $pages   = max(1, (int)ceil($total / $perPage));
    $page    = (is_string($rawPage) && ctype_digit($rawPage) && strlen($rawPage) <= 9) ? (int)$rawPage : 1;
    $page    = min(max(1, $page), $pages);

    return ['total' => $total, 'perPage' => $perPage, 'pages' => $pages, 'page' => $page, 'offset' => ($page - 1) * $perPage];
}

/** HTML of the page links. $query holds the other URL parameters (filters) that must be kept. */
function pagination_html(array $p, string $path, array $query): string
{
    if ($p['pages'] <= 1) {
        return '';
    }
    $link = static function (int $page, string $label, bool $disabled = false, bool $active = false) use ($path, $query): string {
        $href = url($path) . '?' . http_build_query(array_merge($query, ['page' => $page]));
        $class = 'page-item' . ($disabled ? ' disabled' : '') . ($active ? ' active' : '');
        return '<li class="' . $class . '"><a class="page-link" href="' . e($href) . '"' . ($active ? ' aria-current="page"' : '') . '>' . e($label) . '</a></li>';
    };

    $html = '<nav aria-label="Pagination"><ul class="pagination justify-content-center mb-0">';
    $html .= $link(max(1, $p['page'] - 1), 'Previous', $p['page'] === 1);
    $from = max(1, $p['page'] - 3);
    $to   = min($p['pages'], $p['page'] + 3);
    for ($i = $from; $i <= $to; $i++) {
        $html .= $link($i, (string)$i, false, $i === $p['page']);
    }
    $html .= $link(min($p['pages'], $p['page'] + 1), 'Next', $p['page'] === $p['pages']);
    return $html . '</ul></nav>';
}
