/**
 * Format angka dan persentase standar Indonesia
 */

export function formatNumber(num) {
    if (num === null || num === undefined || num === '') {
        return '—';
    }
    const val = Number(num);
    if (isNaN(val)) return '—';
    return new Intl.NumberFormat('id-ID').format(val);
}

export function formatPercentage(pct, withSymbol = true) {
    if (pct === null || pct === undefined || isNaN(pct)) {
        return withSymbol ? '0,00 %' : '0,00';
    }
    const val = Number(pct);
    const formatted = val.toLocaleString('id-ID', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
    return withSymbol ? `${formatted} %` : formatted;
}

export const MONTH_NAMES = [
    '',
    'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember',
];

export function getMonthName(monthNumber) {
    return MONTH_NAMES[monthNumber] || '';
}
