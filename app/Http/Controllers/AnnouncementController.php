<?php

namespace App\Http\Controllers;

use App\Enums\AnnouncementCategory;
use App\Enums\AnnouncementTarget;
use App\Enums\StudentStatus;
use App\Http\Controllers\Concerns\ResolvesCurrentStudent;
use App\Http\Requests\Announcement\StoreAnnouncementRequest;
use App\Models\Announcement;
use App\Models\AnnouncementRecipient;
use App\Models\Classroom;
use App\Models\Student;
use App\Services\CurrentStudentResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    use ResolvesCurrentStudent;

    /**
     * Form kirim notifikasi + riwayat notifikasi beserta jumlah yang sudah membaca.
     */
    public function index(): View
    {
        $announcements = Announcement::query()
            ->with(['classroom', 'creator'])
            ->withCount([
                'recipients',
                'recipients as read_count' => fn ($query) => $query->whereNotNull('read_at'),
            ])
            ->latest('published_at')
            ->paginate(15);

        $classrooms = Classroom::query()
            ->withCount(['students' => fn ($query) => $query->where('status', StudentStatus::Active)])
            ->orderBy('name')
            ->get();
        $students = Student::query()
            ->with('classroom')
            ->where('status', StudentStatus::Active)
            ->orderBy('name')
            ->get(['id', 'name', 'nis', 'classroom_id']);

        return view('admin.notifikasi', [
            'announcements' => $announcements,
            'classrooms' => $classrooms,
            'students' => $students,
            'categories' => AnnouncementCategory::cases(),
            'targets' => AnnouncementTarget::cases(),
        ]);
    }

    public function store(StoreAnnouncementRequest $request): RedirectResponse
    {
        $target = $request->enum('target', AnnouncementTarget::class);
        $studentIds = $this->recipientIdsFor($target, $request->integer('classroom_id'), $request->input('student_ids', []));

        if ($studentIds->isEmpty()) {
            return back()->withInput()->with('error', 'Tidak ada siswa penerima untuk target yang dipilih.');
        }

        $publishedAt = $request->filled('published_at')
            ? Carbon::parse($request->input('published_at'))->max(now())
            : now();

        $announcement = DB::transaction(function () use ($request, $target, $studentIds, $publishedAt) {
            $announcement = Announcement::create([
                'created_by' => $request->user()->id,
                'title' => $request->input('title'),
                'message' => $request->input('message'),
                'category' => $request->enum('category', AnnouncementCategory::class),
                'target' => $target,
                'classroom_id' => $target === AnnouncementTarget::Classroom ? $request->integer('classroom_id') : null,
                'published_at' => $publishedAt,
            ]);

            $timestamp = now();
            $studentIds->chunk(500)->each(fn (Collection $chunk) => AnnouncementRecipient::insert(
                $chunk->map(fn (int $studentId) => [
                    'announcement_id' => $announcement->id,
                    'student_id' => $studentId,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ])->all()
            ));

            return $announcement;
        });

        $message = $announcement->isScheduled()
            ? "Notifikasi dijadwalkan untuk {$studentIds->count()} siswa pada {$announcement->published_at->translatedFormat('d F Y, H:i')} WIB."
            : "Notifikasi terkirim ke {$studentIds->count()} siswa.";

        return redirect()->route('admin.notifikasi')->with('success', $message);
    }

    /**
     * Detail notifikasi untuk admin: siapa saja yang sudah & belum membaca.
     */
    public function show(Announcement $announcement): View
    {
        $announcement->load(['classroom', 'creator']);
        $recipients = $announcement->recipients()
            ->with('student.classroom')
            ->get()
            ->sortBy(fn (AnnouncementRecipient $recipient) => [$recipient->isRead() ? 1 : 0, $recipient->student->name])
            ->values();

        return view('admin.notifikasi-detail', compact('announcement', 'recipients'));
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return redirect()->route('admin.notifikasi')->with('success', 'Notifikasi berhasil dihapus.');
    }

    /**
     * Semua notifikasi milik siswa yang sedang login.
     */
    public function studentIndex(Request $request, CurrentStudentResolver $resolver): View|RedirectResponse
    {
        $student = $this->resolveStudentOrRedirect($request->user(), $resolver);
        if ($student instanceof RedirectResponse) {
            return $student;
        }

        $notifications = AnnouncementRecipient::query()
            ->visibleTo($student)
            ->newestFirst()
            ->with('announcement')
            ->paginate(15);

        return view('siswa.notifikasi', compact('notifications'));
    }

    /**
     * Buka detail notifikasi sekaligus menandainya sudah dibaca.
     */
    public function studentShow(Request $request, Announcement $announcement, CurrentStudentResolver $resolver): View|RedirectResponse
    {
        $student = $this->resolveStudentOrRedirect($request->user(), $resolver);
        if ($student instanceof RedirectResponse) {
            return $student;
        }

        $recipient = AnnouncementRecipient::query()
            ->visibleTo($student)
            ->where('announcement_id', $announcement->id)
            ->firstOrFail();

        if (! $recipient->isRead()) {
            $recipient->update(['read_at' => now()]);
        }

        return view('siswa.notifikasi-detail', compact('announcement'));
    }

    public function markAllAsRead(Request $request, CurrentStudentResolver $resolver): RedirectResponse
    {
        $student = $this->resolveStudentOrRedirect($request->user(), $resolver);
        if ($student instanceof RedirectResponse) {
            return $student;
        }

        AnnouncementRecipient::query()->visibleTo($student)->unread()->update(['read_at' => now()]);

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }

    /**
     * Pengiriman ke semua siswa / per kelas hanya menjangkau siswa aktif,
     * bukan yang sudah lulus atau nonaktif.
     *
     * @param  array<int, int|string>  $selectedStudentIds
     * @return Collection<int, int>
     */
    private function recipientIdsFor(AnnouncementTarget $target, int $classroomId, array $selectedStudentIds): Collection
    {
        $query = match ($target) {
            AnnouncementTarget::AllStudents => Student::query()->where('status', StudentStatus::Active),
            AnnouncementTarget::Classroom => Student::query()->where('status', StudentStatus::Active)->where('classroom_id', $classroomId),
            AnnouncementTarget::SpecificStudents => Student::query()->whereIn('id', $selectedStudentIds),
        };

        return $query->pluck('id');
    }
}
