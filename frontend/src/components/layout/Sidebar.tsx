import {
    LayoutDashboard,
    Boxes,
    ShieldAlert,
    Bug,
    ClipboardCheck,
    Users,
    Building2,
    LogOut,
} from "lucide-react";

import {
    NavLink,
    type NavLinkRenderProps,
} from "react-router-dom";

import SirisWordmark from "../SirisWordmark";

interface SidebarProps {
    isOpen: boolean;
    onClose: () => void;
    onLogout: () => void;
    loggingOut: boolean;
    logoutError: string;
}

interface MenuItem {
    label: string;
    path: string;
    icon: typeof LayoutDashboard;
}

const menuItems: MenuItem[] = [
    {
        label: "Dashboard",
        path: "/admin/dashboard",
        icon: LayoutDashboard,
    },
    {
        label: "Assets",
        path: "/admin/assets",
        icon: Boxes,
    },
    {
        label: "Threats",
        path: "/admin/threats",
        icon: ShieldAlert,
    },
    {
        label: "Vulnerabilities",
        path: "/admin/vulnerabilities",
        icon: Bug,
    },
    {
        label: "Risk Assessment",
        path: "/admin/risk-assessments",
        icon: ClipboardCheck,
    },
    {
        label: "Users",
        path: "/admin/users",
        icon: Users,
    },
    {
        label: "Organisasi",
        path: "/admin/organisasi",
        icon: Building2,
    },
];

const Sidebar = ({
    isOpen,
    onClose,
    onLogout,
    loggingOut,
    logoutError,
}: SidebarProps) => {
    return (
        <aside
            className={`sidebar ${
                isOpen
                    ? "sidebar-open"
                    : "sidebar-closed"
            }`}
        >
            <div className="sidebar-logo">
                <img className="kemhan-logo" src="/kemhan-logo.png" alt="Logo Kementerian Pertahanan Republik Indonesia" />

                {isOpen && (
                    <div className="logo-text">
                        <h2><SirisWordmark /></h2>
                        <span>
                            Risk Information System
                        </span>
                    </div>
                )}
            </div>

            <nav className="sidebar-nav">
                {menuItems.map((item) => {
                    const Icon = item.icon;

                    return (
                        <NavLink
                            key={item.path}
                            to={item.path}
                            onClick={onClose}
                            className={({
                                isActive,
                            }: NavLinkRenderProps) =>
                                `sidebar-link ${
                                    isActive
                                        ? "active"
                                        : ""
                                }`
                            }
                        >
                            <Icon size={20} />

                            {isOpen && (
                                <span>
                                    {item.label}
                                </span>
                            )}
                        </NavLink>
                    );
                })}
            </nav>

            {isOpen && (
                <div className="sidebar-footer">
                    <div className="sidebar-footer-row">
                        <div className="sidebar-footer-brand"><strong><SirisWordmark /></strong><small>Admin Panel v1.0</small></div>
                        <button type="button" className="sidebar-logout" disabled={loggingOut} onClick={onLogout}>
                            <LogOut size={14} aria-hidden="true" />{loggingOut ? "Keluar..." : "Keluar"}
                        </button>
                    </div>
                    {logoutError && <p className="sidebar-logout-error" role="alert">{logoutError}</p>}
                </div>
            )}
        </aside>
    );
};

export default Sidebar;
