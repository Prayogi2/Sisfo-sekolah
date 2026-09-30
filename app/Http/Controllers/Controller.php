<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Guru yang juga diberi akses admin tetap bekerja sebagai guru di
     * halaman /guru, dan sebagai admin di halaman /admin.
     */
    protected function actingAsGuru(Request $request): bool
    {
        return $request->user()->hasRole('guru') && ! $request->routeIs('admin.*');
    }
}
