<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Pengaturan SPP. Nominal dipakai saat admin membuat tagihan bulanan.
 * Rekening tujuan sengaja dikosongkan — nomor rekening asli madrasah
 * harus diisi sendiri, tidak boleh ditebak/di-hardcode.
 */
class SppSettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::set('spp_amount', 350000);
        Setting::set('spp_bank_name', '');
        Setting::set('spp_bank_account', '');
        Setting::set('spp_bank_holder', '');
    }
}
