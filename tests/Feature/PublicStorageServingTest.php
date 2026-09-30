<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Hosting (Hostinger) tidak selalu punya symlink public/storage, jadi file
 * di disk public juga disajikan Laravel lewat route /storage/{path}.
 */
class PublicStorageServingTest extends TestCase
{
    public function test_an_uploaded_photo_is_served_from_the_storage_url(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('foto.jpg')->store('student-photos', 'public');

        $this->get('/storage/'.$path)
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');
    }

    /**
     * View memakai Storage::url() (disk default), jadi URL itulah yang harus
     * benar-benar menyajikan foto di disk public.
     */
    public function test_the_photo_url_generated_by_views_serves_the_photo(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('foto.png')->store('student-photos', 'public');

        $this->get(Storage::url($path))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    public function test_a_missing_file_returns_not_found(): void
    {
        Storage::fake('public');

        $this->get('/storage/student-photos/tidak-ada.jpg')->assertNotFound();
    }

    public function test_files_cannot_be_uploaded_through_the_storage_url_without_a_signature(): void
    {
        Storage::fake('public');

        $this->call('PUT', '/storage/student-photos/titipan.php', content: '<?php echo 1;')->assertForbidden();

        Storage::disk('public')->assertMissing('student-photos/titipan.php');
    }
}
