<?php

namespace Tests\Feature;

use App\Actions\CreateMonthlyReport;
use App\Jobs\ProcessOcrUpload;
use App\Models\Kelurahan;
use App\Models\KunjunganRow;
use App\Models\MonthlyReport;
use App\Models\OcrUpload;
use App\Models\Puskesmas;
use App\Models\User;
use App\Services\Ocr\MockOcrProvider;
use App\Services\Ocr\OcrProvider;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OcrWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Storage::fake('local');
    }

    /**
     * Memastikan guest tidak dapat mengakses rute-rute pemindaian OCR.
     */
    public function test_guest_cannot_access_ocr_routes(): void
    {
        $this->get('/scan')->assertRedirect('/login');
        $this->post('/scan', [])->assertRedirect('/login');
        $this->get('/scan/1')->assertRedirect('/login');
        $this->get('/scan/1/status')->assertRedirect('/login');
        $this->post('/scan/1/apply', [])->assertRedirect('/login');
    }

    /**
     * Memastikan halaman formulir scan /scan dapat dirender oleh petugas.
     */
    public function test_scan_page_can_be_rendered(): void
    {
        $petugas = User::where('role', 'petugas')->first();
        $puskesmas = Puskesmas::first();

        // Buat laporan draft untuk dipilih
        app(CreateMonthlyReport::class)->execute($puskesmas, 2026, 1, $petugas);

        $response = $this->actingAs($petugas)->get('/scan');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Ocr/Scan')
            ->has('draftReports', 1)
            ->has('sections')
            ->has('dailyLimit')
            ->where('todayUsage', 0)
        );
    }

    /**
     * Memastikan alur pengunggahan foto formulir memicu job dan menghasilkan status done.
     */
    public function test_uploading_photo_creates_ocr_upload_and_processes(): void
    {
        $petugas = User::where('role', 'petugas')->first();
        $puskesmas = Puskesmas::first();
        $report = app(CreateMonthlyReport::class)->execute($puskesmas, 2026, 1, $petugas);

        $file = UploadedFile::fake()->image('formulir_kunjungan.jpg', 200, 200);

        $response = $this->actingAs($petugas)->post('/scan', [
            'monthly_report_id' => $report->id,
            'section'           => 'kunjungan_dalam',
            'image'             => $file,
        ]);

        $upload = OcrUpload::latest()->first();
        $this->assertNotNull($upload);

        $response->assertRedirect(route('scan.show', $upload->id));

        $this->assertSame('kunjungan_dalam', $upload->section);
        $this->assertSame($report->id, $upload->monthly_report_id);
        $this->assertSame($petugas->id, $upload->created_by);

        // Pada mode queue=sync (default testing), Job langsung selesai dan status menjadi 'done'
        $upload->refresh();
        $this->assertSame('done', $upload->status);
        $this->assertNotEmpty($upload->result_json);
        $this->assertNotEmpty($upload->confidence_json);

        // Pastikan hasil ekstraksi memuat 6 kelurahan
        $this->assertCount(6, $upload->result_json);
    }

    /**
     * Memastikan endpoint polling status /scan/{id}/status mengembalikan data JSON yang valid.
     */
    public function test_polling_status_returns_json(): void
    {
        $petugas = User::where('role', 'petugas')->first();
        $puskesmas = Puskesmas::first();
        $report = app(CreateMonthlyReport::class)->execute($puskesmas, 2026, 1, $petugas);

        $upload = OcrUpload::create([
            'monthly_report_id' => $report->id,
            'section'           => 'kunjungan_dalam',
            'image_path'        => '/tmp/fake.jpg',
            'status'            => 'done',
            'result_json'       => [['row_index' => 1, 'kelurahan_name' => 'PAYOLANSEK', 'fields' => ['in_a6069_p_baru' => 40]]],
            'confidence_json'   => [['row_index' => 1, 'kelurahan_name' => 'PAYOLANSEK', 'fields' => ['in_a6069_p_baru' => 0.95]]],
            'created_by'        => $petugas->id,
        ]);

        $response = $this->actingAs($petugas)->get(route('scan.status', $upload->id));

        $response->assertOk();
        $response->assertJson([
            'id'     => $upload->id,
            'status' => 'done',
            'result_json' => [
                ['row_index' => 1, 'kelurahan_name' => 'PAYOLANSEK', 'fields' => ['in_a6069_p_baru' => 40]],
            ],
        ]);
    }

    /**
     * Memastikan halaman pratinjau /scan/{id} dapat dirender beserta prop hasil ekstraksi.
     */
    public function test_preview_page_can_be_rendered(): void
    {
        $petugas = User::where('role', 'petugas')->first();
        $puskesmas = Puskesmas::first();
        $report = app(CreateMonthlyReport::class)->execute($puskesmas, 2026, 1, $petugas);

        $upload = OcrUpload::create([
            'monthly_report_id' => $report->id,
            'section'           => 'kunjungan_dalam',
            'image_path'        => '/tmp/fake.jpg',
            'status'            => 'done',
            'result_json'       => [['row_index' => 1, 'kelurahan_name' => 'PAYOLANSEK', 'fields' => ['in_a6069_p_baru' => 40]]],
            'confidence_json'   => [['row_index' => 1, 'kelurahan_name' => 'PAYOLANSEK', 'fields' => ['in_a6069_p_baru' => 0.95]]],
            'created_by'        => $petugas->id,
        ]);

        $response = $this->actingAs($petugas)->get(route('scan.show', $upload->id));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Ocr/Preview')
            ->has('upload', fn (Assert $u) => $u
                ->where('id', $upload->id)
                ->where('status', 'done')
                ->where('section', 'kunjungan_dalam')
                ->etc()
            )
            ->has('fields')
            ->has('kelurahans', 6)
        );
    }

    /**
     * Memastikan tombol Simpan (apply) menuliskan nilai OCR/koreksi ke tabel kunjungan_rows.
     */
    public function test_applying_ocr_results_updates_kunjungan_rows(): void
    {
        $petugas = User::where('role', 'petugas')->first();
        $puskesmas = Puskesmas::first();
        $report = app(CreateMonthlyReport::class)->execute($puskesmas, 2026, 1, $petugas);
        $payolansek = Kelurahan::where('name', 'PAYOLANSEK')->first();

        $upload = OcrUpload::create([
            'monthly_report_id' => $report->id,
            'section'           => 'kunjungan_dalam',
            'image_path'        => '/tmp/fake.jpg',
            'status'            => 'done',
            'created_by'        => $petugas->id,
        ]);

        $payload = [
            'rows' => [
                [
                    'kelurahan_id' => $payolansek->id,
                    'fields'       => [
                        'in_a6069_l_baru' => 16,
                        'in_a6069_p_baru' => 40,
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($petugas)->post(route('scan.apply', $upload->id), $payload);

        // Redirect ke form kunjungan
        $response->assertRedirect(route('forms.kunjungan.edit', $report->id));

        // Verifikasi database kunjungan_rows terisi angka hasil scan
        $row = KunjunganRow::where('monthly_report_id', $report->id)
            ->where('kelurahan_id', $payolansek->id)
            ->first();

        $this->assertSame(16, $row->in_a6069_l_baru);
        $this->assertSame(40, $row->in_a6069_p_baru);

        // Verifikasi status upload berubah menjadi applied
        $upload->refresh();
        $this->assertSame('applied', $upload->status);
        $this->assertNotNull($upload->applied_at);
    }

    /**
     * Memastikan batas pemindaian harian (30 kali/hari) diterapkan dan mencegah upload berikutnya.
     */
    public function test_daily_scan_limit_is_enforced(): void
    {
        $petugas = User::where('role', 'petugas')->first();
        $puskesmas = Puskesmas::first();
        $report = app(CreateMonthlyReport::class)->execute($puskesmas, 2026, 1, $petugas);

        // Buat 30 upload hari ini untuk petugas
        for ($i = 0; $i < 30; $i++) {
            OcrUpload::create([
                'monthly_report_id' => $report->id,
                'section'           => 'kunjungan_dalam',
                'image_path'        => "/tmp/fake_{$i}.jpg",
                'status'            => 'done',
                'created_by'        => $petugas->id,
            ]);
        }

        $file = UploadedFile::fake()->image('scan_31.jpg');

        $response = $this->actingAs($petugas)->post('/scan', [
            'monthly_report_id' => $report->id,
            'section'           => 'kunjungan_dalam',
            'image'             => $file,
        ]);

        $response->assertSessionHasErrors('image');
        $this->assertCount(30, OcrUpload::where('created_by', $petugas->id)->get());
    }

    /**
     * Memastikan kegagalan provider OCR mencatat status failed dan pesan error ramah.
     */
    public function test_failed_ocr_processing_updates_status_and_error(): void
    {
        $mockProvider = new MockOcrProvider();
        $mockProvider->setShouldFail(true, 'AI tidak dapat membaca gambar yang buram.');
        $this->app->instance(OcrProvider::class, $mockProvider);

        $petugas = User::where('role', 'petugas')->first();
        $puskesmas = Puskesmas::first();
        $report = app(CreateMonthlyReport::class)->execute($puskesmas, 2026, 1, $petugas);

        $upload = OcrUpload::create([
            'monthly_report_id' => $report->id,
            'section'           => 'kunjungan_dalam',
            'image_path'        => '/tmp/fake.jpg',
            'status'            => 'queued',
            'created_by'        => $petugas->id,
        ]);

        ProcessOcrUpload::dispatchSync($upload->id);

        $upload->refresh();
        $this->assertSame('failed', $upload->status);
        $this->assertStringContainsString('AI tidak dapat membaca gambar', $upload->error);
    }

    /**
     * Memastikan streaming foto formulir aman dan dapat diakses petugas.
     */
    public function test_image_streaming_route(): void
    {
        $petugas = User::where('role', 'petugas')->first();
        $puskesmas = Puskesmas::first();
        $report = app(CreateMonthlyReport::class)->execute($puskesmas, 2026, 1, $petugas);

        $tempPath = tempnam(sys_get_temp_dir(), 'test_img_') . '.jpg';
        file_put_contents($tempPath, 'fake_jpg_binary');

        $upload = OcrUpload::create([
            'monthly_report_id' => $report->id,
            'section'           => 'kunjungan_dalam',
            'image_path'        => $tempPath,
            'status'            => 'done',
            'created_by'        => $petugas->id,
        ]);

        try {
            $response = $this->actingAs($petugas)->get(route('scan.image', $upload->id));
            $response->assertOk();
        } finally {
            @unlink($tempPath);
        }
    }
}
