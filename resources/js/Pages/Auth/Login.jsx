import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { lang } from '@/lang';
import { Head, useForm } from '@inertiajs/react';

export default function Login({ status }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: true,
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    const fillDemo = (role) => {
        if (role === 'petugas') {
            setData({
                email: 'petugas@sipela.test',
                password: 'password',
                remember: true,
            });
        } else {
            setData({
                email: 'admin@sipela.test',
                password: 'password',
                remember: true,
            });
        }
    };

    return (
        <GuestLayout>
            <Head title={lang.auth.loginTitle} />

            <div className="mb-6">
                <h1 className="text-xl font-bold text-slate-900">
                    {lang.auth.loginTitle}
                </h1>
                <p className="mt-1 text-sm text-slate-500">
                    Masukkan akun untuk mengakses pencatatan dan pelaporan lansia.
                </p>
            </div>

            {status && (
                <div className="mb-4 rounded-lg bg-green-50 p-3 text-sm font-medium text-green-700 border border-green-200">
                    {status}
                </div>
            )}

            <form onSubmit={submit} className="space-y-5">
                <div>
                    <InputLabel
                        htmlFor="email"
                        value={lang.auth.email}
                        className="text-base font-semibold text-slate-800"
                    />

                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        placeholder={lang.auth.emailPlaceholder}
                        className="mt-1 block w-full min-h-[48px] text-base rounded-lg border-slate-300 focus:border-teal-600 focus:ring-teal-600"
                        autoComplete="username"
                        isFocused={true}
                        onChange={(e) => setData('email', e.target.value)}
                    />

                    <InputError message={errors.email} className="mt-2 text-sm" />
                </div>

                <div>
                    <InputLabel
                        htmlFor="password"
                        value={lang.auth.password}
                        className="text-base font-semibold text-slate-800"
                    />

                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        placeholder={lang.auth.passwordPlaceholder}
                        className="mt-1 block w-full min-h-[48px] text-base rounded-lg border-slate-300 focus:border-teal-600 focus:ring-teal-600"
                        autoComplete="current-password"
                        onChange={(e) => setData('password', e.target.value)}
                    />

                    <InputError message={errors.password} className="mt-2 text-sm" />
                </div>

                <div className="flex items-center">
                    <label className="flex items-center cursor-pointer select-none py-1">
                        <Checkbox
                            name="remember"
                            checked={data.remember}
                            onChange={(e) =>
                                setData('remember', e.target.checked)
                            }
                            className="h-5 w-5 rounded border-slate-300 text-teal-700 focus:ring-teal-600"
                        />
                        <span className="ms-3 text-sm font-medium text-slate-700">
                            {lang.auth.rememberMe}
                        </span>
                    </label>
                </div>

                <div className="pt-2">
                    <button
                        type="submit"
                        disabled={processing}
                        className="flex w-full min-h-[48px] items-center justify-center rounded-lg bg-teal-700 px-4 py-3 text-base font-bold text-white shadow-sm transition hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2 active:bg-teal-900 disabled:opacity-50"
                    >
                        {processing ? lang.auth.loggingIn : lang.auth.loginButton}
                    </button>
                </div>
            </form>

            <div className="mt-6 border-t border-slate-200 pt-4">
                <p className="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                    Akun Cepat (Testing / Demo):
                </p>
                <div className="grid grid-cols-2 gap-2">
                    <button
                        type="button"
                        onClick={() => fillDemo('petugas')}
                        className="min-h-[40px] px-3 py-2 text-xs font-medium rounded-lg border border-teal-200 bg-teal-50 text-teal-800 hover:bg-teal-100 transition"
                    >
                        Petugas Lansia
                    </button>
                    <button
                        type="button"
                        onClick={() => fillDemo('admin')}
                        className="min-h-[40px] px-3 py-2 text-xs font-medium rounded-lg border border-slate-200 bg-slate-100 text-slate-800 hover:bg-slate-200 transition"
                    >
                        Administrator
                    </button>
                </div>
            </div>
        </GuestLayout>
    );
}
