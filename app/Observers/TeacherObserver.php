<?php

namespace App\Observers;

use App\Models\Teacher;

class TeacherObserver
{
    /**
     * Handle the Teacher "created" event.
     */
    public function created(Teacher $teacher): void
    {
        $this->syncUsername($teacher);
    }

    /**
     * Handle the Teacher "updated" event.
     */
    public function updated(Teacher $teacher): void
    {
        if ($teacher->wasChanged('nip') || $teacher->wasChanged('user_id')) {
            $this->syncUsername($teacher);
        }
    }

    /**
     * Keep the linked user's login username equal to the teacher's NIP.
     */
    private function syncUsername(Teacher $teacher): void
    {
        if ($teacher->user_id && $teacher->nip) {
            $teacher->user?->update(['username' => $teacher->nip]);
        }
    }

    /**
     * Handle the Teacher "deleted" event.
     */
    public function deleted(Teacher $teacher): void
    {
        //
    }

    /**
     * Handle the Teacher "restored" event.
     */
    public function restored(Teacher $teacher): void
    {
        //
    }

    /**
     * Handle the Teacher "force deleted" event.
     */
    public function forceDeleted(Teacher $teacher): void
    {
        //
    }
}
