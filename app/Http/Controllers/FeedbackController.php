<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentStudent;
use App\Http\Requests\Feedback\StoreStudentFeedbackRequest;
use App\Models\Feedback;
use App\Models\User;
use App\Notifications\NewFeedbackSubmitted;
use App\Services\CurrentStudentResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    use ResolvesCurrentStudent;

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Feedback::class);

        $feedbacks = Feedback::query()->with(['guardian', 'student.classroom'])->latest()->paginate(15);

        $request->user()->unreadNotifications()->where('type', NewFeedbackSubmitted::class)->update(['read_at' => now()]);

        return view('admin.kritik-saran', compact('feedbacks'));
    }

    public function studentCreate(): View
    {
        return view('siswa.kritik-saran');
    }

    public function studentStore(StoreStudentFeedbackRequest $request, CurrentStudentResolver $resolver): RedirectResponse
    {
        $student = $this->resolveStudentOrRedirect($request->user(), $resolver);

        if ($student instanceof RedirectResponse) {
            return $student->with('error', 'Akun ini belum terhubung ke data siswa. Hubungi admin sekolah.');
        }

        $feedback = Feedback::create([
            'student_id' => $student->id,
            'message' => $request->validated('message'),
        ]);
        $feedback->loadMissing(['student.classroom', 'guardian']);

        User::role('admin')->get()->each(
            fn (User $admin) => $admin->notify(new NewFeedbackSubmitted($feedback))
        );

        return redirect()
            ->route('siswa.kritik-saran')
            ->with('success', 'Kritik atau saran berhasil dikirim kepada admin.');
    }
}
