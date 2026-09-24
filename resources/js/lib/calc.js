/**
 * resources/js/lib/calc.js
 * Cermin perhitungan rumus turunan LansiaCalculator untuk pratinjau instan di frontend.
 * Sumber kebenaran mutlak tetap berada di app/Services/LansiaCalculator.php.
 */

export function calculateKunjunganRow(values = {}) {
    const get = (key) => {
        const val = values[key];
        if (val === null || val === undefined || val === '') return 0;
        const n = parseInt(val, 10);
        return isNaN(n) ? 0 : n;
    };

    // 1. total_lansia_60
    const totalLansia60 =
        get('sas_a6069_l') +
        get('sas_a6069_p') +
        get('sas_a70_l') +
        get('sas_a70_p');

    // 2. total visit & std
    const ages = ['a4559', 'a6069', 'a70'];
    const genders = ['l', 'p'];
    const types = ['lama', 'baru'];

    const totalVisit = {};
    const stdBaru = {};
    const stdAbs = {};

    ages.forEach((age) => {
        totalVisit[age] = {};
        stdBaru[age] = {};
        stdAbs[age] = {};

        genders.forEach((sex) => {
            totalVisit[age][sex] = {};
            types.forEach((t) => {
                const inV = get(`in_${age}_${sex}_${t}`);
                const outV = get(`out_${age}_${sex}_${t}`);
                totalVisit[age][sex][t] = inV + outV;
            });

            stdBaru[age][sex] = totalVisit[age][sex]['baru'];
            stdAbs[age][sex] = totalVisit[age][sex]['lama'] + totalVisit[age][sex]['baru'];
        });
    });

    // 3. spm_l_abs & spm_p_abs
    const spmLabs = stdAbs['a6069']['l'] + stdAbs['a70']['l'];
    const spmPabs = stdAbs['a6069']['p'] + stdAbs['a70']['p'];

    // 4. spm_total
    const spmTotal = spmLabs + spmPabs;

    // 5. spm_pct
    const spmPct = totalLansia60 > 0
        ? Math.round((spmTotal / totalLansia60) * 10000) / 100
        : 0;

    // 6. In & Out sums
    let totalIn = 0;
    let totalOut = 0;

    ages.forEach((age) => {
        genders.forEach((sex) => {
            types.forEach((t) => {
                totalIn += get(`in_${age}_${sex}_${t}`);
                totalOut += get(`out_${age}_${sex}_${t}`);
            });
        });
    });

    const totalKunjunganBulan = totalIn + totalOut;

    // 7. Kemandirian
    const mandiri6069Total = get('mandiri_6069_a') + get('mandiri_6069_b') + get('mandiri_6069_c');
    const mandiri70Total = get('mandiri_70_a') + get('mandiri_70_b') + get('mandiri_70_c');
    const mandiriTotal = mandiri6069Total + mandiri70Total;

    return {
        total_lansia_60: totalLansia60,
        spm_l_abs: spmLabs,
        spm_p_abs: spmPabs,
        spm_total: spmTotal,
        spm_pct: spmPct,
        total_in: totalIn,
        total_out: totalOut,
        total_kunjungan_bulan: totalKunjunganBulan,
        mandiri_total: mandiriTotal,
    };
}

export function calculateLayananRow(values = {}) {
    const get = (key) => {
        const val = values[key];
        if (val === null || val === undefined || val === '') return 0;
        const n = parseInt(val, 10);
        return isNaN(n) ? 0 : n;
    };

    const kelainanKeys = [
        'gg_me', 'imt_lebih', 'imt_kurang', 'td_tinggi', 'td_rendah',
        'hb_kurang', 'kolesterol', 'dm', 'asam_urat', 'ginjal',
        'kognitif', 'penglihatan', 'pendengaran', 'lainnya'
    ];

    let kelainanTotalL = 0;
    let kelainanTotalP = 0;

    kelainanKeys.forEach((key) => {
        kelainanTotalL += get(`kel_${key}_l`);
        kelainanTotalP += get(`kel_${key}_p`);
    });

    const tindakanCols = [
        'pengobatan_edukasi', 'pengobatan_obati', 'pengobatan_rujuk',
        'konseling_baru', 'konseling_lama', 'konseling_selesai', 'penyuluhan'
    ];

    let tindakanTotal = 0;
    tindakanCols.forEach((col) => {
        tindakanTotal += get(col);
    });

    return {
        kelainan_total_l: kelainanTotalL,
        kelainan_total_p: kelainanTotalP,
        kelainan_total: kelainanTotalL + kelainanTotalP,
        tindakan_total: tindakanTotal,
    };
}
