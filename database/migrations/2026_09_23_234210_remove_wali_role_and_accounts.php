<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Portal wali murid dihapus: siswa sekarang login dengan akunnya sendiri.
     * Akun login wali ikut dihapus, tapi data orang tua/wali di Buku Induk
     * (tabel guardians) tetap disimpan.
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $waliUserIds = DB::table('users')->where('role', 'wali')->pluck('id');

        DB::transaction(function () use ($tableNames, $waliUserIds) {
            DB::table('guardians')->whereIn('user_id', $waliUserIds)->update(['user_id' => null]);
            DB::table('sessions')->whereIn('user_id', $waliUserIds)->delete();
            DB::table($tableNames['model_has_roles'])
                ->where('model_type', (new User)->getMorphClass())
                ->whereIn(config('permission.column_names.model_morph_key'), $waliUserIds)
                ->delete();
            DB::table('users')->whereIn('id', $waliUserIds)->delete();

            $waliRoleIds = DB::table($tableNames['roles'])->where('name', 'wali')->pluck('id');
            DB::table($tableNames['role_has_permissions'])->whereIn(config('permission.column_names.role_pivot_key') ?? 'role_id', $waliRoleIds)->delete();
            DB::table($tableNames['roles'])->whereIn('id', $waliRoleIds)->delete();
        });

        app()['cache']->forget(config('permission.cache.key'));
    }

    /**
     * Akun wali yang sudah dihapus tidak bisa dikembalikan.
     */
    public function down(): void
    {
        //
    }
};
