<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_petugas_is_forbidden_from_accessing_admin_routes(): void
    {
        $petugas = User::factory()->create([
            'role' => 'petugas',
            'is_active' => true,
        ]);

        $this->actingAs($petugas)
            ->get('/kelurahan')
            ->assertForbidden();

        $this->actingAs($petugas)
            ->get('/pengguna')
            ->assertForbidden();
    }

    public function test_admin_can_access_admin_routes(): void
    {
        $admin = User::factory()->admin()->create([
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get('/kelurahan')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/pengguna')
            ->assertOk();
    }

    public function test_petugas_can_access_common_routes(): void
    {
        $petugas = User::factory()->create([
            'role' => 'petugas',
            'is_active' => true,
        ]);

        $this->actingAs($petugas)
            ->get('/dashboard')
            ->assertOk();

        $this->actingAs($petugas)
            ->get('/laporan')
            ->assertOk();

        $this->actingAs($petugas)
            ->get('/scan')
            ->assertOk();

        $this->actingAs($petugas)
            ->get('/statistik')
            ->assertOk();
    }

    public function test_inactive_user_cannot_authenticate(): void
    {
        $user = User::factory()->inactive()->create([
            'email' => 'inactive@sipela.test',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'inactive@sipela.test',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_seeded_users_can_authenticate(): void
    {
        $this->seed(DatabaseSeeder::class);

        $responsePetugas = $this->post('/login', [
            'email' => 'petugas@sipela.test',
            'password' => 'password',
        ]);
        $responsePetugas->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();

        $this->post('/logout');
        $this->assertGuest();

        $responseAdmin = $this->post('/login', [
            'email' => 'admin@sipela.test',
            'password' => 'password',
        ]);
        $responseAdmin->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();
    }
}
