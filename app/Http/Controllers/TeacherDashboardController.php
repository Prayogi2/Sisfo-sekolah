<?php

namespace App\Http\Controllers;

use App\Enums\LeaveRequestStatus;
use App\Models\Classroom;
use App\Models\LeaveRequest;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Dashboard guru berpusat pada dua pekerjaannya: mengisi bank soal dan
 * menjalankan kuis CBT (membuka saat jam pelajaran, menutup setelahnya).
 */
class TeacherDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $teacher = Teacher::query()->where('user_id', $user->id)->first();

        $quizzes = Quiz::query()
            ->with(['subject', 'classroom'])
            ->withCount(['questions', 'attempts'])
            ->where('created_by', $user->id)
            ->orderByDesc('is_open')
            ->latest()
            ->get();

        $homeroomClassroomIds = $teacher
            ? Classroom::query()->where('homeroom_teacher_id', $teacher->id)->pluck('id')
            : collect();

        $stats = [
            'bank_soal' => QuizQuestion::query()->where('created_by', $user->id)->count(),
            'kuis_dibuka' => $quizzes->where('is_open', true)->count(),
            'kuis_total' => $quizzes->count(),
            'dikerjakan' => QuizAttempt::query()
                ->whereIn('quiz_id', $quizzes->pluck('id'))
                ->where('status', 'submitted')
                ->count(),
            'izin_pending' => LeaveRequest::query()
                ->where('status', LeaveRequestStatus::Pending)
                ->whereHas('student', fn ($query) => $query->whereIn('classroom_id', $homeroomClassroomIds))
                ->count(),
            'siswa_wali_kelas' => Student::query()->whereIn('classroom_id', $homeroomClassroomIds)->count(),
        ];

        return view('guru.dashboard', compact('teacher', 'quizzes', 'stats'));
    }
}
