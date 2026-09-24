<?php

namespace App\Policies;

use App\Models\MonthlyReport;
use App\Models\User;

class MonthlyReportPolicy
{
    /**
     * Izinkan melihat laporan jika akun pengguna aktif.
     */
    public function view(User $user, MonthlyReport $report): bool
    {
        return $user->is_active;
    }

    /**
     * Mengedit form laporan hanya diperbolehkan jika status masih 'draft'.
     * Laporan berstatus 'final' terkunci bagi semua pengguna (termasuk admin)
     * sampai admin membuka kunci kembali (reopen).
     */
    public function update(User $user, MonthlyReport $report): bool
    {
        return $user->is_active && $report->isDraft();
    }

    /**
     * Memfinalisasi laporan (kunci) dapat dilakukan oleh petugas atau admin
     * selama laporan masih berstatus 'draft'.
     */
    public function finalize(User $user, MonthlyReport $report): bool
    {
        return $user->is_active && $report->isDraft();
    }

    /**
     * Membuka kembali (reopen) laporan yang terkunci HANYA dapat dilakukan oleh Administrator.
     */
    public function reopen(User $user, MonthlyReport $report): bool
    {
        return $user->isAdmin() && $user->is_active && $report->isFinal();
    }
}
