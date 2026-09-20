import { useState, type ReactNode } from "react";
import { Menu, X } from "lucide-react";
import Sidebar from "./Sidebar";
import SirisWordmark from "../SirisWordmark";
import { useNavigate } from "react-router-dom";
import api, { getApiError } from "../../api/axios";

interface AdminLayoutProps {
    children: ReactNode;
}

const AdminLayout = ({ children }: AdminLayoutProps) => {
    const [sidebarOpen, setSidebarOpen] = useState(() => window.innerWidth > 800);
    const [loggingOut, setLoggingOut] = useState(false);
    const [logoutError, setLogoutError] = useState("");
    const navigate = useNavigate();

    async function logout() {
        setLoggingOut(true);
        setLogoutError("");
        try {
            await api.post("/logout");
            localStorage.removeItem("token");
            navigate("/login", { replace: true });
        } catch (error) {
            setLogoutError(getApiError(error));
        } finally {
            setLoggingOut(false);
        }
    }

    return (
        <div className="admin-layout">
            {sidebarOpen && <button className="sidebar-backdrop" aria-label="Tutup menu" onClick={() => setSidebarOpen(false)} />}
            <Sidebar
                isOpen={sidebarOpen}
                onLogout={logout}
                loggingOut={loggingOut}
                logoutError={logoutError}
                onClose={() => { if (window.innerWidth <= 800) setSidebarOpen(false); }}
            />

            <main
                className={`admin-main ${
                    sidebarOpen ? "sidebar-active" : ""
                }`}
            >
                <header className="admin-header">
                    <button
                        type="button"
                        className="menu-button"
                        onClick={() =>
                            setSidebarOpen((prev) => !prev)
                        }
                        aria-label={
                            sidebarOpen
                                ? "Tutup sidebar"
                                : "Buka sidebar"
                        }
                    >
                        {sidebarOpen ? (
                            <X size={22} />
                        ) : (
                            <Menu size={22} />
                        )}
                    </button>

                    <div className="header-title">
                        <strong><SirisWordmark /></strong>
                        <span>Risk Information System</span>
                    </div>

                    <div className="header-user">
                        <div className="user-avatar">
                            A
                        </div>

                        <div className="user-info">
                            <strong>Administrator</strong>
                            <span>Admin</span>
                        </div>
                    </div>
                </header>

                <section className="admin-content">
                    {children}
                </section>
            </main>
        </div>
    );
};

export default AdminLayout;
