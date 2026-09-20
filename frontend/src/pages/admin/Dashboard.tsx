import { useEffect, useMemo, useState } from "react";
import {
    Boxes,
    ShieldAlert,
    Bug,
    ClipboardCheck,
    RefreshCw,
    Search,
} from "lucide-react";

import {
    Cell,
    Pie,
    PieChart,
    ResponsiveContainer,
    Tooltip,
    Legend,
    BarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid,
} from "recharts";

import { getAdminDashboard } from "../../api/dashboardApi";
import { getApiError } from "../../api/axios";
import "./Dashboard.css";
import SirisWordmark from "../../components/SirisWordmark";

import type {
    DashboardData,
    RecentRisk,
} from "../../types/dashboard";

const Dashboard = () => {
    const [dashboard, setDashboard] =
        useState<DashboardData | null>(null);

    const [loading, setLoading] =
        useState(true);

    const [error, setError] =
        useState("");
    const [updatedAt, setUpdatedAt] = useState<Date | null>(null);

    const [search, setSearch] =
        useState("");

    const [riskFilter, setRiskFilter] =
        useState("semua");

    const loadDashboard = async () => {
        try {
            setLoading(true);
            setError("");

            const response =
                await getAdminDashboard();

            setDashboard(response.data);
            setUpdatedAt(new Date());
        } catch (err) {
            setError(getApiError(err));
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        loadDashboard();
    }, []);

    const riskChartData = useMemo(() => {
        if (!dashboard) {
            return [];
        }

        return [
            {
                name: "Rendah",
                value: dashboard.risk_levels.rendah,
            },
            {
                name: "Sedang",
                value: dashboard.risk_levels.sedang,
            },
            {
                name: "Tinggi",
                value: dashboard.risk_levels.tinggi +
                    (dashboard.risk_levels.sangat_tinggi ?? 0) +
                    (dashboard.risk_levels.ekstrem ?? 0),
            },
        ];
    }, [dashboard]);

    const treatmentChartData = useMemo(() => {
        if (!dashboard) {
            return [];
        }

        return [
            {
                name: "Belum",
                total:
                    dashboard.treatment_status
                        .belum,
            },
            {
                name: "Proses",
                total:
                    dashboard.treatment_status
                        .proses,
            },
            {
                name: "Selesai",
                total:
                    dashboard.treatment_status
                        .selesai,
            },
        ];
    }, [dashboard]);

    const filteredRisks = useMemo(() => {
        if (!dashboard) {
            return [];
        }

        return dashboard.recent_risks.filter(
            (risk: RecentRisk) => {
                const keyword =
                    search.toLowerCase();

                const assetName =
                    risk.asset?.nama
                        ?.toLowerCase() ?? "";

                const threatName =
                    risk.threat?.nama_ancaman
                        ?.toLowerCase() ?? "";

                const matchesSearch =
                    assetName.includes(keyword) ||
                    threatName.includes(keyword);

                const matchesFilter =
                    riskFilter === "semua" ||
                    risk.level.toLowerCase() ===
                        riskFilter;

                return (
                    matchesSearch &&
                    matchesFilter
                );
            }
        );
    }, [
        dashboard,
        search,
        riskFilter,
    ]);

    if (loading && !dashboard) {
        return (
            <div className="dashboard">
                <div className="page-header">
                    <div>
                        <h1>Dashboard</h1>
                        <p>
                            Memuat data dashboard...
                        </p>
                    </div>
                </div>

                <div className="dashboard-loading">
                    <span>Memuat data <SirisWordmark />...</span>
                </div>
            </div>
        );
    }

    if (error && !dashboard) {
        return (
            <div className="dashboard">
                <div className="page-header">
                    <div>
                        <h1>Dashboard</h1>
                        <p>
                            Ringkasan kondisi risiko
                            keamanan informasi.
                        </p>
                    </div>

                    <button
                        className="refresh-button"
                        onClick={loadDashboard}
                    >
                        <RefreshCw size={16} />
                        Coba Lagi
                    </button>
                </div>

                <div className="dashboard-error">
                    <strong>
                        Terjadi kesalahan
                    </strong>

                    <p>{error}</p>
                </div>
            </div>
        );
    }

    if (!dashboard) {
        return null;
    }

    return (
        <div className="dashboard">
            <div className="page-header">
                <div>
                    <span className="dashboard-eyebrow">PUSAT PEMANTAUAN RISIKO</span>
                    <h1>Dashboard</h1>

                    <p>
                        Ringkasan kondisi risiko
                        keamanan informasi Kementerian Pertahanan.
                    </p>
                </div>

                <button
                    className="refresh-button"
                    onClick={loadDashboard}
                    disabled={loading}
                >
                    <RefreshCw size={16} className={loading ? "dashboard-spinning" : ""} />
                    {loading ? "Memperbarui..." : "Perbarui data"}
                </button>
            </div>

            {error && <div className="dashboard-error" role="alert"><strong>Data belum berhasil diperbarui</strong><p>{error} Data terakhir tetap ditampilkan.</p></div>}
            <section className="dashboard-overview" aria-label="Ringkasan pemantauan">
                <div className="overview-intro">
                    <span className="overview-label"><ShieldAlert size={16} /> RINGKASAN KEAMANAN INFORMASI</span>
                    <h2>Pantau risiko, tentukan prioritas.</h2>
                    <p>Gambaran aset dan hasil penilaian untuk mendukung tindak lanjut risiko.</p>
                    <span className="overview-updated">{updatedAt && `Diperbarui ${updatedAt.toLocaleString("id-ID", { day: "numeric", month: "short", hour: "2-digit", minute: "2-digit" })}`}</span>
                </div>
                <div className="overview-priority"><span>Risiko kategori tinggi</span><strong>{riskChartData[2].value.toLocaleString("id-ID")}</strong><small>Termasuk sangat tinggi dan ekstrem</small></div>
            </section>

            {/* STATISTICS */}

            <div className="stats-grid">
                <div className="stat-card">
                    <div className="stat-icon">
                        <Boxes size={24} />
                    </div>

                    <div>
                        <span>Total Aset</span>

                        <h2>
                            {
                                dashboard.summary
                                    .total_assets
                            }
                        </h2>

                        <small>
                            Aset terdaftar
                        </small>
                    </div>
                </div>

                <div className="stat-card">
                    <div className="stat-icon">
                        <ShieldAlert
                            size={24}
                        />
                    </div>

                    <div>
                        <span>Total Ancaman</span>

                        <h2>
                            {
                                dashboard.summary
                                    .total_threats
                            }
                        </h2>

                        <small>
                            Ancaman terdaftar
                        </small>
                    </div>
                </div>

                <div className="stat-card">
                    <div className="stat-icon">
                        <Bug size={24} />
                    </div>

                    <div>
                        <span>
                            Kerentanan
                        </span>

                        <h2>
                            {
                                dashboard.summary
                                    .total_vulnerabilities
                            }
                        </h2>

                        <small>
                            Kerentanan terdaftar
                        </small>
                    </div>
                </div>

                <div className="stat-card">
                    <div className="stat-icon">
                        <ClipboardCheck
                            size={24}
                        />
                    </div>

                    <div>
                        <span>Total Risiko</span>

                        <h2>
                            {
                                dashboard.summary
                                    .total_risks
                            }
                        </h2>

                        <small>
                            Penilaian risiko
                        </small>
                    </div>
                </div>
            </div>

            {/* CHARTS */}

            <div className="dashboard-grid">
                <div className="dashboard-panel">
                    <div className="panel-header">
                        <div>
                            <h3>
                                Distribusi Risiko
                            </h3>

                            <p>
                                Distribusi tingkat
                                risiko.
                            </p>
                        </div>
                    </div>

                    <div className="chart-container">
                        {riskChartData.every((item) => item.value === 0) ? <div className="dashboard-chart-empty"><ShieldAlert size={30} /><strong>Belum ada penilaian risiko</strong><span>Distribusi akan tampil setelah data penilaian tersedia.</span></div> : <>
                        <ResponsiveContainer
                            width="100%"
                            height={300}
                        >
                            <PieChart>
                                <Pie
                                    data={
                                        riskChartData
                                    }
                                    dataKey="value"
                                    nameKey="name"
                                    cx="50%"
                                    cy="50%"
                                    outerRadius={100}
                                    innerRadius={72}
                                    paddingAngle={3}
                                >
                                    {riskChartData.map(
                                        (
                                            _entry,
                                            index
                                        ) => (
                                            <Cell
                                                key={`cell-${index}`}
                                                fill={["#16a34a", "#eab308", "#dc2626"][index]}
                                            />
                                        )
                                    )}
                                </Pie>

                                <Tooltip />

                                <Legend iconType="circle" iconSize={9} />
                            </PieChart>
                        </ResponsiveContainer>
                        <div className="risk-distribution-counts">{riskChartData.map((item) => <div key={item.name}><span>{item.name}</span><strong>{item.value.toLocaleString("id-ID")}</strong></div>)}</div>
                        </>}
                    </div>
                </div>

                <div className="dashboard-panel">
                    <div className="panel-header">
                        <div>
                            <h3>
                                Penanganan Risiko
                            </h3>

                            <p>
                                Status penanganan
                                risiko.
                            </p>
                        </div>
                    </div>

                    <div className="chart-container">
                        {treatmentChartData.every((item) => item.total === 0) ? <div className="dashboard-chart-empty"><ClipboardCheck size={30} /><strong>Belum ada data penanganan</strong><span>Status tindak lanjut akan ditampilkan di sini.</span></div> : <>
                        <ResponsiveContainer
                            width="100%"
                            height={300}
                        >
                            <BarChart
                                data={
                                    treatmentChartData
                                }
                            >
                                <CartesianGrid
                                    strokeDasharray="3 3"
                                    vertical={false}
                                    stroke="#ece9e5"
                                />

                                <XAxis dataKey="name" axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: "#727681" }} />

                                <YAxis allowDecimals={false} axisLine={false} tickLine={false} width={36} tick={{ fontSize: 12, fill: "#727681" }} />

                                <Tooltip />

                                <Bar
                                    dataKey="total"
                                    name="Jumlah penanganan"
                                    fill="#792e3d"
                                    maxBarSize={56}
                                    radius={[
                                        5,
                                        5,
                                        0,
                                        0,
                                    ]}
                                />
                            </BarChart>
                        </ResponsiveContainer>
                        <div className="risk-distribution-counts">{treatmentChartData.map((item) => <div key={item.name}><span>{item.name}</span><strong>{item.total.toLocaleString("id-ID")}</strong></div>)}</div>
                        </>}
                    </div>
                </div>
            </div>

            {/* BIDANG */}

            <div className="dashboard-panel">
                <div className="panel-header">
                    <div>
                        <h3>
                            Monitoring Bidang
                        </h3>

                        <p>
                            Monitoring risiko
                            berdasarkan bidang.
                        </p>
                    </div>
                </div>

                <div className="table-wrapper">
                    <table className="dashboard-table">
                        <thead>
                            <tr>
                                <th>Bidang</th>
                                <th>Aset</th>
                                <th>Pengguna</th>
                                <th>Risiko</th>
                            </tr>
                        </thead>

                        <tbody>
                            {dashboard
                                .bidang_monitoring
                                .length > 0 ? (
                                dashboard.bidang_monitoring.map(
                                    (bidang) => (
                                        <tr
                                            key={
                                                bidang.id
                                            }
                                        >
                                            <td>
                                                <strong>
                                                    {
                                                        bidang.nama_bidang
                                                    }
                                                </strong>
                                            </td>

                                            <td>
                                                {
                                                    bidang.total_asset
                                                }
                                            </td>

                                            <td>
                                                {
                                                    bidang.total_user
                                                }
                                            </td>

                                            <td>
                                                {
                                                    bidang.total_risiko
                                                }
                                            </td>
                                        </tr>
                                    )
                                )
                            ) : (
                                <tr>
                                    <td
                                        colSpan={4}
                                        className="empty-cell"
                                    >
                                        Belum ada
                                        data bidang.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* RECENT RISK */}

            <div className="dashboard-panel recent-risk-panel">
                <div className="panel-header">
                    <div>
                        <h3>
                            Penilaian Risiko Terbaru
                        </h3>

                        <p>
                            Risiko terbaru yang
                            telah dinilai.
                        </p>
                    </div>
                </div>

                <div className="risk-toolbar">
                    <div className="search-box">
                        <Search size={17} />

                        <input
                            type="text"
                            placeholder="Cari aset atau ancaman..."
                            aria-label="Cari aset atau ancaman"
                            value={search}
                            onChange={(event) =>
                                setSearch(
                                    event.target
                                        .value
                                )
                            }
                        />
                    </div>

                    <select
                        aria-label="Filter tingkat risiko"
                        value={riskFilter}
                        onChange={(event) =>
                            setRiskFilter(
                                event.target.value
                            )
                        }
                    >
                        <option value="semua">
                            Semua Risiko
                        </option>

                        <option value="rendah">
                            Rendah
                        </option>

                        <option value="sedang">
                            Sedang
                        </option>

                        <option value="tinggi">
                            Tinggi
                        </option>

                        <option value="sangat_tinggi">
                            Sangat Tinggi
                        </option>

                        <option value="ekstrem">
                            Ekstrem
                        </option>
                    </select>
                </div>

                <div className="table-wrapper">
                    <table className="dashboard-table">
                        <thead>
                            <tr>
                                <th>Aset</th>
                                <th>Ancaman</th>
                                <th>Kemungkinan</th>
                                <th>Dampak</th>
                                <th>Skor</th>
                                <th>Tingkat</th>
                            </tr>
                        </thead>

                        <tbody>
                            {filteredRisks.length > 0 ? (
                                filteredRisks.map(
                                    (risk) => (
                                        <tr
                                            key={
                                                risk.id
                                            }
                                        >
                                            <td>
                                                <strong>
                                                    {
                                                        risk
                                                            .asset
                                                            ?.nama
                                                    }
                                                </strong>

                                                <small className="table-subtext">
                                                    {
                                                        risk
                                                            .asset
                                                            ?.snumber
                                                    }
                                                </small>
                                            </td>

                                            <td>
                                                {
                                                    risk
                                                        .threat
                                                        ?.nama_ancaman
                                                }
                                            </td>

                                            <td>
                                                {
                                                    risk.likelihood
                                                }
                                            </td>

                                            <td>
                                                {
                                                    risk.impact
                                                }
                                            </td>

                                            <td>
                                                <strong>
                                                    {
                                                        risk.skor
                                                    }
                                                </strong>
                                            </td>

                                            <td>
                                                <span
                                                    className={`risk-badge risk-${risk.level
                                                        .toLowerCase()
                                                        .replace(
                                                            /[\s_]+/g,
                                                            "-"
                                                        )}`}
                                                >
                                                    {
                                                        risk.level.replace(/_/g, " ")
                                                    }
                                                </span>
                                            </td>
                                        </tr>
                                    )
                                )
                            ) : (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="empty-cell"
                                    >
                                        Tidak ada
                                        risiko yang
                                        sesuai.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
};

export default Dashboard;
