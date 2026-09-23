<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Notifications\NewFeedbackSubmitted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Feedback::class);

        $feedbacks = Feedback::query()->with('guardian')->latest()->paginate(15);

        $request->user()->unreadNotifications()->where('type', NewFeedbackSubmitted::class)->update(['read_at' => now()]);

        return view('admin.kritik-saran', compact('feedbacks'));
    }
}
