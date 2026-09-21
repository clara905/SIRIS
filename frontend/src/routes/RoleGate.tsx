import { createContext, useContext, useEffect, useState, type ReactNode } from "react";
import { Navigate } from "react-router-dom";
import api, { getApiError } from "../api/axios";
import type { Person } from "../api/satker/portal";
const UserContext = createContext<Person | null>(null);
export function useCurrentUser() { const user = useContext(UserContext); if (!user) throw new Error("Sesi pengguna belum tersedia"); return user; }
export function roleHome(role?: string) { return role === "admin" ? "/admin/dashboard" : role === "satker" ? "/satker/dashboard" : role === "kasub" ? "/kasub/pengajuan" : "/account"; }
export default function RoleGate({ role, children }: { role?: string; children: ReactNode }) {
    const [user, setUser] = useState<Person | null>(null);
    const [error, setError] = useState("");
    const [revision, setRevision] = useState(0);
    useEffect(() => { if (!localStorage.getItem("token")) return; const controller = new AbortController(); setError(""); api.get<{ data: Person }>("/me", { signal: controller.signal }).then((response) => { if (!controller.signal.aborted) setUser(response.data.data); }).catch((err) => { if (!controller.signal.aborted) setError(getApiError(err)); }); return () => controller.abort(); }, [revision]);
    if (!localStorage.getItem("token")) return <Navigate to="/login" replace />;
    if (error) return <div role="alert" style={{ padding: 32 }}><p>{error}</p><button onClick={() => setRevision((v) => v + 1)}>Coba lagi</button></div>;
    if (!user) return <p role="status" style={{ padding: 32 }}>Memuat sesi pengguna...</p>;
    if (role && user.role.name !== role) return <Navigate to={roleHome(user.role.name)} replace />;
    return <UserContext.Provider value={user}>{children}</UserContext.Provider>;
}
