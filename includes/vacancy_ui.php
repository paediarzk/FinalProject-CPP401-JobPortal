<?php
declare(strict_types=1);

/**
 * HTML pieces shared by the home page and the vacancy list (Milestone 3-B).
 * Everything printed from the database goes through e().
 */
require_once __DIR__ . '/vacancy.php';

/** Search form (keyword + position type + location). Sends GET to vacancies.php; $filters are the current values. */
function vacancy_search_form_html(array $filters, bool $showClear = false): string
{
    $options = '<option value="">All position types</option>';
    foreach (POSITION_TYPES as $value => $label) {
        $options .= '<option value="' . e($value) . '"' . (($filters['type'] ?? '') === $value ? ' selected' : '') . '>' . e($label) . '</option>';
    }
    $max = (int)PUBLIC_FILTER_MAXLEN;

    $html  = '<form method="get" action="' . e(url('vacancies.php')) . '" class="row g-2 align-items-end" role="search">';
    $html .= '<div class="col-md-4"><label class="form-label" for="q">Keyword</label>'
           . '<input class="form-control" id="q" name="q" maxlength="' . $max . '" value="' . e($filters['q'] ?? '') . '" placeholder="Position, company or skill"></div>';
    $html .= '<div class="col-sm-6 col-md-3"><label class="form-label" for="type">Position type</label>'
           . '<select class="form-select" id="type" name="type">' . $options . '</select></div>';
    $html .= '<div class="col-sm-6 col-md-3"><label class="form-label" for="location">Location</label>'
           . '<input class="form-control" id="location" name="location" maxlength="' . $max . '" value="' . e($filters['location'] ?? '') . '" placeholder="e.g. Jakarta"></div>';
    $html .= '<div class="col-12 col-md-2 d-flex gap-2"><button class="btn btn-brand flex-grow-1" type="submit">Search</button>';
    if ($showClear) {
        $html .= '<a class="btn btn-outline-secondary d-flex align-items-center" href="' . e(url('vacancies.php')) . '">Clear</a>';
    }
    return $html . '</div></form>';
}

/** One vacancy as a card (a row from search_public_vacancies()). */
function vacancy_card_html(array $v): string
{
    $detail = url('vacancy.php?id=' . (int)$v['id']);
    $allowance = $v['allowance'] === null ? 'Allowance not stated' : 'IDR ' . number_format((float)$v['allowance'], 0, ',', '.') . ' / month';

    $html  = '<article class="app-card vacancy-card">';
    $html .= '<div class="d-flex flex-wrap justify-content-between align-items-start gap-2">';
    $html .= '<div><h2 class="h5 mb-1"><a class="text-decoration-none" href="' . e($detail) . '">' . e($v['title']) . '</a></h2>';
    $html .= '<div class="text-muted">' . e($v['company_name']) . '</div></div>';
    $html .= '<span class="badge text-bg-light border">' . e(POSITION_TYPES[$v['position_type']] ?? $v['position_type']) . '</span></div>';
    $html .= '<div class="small text-muted mt-2">' . e($v['location']) . ' &middot; ' . e($allowance)
           . ' &middot; Apply before ' . e($v['deadline']) . '</div>';
    if (!empty($v['skill_names'])) {
        $html .= '<div class="mt-2">';
        foreach (explode(', ', $v['skill_names']) as $skill) {
            $html .= '<span class="badge rounded-pill text-bg-light border me-1">' . e($skill) . '</span>';
        }
        $html .= '</div>';
    }
    $html .= '<div class="mt-3"><a class="btn btn-outline-secondary btn-sm" href="' . e($detail) . '">View details</a></div>';
    return $html . '</article>';
}
