import { useEffect, useRef, useState } from "react";
import { Trash2 } from "lucide-react";
import api, { getApiError } from "../api/axios";

export default function AdminDeleteButton({ endpoint, label, onDeleted }: { endpoint: string; label: string; onDeleted: () => void }) {
    const dialog = useRef<HTMLDialogElement>(null);
    const [open, setOpen] = useState(false);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState("");
    useEffect(() => { if (open) dialog.current?.showModal(); }, [open]);
    async function remove() {
        setBusy(true); setError("");
        try { await api.delete(endpoint); setOpen(false); onDeleted(); }
        catch (err) { setError(getApiError(err)); }
        finally { setBusy(false); }
    }
    return <><button className="asset-edit" aria-label={`Hapus ${label}`} onClick={() => { setError(""); setOpen(true); }}><Trash2 size={15} />Hapus</button>
        {open && <dialog ref={dialog} className="asset-dialog" aria-label="Konfirmasi hapus" onCancel={(event) => { event.preventDefault(); if (!busy) setOpen(false); }}>
            <header><h2>Hapus data?</h2></header>
            <div style={{ padding: 24, whiteSpace: "normal" }}><p>Hapus <strong>{label}</strong>? Data yang dihapus tidak dapat dikembalikan.</p>{error && <p role="alert" style={{ color: "#b91c1c", marginTop: 16 }}>{error}</p>}</div>
            <footer><button className="asset-edit" disabled={busy} onClick={() => setOpen(false)}>Batal</button><button className="asset-primary" disabled={busy} onClick={remove}>{busy ? "Menghapus..." : "Ya, hapus"}</button></footer>
        </dialog>}
    </>;
}
