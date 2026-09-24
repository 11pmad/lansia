import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import KelurahanTabs from '@/Components/KelurahanTabs';
import NumberField from '@/Components/NumberField';
import SectionCard from '@/Components/SectionCard';
import { calculateLayananRow } from '@/lib/calc';
import { formatNumber, getMonthName } from '@/lib/format';
import { lang } from '@/lang';
import { Head, Link } from '@inertiajs/react';
import axios from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

export default function LayananForm({
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

    const [openSection, setOpenSection] = useState('kelainan'); // 'sasaran' | 'kelainan' | 'tindakan' | 'lain'

    const [saveStatus, setSaveStatus] = useState('saved');
    const [savedAt, setSavedAt] = useState('');
    const debounceTimerRef = useRef(null);

    const currentKelurahan = kelurahans.find((k) => k.id === activeKelId) || kelurahans[0];
    const currentRowValues = rows[activeKelId] || {};
    const currentCalc = calcs[activeKelId] || calculateLayananRow(currentRowValues);

    const triggerSave = useCallback(
        async (kelId, valuesToSave) => {
            if (isLocked) return;
            setSaveStatus('saving');

            try {
                const response = await axios.put(
                    route('forms.layanan.update', {
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

                    setCalcs((prev) => ({
                        ...prev,
                        [kelId]: response.data.calculations,
                    }));

                    const totalItems = (response.data.calculations?.kelainan_total || 0) + (response.data.calculations?.tindakan_total || 0);
                    setCompletionMap((prev) => ({
                        ...prev,
                        [kelId]: totalItems > 0,
                    }));
                }
            } catch (err) {
                console.error(err);
                setSaveStatus('error');
            }
        },
        [report.id, isLocked]
    );

    const handleFieldChange = (fieldKey, value) => {
        if (isLocked) return;

        const updatedRow = {
            ...currentRowValues,
            [fieldKey]: value,
        };

        setRows((prev) => ({
            ...prev,
            [activeKelId]: updatedRow,
        }));

        const instantCalc = calculateLayananRow(updatedRow);
        setCalcs((prev) => ({
            ...prev,
            [activeKelId]: instantCalc,
        }));

        const totalItems = (instantCalc.kelainan_total || 0) + (instantCalc.tindakan_total || 0);
        setCompletionMap((prev) => ({
            ...prev,
            [activeKelId]: totalItems > 0,
        }));

        setSaveStatus('saving');
        if (debounceTimerRef.current) {
            clearTimeout(debounceTimerRef.current);
        }

        debounceTimerRef.current = setTimeout(() => {
            triggerSave(activeKelId, updatedRow);
        }, 1500);
    };

    useEffect(() => {
        return () => {
            if (debounceTimerRef.current) {
                clearTimeout(debounceTimerRef.current);
            }
        };
    }, []);

    const handleSelectKelurahan = (newKelId) => {
        if (newKelId === activeKelId) return;

        if (debounceTimerRef.current) {
            clearTimeout(debounceTimerRef.current);
            triggerSave(activeKelId, currentRowValues);
        }

        setActiveKelId(newKelId);
    };

    const handleNextKelurahan = () => {
        const currentIndex = kelurahans.findIndex((k) => k.id === activeKelId);
        if (currentIndex < kelurahans.length - 1) {
            handleSelectKelurahan(kelurahans[currentIndex + 1].id);
        } else {
            triggerSave(activeKelId, currentRowValues);
            window.location.href = route('reports.show', report.id);
        }
    };

    return (
        <AuthenticatedLayout
            header={`Form Layanan: ${currentKelurahan?.name || ''}`}
            backUrl={route('reports.show', report.id)}
        >
            <Head title={`Form Layanan — ${currentKelurahan?.name || ''}`} />

            <div className="space-y-4 max-w-3xl mx-auto">
                {/* Header Laporan & Status Kunci / Autosave */}
                <div className="flex items-center justify-between bg-white px-4 py-3 rounded-card border border-slate-200 shadow-sm">
                    <div>
                        <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            Form Layanan Lansia
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

                {/* Bagian Accordion Form */}
                <div className="space-y-3">
                    {/* 1. Sasaran Lansia */}
                    <SectionCard
                        title="1. Sasaran Lansia"
                        subtitle="Jumlah target sasaran lansia (disimpan terpisah sesuai Excel Dinkes)"
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

                    {/* 2. 14 Kelainan / Penyakit Lansia */}
                    <SectionCard
                        title="2. Kelainan & Masalah Kesehatan (14 Penyakit)"
                        subtitle="Pencatatan kasus baru & lama per jenis kelamin (Laki-laki & Perempuan)"
                        isOpen={openSection === 'kelainan'}
                        onToggle={() => setOpenSection(openSection === 'kelainan' ? null : 'kelainan')}
                    >
                        <div className="space-y-3.5">
                            {fieldSchema.kelainan?.map((item) => (
                                <div
                                    key={item.key}
                                    className="rounded-lg border border-slate-200 bg-slate-50/50 p-3"
                                >
                                    <h4 className="text-sm font-bold text-slate-900 mb-2">
                                        {item.label}
                                    </h4>
                                    <div className="grid grid-cols-2 gap-3">
                                        <NumberField
                                            id={item.lKey}
                                            label="Laki-laki (L)"
                                            value={currentRowValues[item.lKey]}
                                            disabled={isLocked}
                                            onChange={(val) => handleFieldChange(item.lKey, val)}
                                        />
                                        <NumberField
                                            id={item.pKey}
                                            label="Perempuan (P)"
                                            value={currentRowValues[item.pKey]}
                                            disabled={isLocked}
                                            onChange={(val) => handleFieldChange(item.pKey, val)}
                                        />
                                    </div>
                                </div>
                            ))}
                        </div>
                    </SectionCard>

                    {/* 3. Tindakan Pelayanan */}
                    <SectionCard
                        title="3. Tindakan Pelayanan"
                        subtitle="Pengobatan, konseling, dan kegiatan penyuluhan"
                        isOpen={openSection === 'tindakan'}
                        onToggle={() => setOpenSection(openSection === 'tindakan' ? null : 'tindakan')}
                    >
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            {fieldSchema.tindakan?.map((field) => (
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

                    {/* 4. Lain-lain & Keterangan */}
                    <SectionCard
                        title="4. Lain-lain & Catatan"
                        subtitle="Lansia bekerja, panti dibina, home care, serta keterangan khusus"
                        isOpen={openSection === 'lain'}
                        onToggle={() => setOpenSection(openSection === 'lain' ? null : 'lain')}
                    >
                        <div className="space-y-4">
                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                {fieldSchema.lain?.filter((f) => f.type === 'number').map((field) => (
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

                            <div>
                                <label
                                    htmlFor="keterangan"
                                    className="block text-sm font-semibold text-slate-800 mb-1"
                                >
                                    Keterangan Tambahan (Opsional)
                                </label>
                                <textarea
                                    id="keterangan"
                                    rows="3"
                                    disabled={isLocked}
                                    value={currentRowValues.keterangan || ''}
                                    placeholder="Tuliskan catatan khusus atau kendala lapangan jika ada..."
                                    onChange={(e) => handleFieldChange('keterangan', e.target.value)}
                                    className="w-full rounded-lg border-slate-300 text-sm text-slate-900 shadow-sm focus:border-teal-600 focus:ring-teal-600 disabled:bg-slate-100 disabled:text-slate-400"
                                />
                            </div>
                        </div>
                    </SectionCard>
                </div>

                {/* Kartu Ringkasan Hasil Otomatis */}
                <div className="rounded-card border border-teal-200 bg-teal-50/60 p-4 shadow-sm space-y-3">
                    <div className="flex items-center justify-between border-b border-teal-100 pb-2">
                        <span className="text-xs font-bold text-teal-800 uppercase tracking-wider">
                            Ringkasan Layanan · {currentKelurahan?.name}
                        </span>
                        <span className="text-[11px] font-semibold text-slate-500 bg-white px-2 py-0.5 rounded-full border border-teal-200">
                            {lang.common.calculatedAutomatically}
                        </span>
                    </div>

                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                        <div className="bg-white p-2.5 rounded-lg border border-teal-100">
                            <span className="text-[10px] font-semibold text-slate-500 block">Kelainan (Laki-laki)</span>
                            <span className="text-lg font-bold text-slate-900 tabular-nums">
                                {formatNumber(currentCalc.kelainan_total_l)}
                            </span>
                        </div>

                        <div className="bg-white p-2.5 rounded-lg border border-teal-100">
                            <span className="text-[10px] font-semibold text-slate-500 block">Kelainan (Perempuan)</span>
                            <span className="text-lg font-bold text-slate-900 tabular-nums">
                                {formatNumber(currentCalc.kelainan_total_p)}
                            </span>
                        </div>

                        <div className="bg-teal-700 text-white p-2.5 rounded-lg shadow-sm">
                            <span className="text-[10px] font-medium text-teal-100 block">Total Temuan Kelainan</span>
                            <span className="text-lg font-extrabold tabular-nums">
                                {formatNumber(currentCalc.kelainan_total)}
                            </span>
                        </div>

                        <div className="bg-white p-2.5 rounded-lg border border-teal-100">
                            <span className="text-[10px] font-semibold text-slate-500 block">Total Tindakan</span>
                            <span className="text-lg font-bold text-teal-800 tabular-nums">
                                {formatNumber(currentCalc.tindakan_total)}
                            </span>
                        </div>
                    </div>
                </div>

                {/* Sticky Action Footer */}
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
