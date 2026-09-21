import { Link } from "react-router-dom";
import { useCurrentUser, roleHome } from "../../routes/RoleGate";
export default function Account() { const user = useCurrentUser(); return <main style={{ padding: 32 }}><h1>{user.name}</h1><p>Peran: {user.role.name}</p><p>Modul monitoring untuk peran ini belum tersedia.</p>{roleHome(user.role.name) !== "/account" && <Link to={roleHome(user.role.name)}>Buka portal</Link>}<p><Link to="/login">Kembali ke login</Link></p></main>; }
