import {
    BrowserRouter,
    Navigate,
    Route,
    Routes,
} from "react-router-dom";

import SatkerRoutes from "./routes/SatkerRoutes";
import RoleGate from "./routes/RoleGate";
import ReviewPortal from "./pages/satker/ReviewPortal";
import Account from "./pages/satker/Account";
import AdminLayout from "./components/layout/AdminLayout";
import Dashboard from "./pages/admin/Dashboard";
import Organizations from "./pages/admin/Organizations";
import Users from "./pages/admin/Users";
import Login from "./pages/Login";
import Assets from "./pages/admin/Assets";
import Threats from "./pages/admin/Threats";
import Vulnerabilities from "./pages/admin/Vulnerabilities";
import RiskAssessments from "./pages/admin/RiskAssessments";

function App() {
    return (
        <BrowserRouter>
            <Routes>
                <Route path="/satker/*" element={<SatkerRoutes />} />
                <Route path="/kasub/pengajuan" element={<RoleGate role="kasub"><ReviewPortal /></RoleGate>} />
                <Route path="/account" element={<RoleGate><Account /></RoleGate>} />
                <Route path="/admin/organisasi" element={localStorage.getItem("token") ? <AdminLayout><Organizations /></AdminLayout> : <Navigate to="/login" replace />} />
                <Route path="/admin/users" element={localStorage.getItem("token") ? <AdminLayout><Users /></AdminLayout> : <Navigate to="/login" replace />} />
                <Route path="/login" element={<Login />} />
                <Route path="/admin/risk-assessments" element={localStorage.getItem("token") ? <AdminLayout><RiskAssessments /></AdminLayout> : <Navigate to="/login" replace />} />
                <Route path="/admin/vulnerabilities" element={localStorage.getItem("token") ? <AdminLayout><Vulnerabilities /></AdminLayout> : <Navigate to="/login" replace />} />
                <Route path="/admin/threats" element={localStorage.getItem("token") ? <AdminLayout><Threats /></AdminLayout> : <Navigate to="/login" replace />} />
                <Route path="/admin/assets" element={localStorage.getItem("token") ? <AdminLayout><Assets /></AdminLayout> : <Navigate to="/login" replace />} />
                <Route
                    path="/"
                    element={
                        <Navigate
                            to="/login"
                            replace
                        />
                    }
                />

                <Route
                    path="/admin/dashboard"
                    element={
                        localStorage.getItem("token") ? <AdminLayout>
                            <Dashboard />
                        </AdminLayout> : <Navigate to="/login" replace />
                    }
                />

                <Route
                    path="*"
                    element={
                        <Navigate
                            to="/login"
                            replace
                        />
                    }
                />
            </Routes>
        </BrowserRouter>
    );
}

export default App;
