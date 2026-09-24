import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import KelurahanTabs from '@/Components/KelurahanTabs';
import NumberField from '@/Components/NumberField';
import SectionCard from '@/Components/SectionCard';
import { calculateKunjunganRow } from '@/lib/calc';
import { formatNumber, formatPercentage, getMonthName } from '@/lib/format';
import { lang } from '@/lang';
import { Head, Link } from '@inertiajs/react';
import axios from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

export default function KunjunganForm({
    report,
    kelurahans = [],
    activeKelurahanId: initialKelId,
    initialRows = {},
    initialCalcs = {},
    completionMap: initialCompletionMap = {},
    fieldSchema = {},
}) {
    const isLocked = report.status === 'final';
    const monthName = getMonthName(report.month);

    const [activeKelId, setActiveKelId] = useState(initialKelId);
    const [rows, setRows] = useState(initialRows);
    const [calcs, setCalcs] = useState(initialCalcs);
    const [completionMap, setCompletionMap] = useState(initialCompletionMap);

    // Accordion: hanya 1 bagian terbuka pada satu waktu
    const [openSection, setOpenSection] = useState('umum'); // 'umum' | 'sasaran' | 'dalam' | 'luar' | 'mandiri'

    // Autosave state
    const [saveStatus, setSaveStatus] = useState('saved'); // 'saved' | 'saving' | 'error'
    const [savedAt, setSavedAt] = useState('');
    const debounceTimerRef = useRef(null);

    const currentKelurahan = kelurahans.find((k) => k.id === activeKelId) || kelurahans[0];
    const currentRowValues = rows[activeKelId] || {};
    const currentCalc = calcs[activeKelId] || calculateKunjunganRow(currentRowValues);

    // Fungsi autosave ke backend
    const triggerSave = useCallback(
        async (kelId, valuesToSave) => {
            if (isLocked) return;
            setSaveStatus('saving');

            try {
                const response = await axios.put(
                    route('forms.kunjungan.update', {
                        report: report.id,
                        kelurahan: kelId,
                    }),
                    valuesToSave,
                    {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    }
                );

                if (response.data?.success) {
                    setSaveStatus('saved');
                    setSavedAt(response.data.saved_at || new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }));

                    // Update perhitungan dari response backend
                    setCalcs((prev) => ({
                        ...prev,
                        [kelId]: response.data.calculations,
                    }));

                    setCompletionMap((prev) => ({
                        ...prev,
                        [kelId]: (response.data.calculations?.total_kunjungan_bulan || 0) > 0,
                    }));
                }
            } catch (err) {
                console.error(err);
                setSaveStatus('error');
            }
        },
        [report.id, isLocked]
    );

    // Handler update nilai per field dengan debounce 1,5 dtk
    const handleFieldChange = (fieldKey, value) => {
        if (isLocked) return;

        const updatedRow = {
            ...currentRowValues,
            [fieldKey]: value,
        };

        // Update local state instan
        setRows((prev) => ({
            ...prev,
            [activeKelId]: updatedRow,
        }));

        // Kalkulasi instan di frontend via lib/calc.js (cermin)
        const instantCalc = calculateKunjunganRow(updatedRow);
        setCalcs((prev) => ({
            ...prev,
            [activeKelId]: instantCalc,
        }));

        setCompletionMap((prev) => ({
            ...prev,
            [activeKelId]: (instantCalc.total_kunjungan_bulan || 0) > 0,
        }));

        // Debounce autosave 1.5 detik
        setSaveStatus('saving');
        if (debounceTimerRef.current) {
            clearTimeout(debounceTimerRef.current);
        }

        debounceTimerRef.current = setTimeout(() => {
            triggerSave(activeKelId, updatedRow);
        }, 1500);
    };

    // Bersihkan timer saat unmount
    useEffect(() => {
        return () => {
            if (debounceTimerRef.current) {
                clearTimeout(debounceTimerRef.current);
            }
        };
    }, []);

    // Pindah kelurahan
    const handleSelectKelurahan = (newKelId) => {
        if (newKelId === activeKelId) return;

        // Simpan langsung data kelurahan saat ini jika masih ada pending debounce
        if (debounceTimerRef.current) {
            clearTimeout(debounceTimerRef.current);
            triggerSave(activeKelId, currentRowValues);
        }

        setActiveKelId(newKelId);
    };

    // Navigasi ke kelurahan berikutnya
    const handleNextKelurahan = () => {
        const currentIndex = kelurahans.findIndex((k) => k.id === activeKelId);
        if (currentIndex < kelurahans.length - 1) {
            handleSelectKelurahan(kelurahans[currentIndex + 1].id);
        } else {
            // Jika sudah kelurahan terakhir, simpan dan buka form layanan atau kembali ke show
            triggerSave(activeKelId, currentRowValues);
            window.location.href = route('reports.show', report.id);
        }
    };

    return (
        <AuthenticatedLayout
            header={`Form Kunjungan: ${currentKelurahan?.name || ''}`}
            backUrl={route('reports.show', report.id)}
        >
            <Head title={`Form Kunjungan — ${currentKelurahan?.name || ''}`} />

            <div className="space-y-4 max-w-3xl mx-auto">
                {/* Header Laporan & Status Kunci / Autosave */}
                <div className="flex items-center justify-between bg-white px-4 py-3 rounded-card border border-slate-200 shadow-sm">
                    <div>
                        <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            Form Kunjungan Lansia
                        </span>
                        <h1 className="text-base font-bold text-slate-900 leading-tight">
                            Bulan {monthName} {report.year}
                        </h1>
                    </div>

                    <div className="flex items-center gap-2">
                        {isLocked ? (
                            <span className="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700 border border-slate-300">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" className="h-3 w-3">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                </svg>
                                Terkunci
                            </span>
                        ) : (
                            <div className="text-right">
                                {saveStatus === 'saving' && (
                                    <span className="inline-flex items-center gap-1 text-xs font-semibold text-amber-700">
                                        <svg className="animate-spin h-3.5 w-3.5 text-amber-600" viewBox="0 0 24 24" fill="none">
                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
                                        </svg>
                                        Menyimpan...
                                    </span>
                                )}
                                {saveStatus === 'saved' && (
                                    <span className="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" className="h-3.5 w-3.5">
                                            <polyline points="20 6 9 17 4 12" />
                                        </svg>
                                        Tersimpan {savedAt ? `✓ ${savedAt}` : '✓'}
                                    </span>
                                )}
                                {saveStatus === 'error' && (
                                    <span className="text-xs font-semibold text-rose-600">
                                        Gagal menyimpan! Periksa koneksi.
                                    </span>
                                )}
                            </div>
                        )}
                    </div>
                </div>

                {/* Tabs Kelurahan */}
                <KelurahanTabs
                    kelurahans={kelurahans}
                    activeId={activeKelId}
                    onSelect={handleSelectKelurahan}
                    completionMap={completionMap}
                />

                {/* 5 Bagian Accordion Form */}
                <div className="space-y-3">
                    {/* 1. Bagian Umum & Posyandu */}
                    <SectionCard
                        title="1. Data Umum & Posyandu"
                        subtitle="Jumlah posyandu, kader terlatih, dan kepemilikan JKN"
                        isOpen={openSection === 'umum'}
                        onToggle={() => setOpenSection(openSection === 'umum' ? null : 'umum')}
                    >
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            {fieldSchema.umum?.map((field) => (
                                <NumberField
                                    key={field.key}
                                    id={field.key}
                                    label={field.label}
                                    value={currentRowValues[field.key]}
                                    disabled={isLocked}
                                    showStepper={true}
                                    onChange={(val) => handleFieldChange(field.key, val)}
                                />
                            ))}
                        </div>
                    </SectionCard>

                    {/* 2. Bagian Sasaran Lansia */}
                    <SectionCard
                        title="2. Sasaran Lansia"
                        subtitle="Target sasaran lansia per kelompok umur (L & P)"
                        isOpen={openSection === 'sasaran'}
                        onToggle={() => setOpenSection(openSection === 'sasaran' ? null : 'sasaran')}
                    >
                        <div className="space-y-4">
                            {['a4559', 'a6069', 'a70'].map((age) => {
                                const ageFields = fieldSchema.sasaran?.filter((f) => f.age === age) || [];
                                const ageLabel = ageFields[0]?.ageLabel || age;

                                return (
                                    <div key={age} className="rounded-lg bg-slate-50/80 p-3.5 border border-slate-200/80">
                                        <h4 className="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2.5">
                                            Kelompok Umur: {ageLabel}
                                        </h4>
                                        <div className="grid grid-cols-2 gap-3">
                                            {ageFields.map((field) => (
                                                <NumberField
                                                    key={field.key}
                                                    id={field.key}
                                                    label={field.sexLabel === 'Laki-laki' ? 'Laki-laki (L)' : 'Perempuan (P)'}
                                                    value={currentRowValues[field.key]}
                                                    disabled={isLocked}
                                                    showStepper={true}
                                                    onChange={(val) => handleFieldChange(field.key, val)}
                                                />
                                            ))}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </SectionCard>

                    {/* 3. Bagian Kunjungan Dalam Gedung */}
                    <SectionCard
                        title="3. Kunjungan Dalam Gedung (Puskesmas)"
                        subtitle="12 Kolom kunjungan: 3 kelompok umur × 2 jenis kelamin × (Lama / Baru)"
                        isOpen={openSection === 'dalam'}
                        onToggle={() => setOpenSection(openSection === 'dalam' ? null : 'dalam')}
                    >
                        <div className="space-y-4">
                            {['a4559', 'a6069', 'a70'].map((age) => {
                                const ageFields = fieldSchema.dalam?.filter((f) => f.age === age) || [];
                                const ageLabel = ageFields[0]?.ageLabel || age;

                                return (
                                    <div key={age} className="rounded-lg bg-slate-50/80 p-3.5 border border-slate-200/80">
                                        <h4 className="text-xs font-bold text-teal-800 uppercase tracking-wider mb-3">
                                            {ageLabel} (Dalam Gedung)
                                        </h4>
                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            {/* Laki-laki */}
                                            <div className="rounded-md bg-blue-50/60 p-2.5 border border-blue-200">
                                                <span className="text-xs font-bold text-blue-900 block mb-2">
                                                    Laki-laki
                                                </span>
                                                <div className="grid grid-cols-2 gap-2">
                                                    {ageFields.filter((f) => f.sex === 'l').map((field) => (
                                                        <NumberField
                                                            key={field.key}
                                                            id={field.key}
                                                            label={field.typeLabel}
                                                            value={currentRowValues[field.key]}
                                                            disabled={isLocked}
                                                            onChange={(val) => handleFieldChange(field.key, val)}
                                                        />
                                                    ))}
                                                </div>
                                            </div>

                                            {/* Perempuan */}
                                            <div className="rounded-md bg-pink-50/60 p-2.5 border border-pink-200">
                                                <span className="text-xs font-bold text-pink-900 block mb-2">
                                                    Perempuan
                                                </span>
                                                <div className="grid grid-cols-2 gap-2">
                                                    {ageFields.filter((f) => f.sex === 'p').map((field) => (
                                                        <NumberField
                                                            key={field.key}
                                                            id={field.key}
                                                            label={field.typeLabel}
                                                            value={currentRowValues[field.key]}
                                                            disabled={isLocked}
                                                            onChange={(val) => handleFieldChange(field.key, val)}
                                                        />
                                                    ))}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </SectionCard>

                    {/* 4. Bagian Kunjungan Luar Gedung */}
                    <SectionCard
                        title="4. Kunjungan Luar Gedung (Posyandu)"
                        subtitle="12 Kolom kunjungan: 3 kelompok umur × 2 jenis kelamin × (Lama / Baru)"
                        isOpen={openSection === 'luar'}
                        onToggle={() => setOpenSection(openSection === 'luar' ? null : 'luar')}
                    >
                        <div className="space-y-4">
                            {['a4559', 'a6069', 'a70'].map((age) => {
                                const ageFields = fieldSchema.luar?.filter((f) => f.age === age) || [];
                                const ageLabel = ageFields[0]?.ageLabel || age;

                                return (
                                    <div key={age} className="rounded-lg bg-slate-50/80 p-3.5 border border-slate-200/80">
                                        <h4 className="text-xs font-bold text-amber-800 uppercase tracking-wider mb-3">
                                            {ageLabel} (Luar Gedung / Posyandu)
                                        </h4>
                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            {/* Laki-laki */}
                                            <div className="rounded-md bg-blue-50/60 p-2.5 border border-blue-200">
                                                <span className="text-xs font-bold text-blue-900 block mb-2">
                                                    Laki-laki
                                                </span>
                                                <div className="grid grid-cols-2 gap-2">
                                                    {ageFields.filter((f) => f.sex === 'l').map((field) => (
                                                        <NumberField
                                                            key={field.key}
                                                            id={field.key}
                                                            label={field.typeLabel}
                                                            value={currentRowValues[field.key]}
                                                            disabled={isLocked}
                                                            onChange={(val) => handleFieldChange(field.key, val)}
                                                        />
                                                    ))}
                                                </div>
                                            </div>

                                            {/* Perempuan */}
                                            <div className="rounded-md bg-pink-50/60 p-2.5 border border-pink-200">
                                                <span className="text-xs font-bold text-pink-900 block mb-2">
                                                    Perempuan
                                                </span>
                                                <div className="grid grid-cols-2 gap-2">
                                                    {ageFields.filter((f) => f.sex === 'p').map((field) => (
                                                        <NumberField
                                                            key={field.key}
                                                            id={field.key}
                                                            label={field.typeLabel}
                                                            value={currentRowValues[field.key]}
                                                            disabled={isLocked}
                                                            onChange={(val) => handleFieldChange(field.key, val)}
                                                        />
                                                    ))}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </SectionCard>

                    {/* 5. Bagian Kemandirian */}
                    <SectionCard
                        title="5. Tingkat Kemandirian Lansia"
                        subtitle="Kategori A (Mandiri), B (Ringan/Sedang), C (Ketergantungan)"
                        isOpen={openSection === 'mandiri'}
                        onToggle={() => setOpenSection(openSection === 'mandiri' ? null : 'mandiri')}
                    >
                        <div className="space-y-4">
                            <div className="rounded-lg bg-slate-50/80 p-3.5 border border-slate-200/80">
                                <h4 className="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2.5">
                                    Usia 60–69 Tahun
                                </h4>
                                <div className="grid grid-cols-3 gap-2 sm:gap-3">
                                    {['mandiri_6069_a', 'mandiri_6069_b', 'mandiri_6069_c'].map((key) => {
                                        const grade = key.slice(-1).toUpperCase();
                                        return (
                                            <NumberField
                                                key={key}
                                                id={key}
                                                label={`Tingkat ${grade}`}
                                                value={currentRowValues[key]}
                                                disabled={isLocked}
                                                onChange={(val) => handleFieldChange(key, val)}
                                            />
                                        );
                                    })}
                                </div>
                            </div>

                            <div className="rounded-lg bg-slate-50/80 p-3.5 border border-slate-200/80">
                                <h4 className="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2.5">
                                    Usia &gt;70 Tahun
                                </h4>
                                <div className="grid grid-cols-3 gap-2 sm:gap-3">
                                    {['mandiri_70_a', 'mandiri_70_b', 'mandiri_70_c'].map((key) => {
                                        const grade = key.slice(-1).toUpperCase();
                                        return (
                                            <NumberField
                                                key={key}
                                                id={key}
                                                label={`Tingkat ${grade}`}
                                                value={currentRowValues[key]}
                                                disabled={isLocked}
                                                onChange={(val) => handleFieldChange(key, val)}
                                            />
                                        );
                                    })}
                                </div>
                            </div>
                        </div>
                    </SectionCard>
                </div>

                {/* Kartu Ringkasan Hasil Otomatis (Live Calculation) */}
                <div className="rounded-card border border-teal-200 bg-teal-50/60 p-4 shadow-sm space-y-3">
                    <div className="flex items-center justify-between border-b border-teal-100 pb-2">
                        <span className="text-xs font-bold text-teal-800 uppercase tracking-wider">
                            Hasil Hitung Otomatis · {currentKelurahan?.name}
                        </span>
                        <span className="text-[11px] font-semibold text-slate-500 bg-white px-2 py-0.5 rounded-full border border-teal-200">
                            {lang.common.calculatedAutomatically}
                        </span>
                    </div>

                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                        <div className="bg-white p-2.5 rounded-lg border border-teal-100">
                            <span className="text-[10px] font-semibold text-slate-500 block">Total Sasaran &gt;60 Th</span>
                            <span className="text-lg font-bold text-slate-900 tabular-nums">
                                {formatNumber(currentCalc.total_lansia_60)}
                            </span>
                        </div>

                        <div className="bg-white p-2.5 rounded-lg border border-teal-100">
                            <span className="text-[10px] font-semibold text-slate-500 block">Kunjungan &gt;60 Th (L/P)</span>
                            <span className="text-lg font-bold text-slate-900 tabular-nums">
                                {currentCalc.spm_l_abs} / {currentCalc.spm_p_abs}
                            </span>
                        </div>

                        <div className="bg-white p-2.5 rounded-lg border border-teal-100">
                            <span className="text-[10px] font-semibold text-slate-500 block">Total Standar SPM</span>
                            <span className="text-lg font-bold text-teal-800 tabular-nums">
                                {formatNumber(currentCalc.spm_total)}
                            </span>
                        </div>

                        <div className="bg-teal-700 text-white p-2.5 rounded-lg shadow-sm">
                            <span className="text-[10px] font-medium text-teal-100 block">Capaian SPM (%)</span>
                            <span className="text-lg font-extrabold tabular-nums">
                                {formatPercentage(currentCalc.spm_pct)}
                            </span>
                        </div>
                    </div>
                </div>

                {/* Sticky Action Footer (di atas BottomNav pada mobile) */}
                <div className="sticky bottom-16 lg:bottom-4 z-20 bg-white/95 backdrop-blur-sm p-3 rounded-card border border-slate-200 shadow-md flex items-center justify-between gap-3">
                    <span className="text-xs text-slate-500 font-medium hidden sm:inline">
                        Kelurahan: <strong className="text-slate-800">{currentKelurahan?.name}</strong>
                    </span>

                    <button
                        type="button"
                        onClick={handleNextKelurahan}
                        className="flex-1 sm:flex-initial flex min-h-[48px] items-center justify-center gap-2 rounded-lg bg-teal-700 px-6 py-2.5 text-base font-bold text-white shadow-sm hover:bg-teal-800 active:bg-teal-900 transition"
                    >
                        Simpan & Lanjut ke Kelurahan Berikutnya
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" className="h-5 w-5">
                            <path d="m9 18 6-6-6-6" />
                        </svg>
                    </button>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
