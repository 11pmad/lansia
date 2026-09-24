import React, { useState, useRef } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { lang } from '@/lang';
import { getMonthName } from '@/lib/format';
import { Head, Link, router } from '@inertiajs/react';

export default function Scan({
    draftReports = [],
    selectedReportId = 0,
    selectedSection = 'kunjungan_umum_sasaran',
    sections = [],
    todayUsage = 0,
    dailyLimit = 30,
}) {
    const [reportId, setReportId] = useState(selectedReportId || (draftReports[0]?.id ?? ''));
    const [section, setSection] = useState(selectedSection);
    const [previewUrl, setPreviewUrl] = useState(null);
    const [fileToUpload, setFileToUpload] = useState(null);
    const [isProcessingFile, setIsProcessingFile] = useState(false);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [errorMessage, setErrorMessage] = useState('');

    const fileInputRef = useRef(null);
    const cameraInputRef = useRef(null);

    // Klien: Perkecil sisi terpanjang ke <= 2000px, JPEG quality 0.85 sesuai docs/06_SKILL_OCR.md
    const processImageFile = (file) => {
        if (!file) return;

        setErrorMessage('');
        setIsProcessingFile(true);

        const reader = new FileReader();
        reader.onload = (e) => {
            const img = new Image();
            img.onload = () => {
                const maxPx = 2000;
                let width = img.width;
                let height = img.height;

                if (width > maxPx || height > maxPx) {
                    if (width > height) {
                        height = Math.round((height * maxPx) / width);
                        width = maxPx;
                    } else {
                        width = Math.round((width * maxPx) / height);
                        height = maxPx;
                    }
                }

                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);

                canvas.toBlob(
                    (blob) => {
                        setIsProcessingFile(false);
                        if (!blob) {
                            setErrorMessage('Gagal memproses gambar. Silakan coba kembali.');
                            return;
                        }

                        const resizedFile = new File([blob], file.name || 'scan.jpg', {
                            type: 'image/jpeg',
                            lastModified: Date.now(),
                        });

                        setFileToUpload(resizedFile);
                        setPreviewUrl(URL.createObjectURL(blob));
                    },
                    'image/jpeg',
                    0.85
                );
            };

            img.onerror = () => {
                setIsProcessingFile(false);
                setErrorMessage('Format gambar tidak didukung atau berkas rusak.');
            };

            img.src = e.target.result;
        };

        reader.readAsDataURL(file);
    };

    const handleFileChange = (e) => {
        const file = e.target.files?.[0];
        if (file) {
            processImageFile(file);
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();

        if (!reportId) {
            setErrorMessage('Pilih laporan draft tujuan terlebih dahulu.');
            return;
        }

        if (!fileToUpload) {
            setErrorMessage('Silakan ambil foto atau pilih berkas gambar formulir.');
            return;
        }

        setIsSubmitting(true);

        const formData = new FormData();
        formData.append('monthly_report_id', reportId);
        formData.append('section', section);
        formData.append('image', fileToUpload);

        router.post(route('scan.store'), formData, {
            forceFormData: true,
            onError: (errors) => {
                setIsSubmitting(false);
                setErrorMessage(Object.values(errors)[0] || 'Gagal mengunggah foto.');
            },
        });
    };

    const isLimitExceeded = todayUsage >= dailyLimit;

    return (
        <AuthenticatedLayout header={lang.ocr.title}>
            <Head title={lang.ocr.title} />

            <div className="max-w-3xl mx-auto space-y-6">
                {/* Header Card */}
                <div className="rounded-card border border-teal-200 bg-gradient-to-br from-teal-50/70 to-white p-5 sm:p-6 shadow-sm">
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div>
                            <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-teal-100 text-teal-800 text-xs font-bold mb-2">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" className="h-3.5 w-3.5">
                                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z" />
                                    <circle cx="12" cy="13" r="4" />
                                </svg>
                                OCR AI Vision Server
                            </span>
                            <h1 className="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
                                {lang.ocr.title}
                            </h1>
                            <p className="text-xs sm:text-sm text-slate-600 mt-1">
                                {lang.ocr.subtitle}
                            </p>
                        </div>

                        {/* Kuota Pemindaian Harian */}
                        <div className="rounded-xl bg-white border border-slate-200 p-3 text-right shrink-0">
                            <span className="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">
                                Kuota Hari Ini
                            </span>
                            <span className={`text-base font-extrabold tabular-nums ${isLimitExceeded ? 'text-rose-600' : 'text-teal-900'}`}>
                                {todayUsage} / {dailyLimit}
                            </span>
                            <span className="text-[10px] text-slate-400 block">pemindaian</span>
                        </div>
                    </div>
                </div>

                {/* Notifikasi Bila Belum Ada Laporan Draft */}
                {draftReports.length === 0 ? (
                    <div className="rounded-card border border-amber-200 bg-amber-50 p-6 text-center space-y-4">
                        <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-amber-800">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="h-6 w-6">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                                <line x1="16" y1="2" x2="16" y2="6" />
                                <line x1="8" y1="2" x2="8" y2="6" />
                                <line x1="3" y1="10" x2="21" y2="10" />
                            </svg>
                        </div>
                        <div>
                            <h2 className="text-base font-bold text-amber-900">Belum Ada Laporan Berstatus Draft</h2>
                            <p className="text-xs text-amber-800 mt-1 max-w-md mx-auto">
                                Pemindaian OCR memerlukan laporan bulanan draf sebagai tempat menyimpan data hasil pembacaan.
                            </p>
                        </div>
                        <Link
                            href={route('reports.index')}
                            className="inline-flex min-h-[44px] items-center gap-2 rounded-lg bg-teal-700 px-5 py-2.5 text-sm font-bold text-white hover:bg-teal-800 transition"
                        >
                            Buka Daftar Laporan &amp; Buat Baru →
                        </Link>
                    </div>
                ) : (
                    <form onSubmit={handleSubmit} className="space-y-6">
                        {/* Langkah 1: Pilih Laporan & Bagian */}
                        <div className="rounded-card border border-slate-200 bg-white p-5 sm:p-6 shadow-sm space-y-5">
                            <h2 className="text-sm font-bold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-3 flex items-center gap-2">
                                <span className="flex h-5 w-5 items-center justify-center rounded-full bg-teal-700 text-white text-[11px] font-bold">1</span>
                                {lang.ocr.selectReport} &amp; Bagian
                            </h2>

                            {/* Pilihan Laporan Draft */}
                            <div>
                                <label htmlFor="report-select" className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                    {lang.ocr.selectReport}
                                </label>
                                <select
                                    id="report-select"
                                    value={reportId}
                                    onChange={(e) => setReportId(Number(e.target.value))}
                                    className="w-full min-h-[48px] rounded-xl border border-slate-300 bg-white py-2.5 px-3.5 text-sm font-semibold text-slate-800 shadow-sm focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600"
                                >
                                    {draftReports.map((r) => (
                                        <option key={r.id} value={r.id}>
                                            Laporan Bulan {getMonthName(r.month)} {r.year} (Draft)
                                        </option>
                                    ))}
                                </select>
                            </div>

                            {/* Pilihan Bagian Formulir (Radio Cards) */}
                            <div>
                                <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2.5">
                                    {lang.ocr.selectSection}
                                </label>

                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    {sections.map((sec) => {
                                        const isSelected = section === sec.id;
                                        return (
                                            <div
                                                key={sec.id}
                                                onClick={() => setSection(sec.id)}
                                                className={`cursor-pointer rounded-xl border p-4 transition flex flex-col justify-between ${
                                                    isSelected
                                                        ? 'border-teal-600 bg-teal-50/60 ring-2 ring-teal-600'
                                                        : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50'
                                                }`}
                                            >
                                                <div>
                                                    <div className="flex items-center justify-between gap-2">
                                                        <span className="font-bold text-sm text-slate-900">
                                                            {sec.title}
                                                        </span>
                                                        <span className={`text-[10px] font-bold px-2 py-0.5 rounded uppercase tracking-wider ${
                                                            sec.form_type === 'kunjungan'
                                                                ? 'bg-blue-100 text-blue-800'
                                                                : 'bg-emerald-100 text-emerald-800'
                                                        }`}>
                                                            {sec.form_type}
                                                        </span>
                                                    </div>
                                                    <p className="mt-1.5 text-xs text-slate-500 leading-relaxed">
                                                        {sec.desc}
                                                    </p>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        </div>

                        {/* Langkah 2: Ambil Foto / Unggah */}
                        <div className="rounded-card border border-slate-200 bg-white p-5 sm:p-6 shadow-sm space-y-5">
                            <h2 className="text-sm font-bold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-3 flex items-center gap-2">
                                <span className="flex h-5 w-5 items-center justify-center rounded-full bg-teal-700 text-white text-[11px] font-bold">2</span>
                                Ambil Foto atau Unggah Formulir
                            </h2>

                            {/* Hidden File Inputs */}
                            {/* Input khusus Kamera HP (capture="environment") */}
                            <input
                                ref={cameraInputRef}
                                type="file"
                                accept="image/*"
                                capture="environment"
                                onChange={handleFileChange}
                                className="hidden"
                            />

                            {/* Input Berkas Biasa / Galeri */}
                            <input
                                ref={fileInputRef}
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                onChange={handleFileChange}
                                className="hidden"
                            />

                            {/* Area Aksi Tombol Potret / Unggah */}
                            {!previewUrl ? (
                                <div className="space-y-4">
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        {/* Tombol Kamera (Target Sentuh >= 56px) */}
                                        <button
                                            type="button"
                                            disabled={isLimitExceeded || isProcessingFile}
                                            onClick={() => cameraInputRef.current?.click()}
                                            className="min-h-[56px] flex items-center justify-center gap-3 rounded-xl bg-teal-700 px-4 py-3.5 text-base font-bold text-white shadow-sm hover:bg-teal-800 active:bg-teal-900 transition disabled:opacity-50"
                                        >
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" className="h-6 w-6">
                                                <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z" />
                                                <circle cx="12" cy="13" r="4" />
                                            </svg>
                                            <span>{lang.ocr.takePhoto}</span>
                                        </button>

                                        {/* Tombol Pilih File Galeri */}
                                        <button
                                            type="button"
                                            disabled={isLimitExceeded || isProcessingFile}
                                            onClick={() => fileInputRef.current?.click()}
                                            className="min-h-[56px] flex items-center justify-center gap-3 rounded-xl border-2 border-slate-300 bg-white px-4 py-3.5 text-base font-bold text-slate-800 shadow-sm hover:bg-slate-50 active:bg-slate-100 transition disabled:opacity-50"
                                        >
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="h-6 w-6 text-slate-500">
                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                                <polyline points="17 8 12 3 7 8" />
                                                <line x1="12" y1="3" x2="12" y2="15" />
                                            </svg>
                                            <span>{lang.ocr.uploadFile}</span>
                                        </button>
                                    </div>

                                    {isProcessingFile && (
                                        <div className="flex items-center justify-center gap-2 p-3 text-xs text-teal-800 font-semibold bg-teal-50 rounded-lg">
                                            <svg className="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                            </svg>
                                            <span>Mengoptimalkan ukuran foto...</span>
                                        </div>
                                    )}
                                </div>
                            ) : (
                                /* Pratinjau Foto yang Dipilih */
                                <div className="space-y-4">
                                    <div className="relative rounded-xl border border-slate-200 bg-slate-900/5 p-2 overflow-hidden flex flex-col items-center">
                                        <img
                                            src={previewUrl}
                                            alt="Pratinjau Formulir"
                                            className="max-h-[320px] rounded-lg object-contain"
                                        />
                                        <div className="mt-2 flex items-center justify-between w-full px-2 text-xs text-slate-600">
                                            <span>Ukuran file terkompresi: {(fileToUpload.size / 1024).toFixed(0)} KB</span>
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    setPreviewUrl(null);
                                                    setFileToUpload(null);
                                                }}
                                                className="font-bold text-rose-600 hover:underline"
                                            >
                                                Ganti Foto
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Tips Memotret */}
                            <div className="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-2">
                                <span className="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" className="h-4 w-4 text-teal-700">
                                        <circle cx="12" cy="12" r="10" />
                                        <line x1="12" y1="16" x2="12" y2="12" />
                                        <line x1="12" y1="8" x2="12.01" y2="8" />
                                    </svg>
                                    {lang.ocr.photoTipsTitle}
                                </span>
                                <ul className="text-xs text-slate-600 list-disc list-inside space-y-1">
                                    <li>{lang.ocr.tip1}</li>
                                    <li>{lang.ocr.tip2}</li>
                                    <li>{lang.ocr.tip3}</li>
                                    <li>{lang.ocr.tip4}</li>
                                </ul>
                            </div>

                            {/* Pesan Kesalahan */}
                            {errorMessage && (
                                <div className="rounded-lg bg-rose-50 border border-rose-200 p-3 text-xs font-semibold text-rose-800">
                                    {errorMessage}
                                </div>
                            )}

                            {/* Tombol Kirim Pemindaian */}
                            {previewUrl && (
                                <div className="pt-2">
                                    <button
                                        type="submit"
                                        disabled={isSubmitting || isLimitExceeded}
                                        className="w-full flex items-center justify-center gap-3 min-h-[56px] rounded-xl bg-teal-700 px-6 py-4 text-base font-bold text-white shadow-sm hover:bg-teal-800 active:bg-teal-900 transition disabled:opacity-50"
                                    >
                                        {isSubmitting ? (
                                            <>
                                                <svg className="animate-spin h-5 w-5 text-white" viewBox="0 0 24 24" fill="none">
                                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                                </svg>
                                                <span>Sedang Memproses Foto...</span>
                                            </>
                                        ) : (
                                            <>
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" className="h-5 w-5">
                                                    <polyline points="9 18 15 12 9 6" />
                                                </svg>
                                                <span>Mulai Ekstrak Data dengan AI</span>
                                            </>
                                        )}
                                    </button>
                                </div>
                            )}
                        </div>
                    </form>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
