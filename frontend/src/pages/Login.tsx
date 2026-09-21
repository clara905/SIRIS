import { useCallback, useEffect, useState, type FormEvent } from "react";
import { useNavigate } from "react-router-dom";
import api, { getApiError } from "../api/axios";
import { ArrowRight, Eye, EyeOff, LockKeyhole, Mail, RefreshCw, ShieldCheck } from "lucide-react";
import "./Login.css";
import { roleHome } from "../routes/RoleGate";
import SirisWordmark from "../components/SirisWordmark";

interface CaptchaChallenge {
    id: string;
    image: string;
    expires_in: number;
}

export default function Login() {
    const navigate = useNavigate();
    const [email, setEmail] = useState("");
    const [password, setPassword] = useState("");
    const [showPassword, setShowPassword] = useState(false);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState("");
    const [captcha, setCaptcha] = useState<CaptchaChallenge | null>(null);
    const [captchaAnswer, setCaptchaAnswer] = useState("");
    const [captchaLoading, setCaptchaLoading] = useState(true);
    const [captchaError, setCaptchaError] = useState("");

    const loadCaptcha = useCallback(async (signal?: AbortSignal) => {
        setCaptchaLoading(true);
        setCaptcha(null);
        setCaptchaAnswer("");
        setCaptchaError("");
        try {
            const response = await api.get<{ data: CaptchaChallenge }>("/captcha", { signal });
            if (!signal?.aborted) setCaptcha(response.data.data);
        } catch (error) {
            if (!signal?.aborted) setCaptchaError(getApiError(error));
        } finally {
            if (!signal?.aborted) setCaptchaLoading(false);
        }
    }, []);

    useEffect(() => {
        const controller = new AbortController();
        void loadCaptcha(controller.signal);
        return () => controller.abort();
    }, [loadCaptcha]);



    async function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        if (!captcha || captchaLoading || loading) return;
        setLoading(true);
        setError("");
        try {
            const response = await api.post<{ data: { token: string; user: { role: { name: string } } } }>("/login", {
                email, password, captcha_id: captcha.id, captcha_answer: captchaAnswer,
            });
            localStorage.setItem("token", response.data.data.token);
            navigate(roleHome(response.data.data.user.role?.name), { replace: true });
        } catch (error) {
            setError(getApiError(error));
            await loadCaptcha();
        } finally {
            setLoading(false);
        }
    }

    return (
        <main className="siris-login">
            <div className="login-shell">
            <section className="login-identity" aria-labelledby="identity-title">
                <div className="login-institution">
                    <img src="/kemhan-logo.png" alt="Logo Kementerian Pertahanan Republik Indonesia" width={96} height={96} />
                    <div>KEMENTERIAN PERTAHANAN<span>REPUBLIK INDONESIA</span></div>
                </div>
                <div className="login-intro">
                    <span className="login-eyebrow">SISTEM INFORMASI RISIKO</span>
                    <h1 id="identity-title"><strong className="login-wordmark"><SirisWordmark /></strong><span>Kelola risiko.<br />Lindungi informasi.</span></h1>
                    <p>Pusat pemantauan dan pengelolaan risiko keamanan informasi dalam satu sistem yang terintegrasi.</p>
                    <div className="login-identity-rule" />
                    <div className="login-purpose"><ShieldCheck size={22} aria-hidden="true" /><span>Identifikasi, penilaian, dan pemantauan risiko.</span></div>
                </div>
                <p className="login-identity-footer">Kementerian Pertahanan Republik Indonesia</p>
            </section>
            <section className="login-form-panel" aria-labelledby="login-title">
            <form className="login-form" onSubmit={submit}>
                <header className="login-form-heading">
                    <span className="login-portal-label"><LockKeyhole size={14} aria-hidden="true" /> PORTAL ADMINISTRATOR</span>
                    <h2 id="login-title">Selamat datang kembali</h2>
                    <p>Masuk ke akun Anda untuk mengakses dashboard <SirisWordmark />.</p>
                </header>
                <div className="login-field">
                <label htmlFor="email">Email</label>
                <div className="login-input-wrap"><Mail size={18} aria-hidden="true" />
                <input id="email" type="email" autoComplete="username" placeholder="Masukkan alamat email" required value={email} onChange={(event) => setEmail(event.target.value)} />
                </div></div>
                <div className="login-field">
                <label htmlFor="password">Password</label>
                <div className="login-input-wrap"><LockKeyhole size={18} aria-hidden="true" />
                <input id="password" type={showPassword ? "text" : "password"} autoComplete="current-password" placeholder="Masukkan password" required value={password} onChange={(event) => setPassword(event.target.value)} />
                <button type="button" className="login-password-toggle" onClick={() => setShowPassword(!showPassword)} aria-label={showPassword ? "Sembunyikan password" : "Tampilkan password"} aria-pressed={showPassword}>
                    {showPassword ? <EyeOff size={18} /> : <Eye size={18} />}
                </button></div></div>
                <div className="login-field login-captcha-field">
                <label htmlFor="captcha">Kode CAPTCHA</label>
                <div className="login-captcha-challenge" aria-busy={captchaLoading}>
                    {captchaLoading ? <span role="status">Memuat CAPTCHA...</span> : captcha &&
                        <img src={captcha.image} alt="Kode CAPTCHA enam karakter; masukkan kode yang terlihat" width={264} height={80} />}
                    <button type="button" className="login-captcha-refresh" onClick={() => void loadCaptcha()} disabled={captchaLoading || loading}><RefreshCw size={16} aria-hidden="true" />Ganti kode</button>
                </div>
                {captchaError && <p className="login-alert" role="alert">{captchaError}</p>}
                <input className="login-captcha-input" id="captcha" autoComplete="off" spellCheck={false} required minLength={6} maxLength={6}
                    placeholder="Masukkan 6 karakter" value={captchaAnswer} disabled={loading || captchaLoading || !captcha}
                    onChange={(event) => setCaptchaAnswer(event.target.value.toUpperCase())} aria-describedby="captcha-help" />
                <small id="captcha-help">Kode berlaku 5 menit. Huruf besar dan kecil dianggap sama.</small>
                </div>
                {error && <p className="login-alert" role="alert">{error}</p>}
                <button className="login-submit" type="submit" disabled={loading || captchaLoading || !captcha}>{loading ? "Memproses..." : "Masuk ke dashboard"}<ArrowRight size={18} aria-hidden="true" /></button>
                <p className="login-access-note"><ShieldCheck size={16} aria-hidden="true" />Akses khusus pengguna yang berwenang.</p>
            </form>
            </section>
            </div>
            <footer className="login-page-footer"><SirisWordmark /> <span aria-hidden="true">·</span> Sistem Informasi Risiko</footer>
        </main>
    );
}
