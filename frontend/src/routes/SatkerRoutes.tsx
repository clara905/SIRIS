import { Navigate, Route, Routes } from "react-router-dom";
import RoleGate from "./RoleGate";
import SatkerLayout from "../layouts/SatkerLayout";
import Dashboard from "../pages/satker/Dashboard";
import Assets from "../pages/satker/Assets";
import AssetDetail from "../pages/satker/AssetDetail";
import CreateAsset from "../pages/satker/CreateAsset";
import Pengajuan, { PengajuanDetail } from "../pages/satker/Pengajuan";
import CreatePengajuan from "../pages/satker/CreatePengajuan";
import RiskAssessments, { RiskAssessmentDetail } from "../pages/satker/RiskAssessments";
import Notifications from "../pages/satker/Notifications";
import Profile from "../pages/satker/Profile";
export default function SatkerRoutes() { return <RoleGate role="satker"><Routes><Route element={<SatkerLayout />}><Route index element={<Navigate to="dashboard" replace />} /><Route path="dashboard" element={<Dashboard />} /><Route path="assets" element={<Assets />} /><Route path="assets/create" element={<CreateAsset />} /><Route path="assets/:id" element={<AssetDetail />} /><Route path="assets/:id/edit" element={<CreateAsset />} /><Route path="pengajuan" element={<Pengajuan />} /><Route path="pengajuan/create" element={<CreatePengajuan />} /><Route path="pengajuan/:id" element={<PengajuanDetail />} /><Route path="pengajuan/:id/edit" element={<CreatePengajuan />} /><Route path="history" element={<Pengajuan history />} /><Route path="risk-assessments" element={<RiskAssessments />} /><Route path="risk-assessments/:id" element={<RiskAssessmentDetail />} /><Route path="notifications" element={<Notifications />} /><Route path="profile" element={<Profile />} /><Route path="*" element={<Navigate to="/satker/dashboard" replace />} /></Route></Routes></RoleGate>; }
