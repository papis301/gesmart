<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Models\ReportModel;

final class ReportController
{
    private const PER_PAGE = 100;

    private ReportModel $reports;

    public function __construct()
    {
        $this->reports = new ReportModel();
    }

    /** @return string[] */
    private static function strings(string $key): array
    {
        $v = $_GET[$key] ?? [];
        return is_array($v) ? array_values(array_filter(array_map(static fn($x) => trim((string) $x), $v), static fn(string $x): bool => $x !== '')) : [];
    }

    /** « 2026-10-01T08:00 » (datetime-local) ou « 2026-10-01 08:00:00 » => « Y-m-d H:i:s », sinon null. */
    private static function dateTime(string $v): ?string
    {
        try {
            return (new \DateTimeImmutable(str_replace('T', ' ', $v)))->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    /** Rapport sur les transactions (droit transaction_report). */
    public function transaction(): void
    {
        $from = (string) ($_GET['from_date'] ?? date('Y-m-01') . 'T08:00');
        $to = (string) ($_GET['to_date'] ?? date('Y-m-d') . 'T17:00');
        $submitted = isset($_GET['from_date']) || isset($_GET['to_date']);

        $filters = [
            'from'      => self::dateTime($from) ?? date('Y-m-01 08:00:00'),
            'to'        => self::dateTime($to) ?? date('Y-m-d 17:00:00'),
            'registers' => array_map('intval', array_filter(self::strings('registers'), 'ctype_digit')),
            'clients'   => array_map('intval', array_filter(self::strings('clients'), 'ctype_digit')),
            'banks'     => self::strings('banks'),
            'types'     => array_values(array_intersect(self::strings('transactions'), array_keys(ReportModel::TRANSACTION_TYPES))),
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = $submitted ? $this->reports->transactions($filters, $page, self::PER_PAGE) : null;

        View::render('reports/transactions', [
            'title'     => 'Rapports sur les transactions',
            'actions'   => '',
            'filters'   => $filters,
            'fromInput' => str_replace(' ', 'T', substr($filters['from'], 0, 16)),
            'toInput'   => str_replace(' ', 'T', substr($filters['to'], 0, 16)),
            'options'   => $this->reports->filterOptions(),
            'types'     => ReportModel::TRANSACTION_TYPES,
            'submitted' => $submitted,
            'rows'      => $result['rows'] ?? [],
            'total'     => $result['total'] ?? 0,
            'page'      => $page,
            'pages'     => $result ? max(1, (int) ceil($result['total'] / self::PER_PAGE)) : 1,
        ]);
    }
}
