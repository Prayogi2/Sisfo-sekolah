<?php

namespace App\Http\Controllers;

use App\Http\Requests\Feedback\StoreFeedbackRequest;
use App\Models\Feedback;
use App\Models\User;
use App\Notifications\NewFeedbackSubmitted;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    public function create(Request $request): View
    {
        $feedbacks = Feedback::query()
            ->where('guardian_id', $request->user()->guardian->id)
            ->latest()
            ->get();

        return view('wali-murid.kritik-saran', compact('feedbacks'));
    }

    public function store(StoreFeedbackRequest $request): RedirectResponse
    {
        $feedback = Feedback::create([
            ...$request->validated(),
            'guardian_id' => $request->user()->guardian->id,
        ]);

        Notification::send(User::role('admin')->get(), new NewFeedbackSubmitted($feedback->load('guardian')));

        return back()->with('success', 'Kritik & saran berhasil dikirim ke admin. Terima kasih!');
    }

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Feedback::class);

        $feedbacks = Feedback::query()->with('guardian')->latest()->paginate(15);

        $request->user()->unreadNotifications()->where('type', NewFeedbackSubmitted::class)->update(['read_at' => now()]);

        return view('admin.kritik-saran', compact('feedbacks'));
    }
}
