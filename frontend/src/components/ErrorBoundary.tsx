import { Component, type ReactNode } from "react";

export default class ErrorBoundary extends Component<{ children: ReactNode }, { failed: boolean }> {
    state = { failed: false };

    static getDerivedStateFromError() {
        return { failed: true };
    }

    render() {
        if (this.state.failed) {
            return (
                <main className="login-page">
                    <section className="login-card" role="alert">
                        <h1>Tampilan gagal dimuat</h1>
                        <p>Terjadi kesalahan saat menampilkan aplikasi. Muat ulang halaman untuk mencoba kembali.</p>
                        <button className="refresh-button" onClick={() => window.location.reload()}>Muat ulang</button>
                    </section>
                </main>
            );
        }
        return this.props.children;
    }
}
