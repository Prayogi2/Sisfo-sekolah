<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Halaman "Log Aktivitas": riwayat siapa melakukan apa di sistem.
 * Hanya baca — baris log tidak bisa diubah atau dihapus dari UI.
 */
class ActivityLogController extends Controller
{
    private const PER_PAGE = 30;

    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        return view('admin.log-aktivitas', [
            'logs' => $this->query($filters),
            'filters' => $filters,
            'actors' => User::query()->whereIn('id', ActivityLog::query()->whereNotNull('user_id')->distinct()->pluck('user_id'))->orderBy('name')->get(['id', 'name', 'role']),
            'roles' => ['admin' => 'Admin', 'guru' => 'Guru', 'siswa' => 'Siswa'],
        ]);
    }

    /**
     * @return array{search: ?string, role: ?string, user_id: ?int, date_from: ?string, date_to: ?string, only_rejected: bool}
     */
    private function filters(Request $request): array
    {
        return [
            'search' => $request->query('search') ? (string) $request->query('search') : null,
            'role' => in_array($request->query('role'), ['admin', 'guru', 'siswa'], true) ? (string) $request->query('role') : null,
            'user_id' => $request->integer('user_id') ?: null,
            'date_from' => $this->date($request->query('date_from')),
            'date_to' => $this->date($request->query('date_to')),
            'only_rejected' => $request->boolean('only_rejected'),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, ActivityLog>
     */
    private function query(array $filters): LengthAwarePaginator
    {
        return ActivityLog::query()
            ->with(['user', 'impersonator'])
            ->search($filters['search'])
            ->when($filters['role'], fn ($query, $role) => $query->where('user_role', $role))
            ->when($filters['user_id'], fn ($query, $userId) => $query->where('user_id', $userId))
            ->when($filters['date_from'], fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'], fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->when($filters['only_rejected'], fn ($query) => $query->where(fn ($query) => $query->whereIn('status_code', [401, 403, 419, 422])->orWhere('status_code', '>=', 500)))
            ->newestFirst()
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * Tanggal yang tidak valid diabaikan, bukan membuat halaman error.
     */
    private function date(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
