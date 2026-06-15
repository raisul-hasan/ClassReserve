import { useEffect, useState } from "react";
import { AlertTriangle, BookOpen, Clock, DoorOpen, TrendingUp, Wrench } from "lucide-react";
import { useNavigate } from "react-router";
import { Bar, BarChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from "recharts";
import { getDashboardStats } from "../services/classReserveService";
import type { DashboardStats } from "../types/classReserve";

const PLAYFAIR = { fontFamily: "'Playfair Display', serif" } as const;
const DM_SANS = { fontFamily: "'DM Sans', sans-serif" } as const;

function actionLabel(action: string) { return action.replaceAll("_", " ").replace(/\b\w/g, (letter) => letter.toUpperCase()); }

export function Dashboard() {
  const navigate = useNavigate();
  const [data, setData] = useState<DashboardStats | null>(null);
  const [error, setError] = useState("");

  useEffect(() => {
    getDashboardStats().then(setData).catch((reason) => setError(reason instanceof Error ? reason.message : "Could not load dashboard data."));
  }, []);

  const stats = [
    { title: "Total Rooms", value: data?.rooms.total ?? 0, icon: DoorOpen, color: "#5E657B" },
    { title: "Available Rooms", value: data?.rooms.available ?? 0, icon: DoorOpen, color: "#3B6E4A" },
    { title: "Pending Requests", value: data?.bookings.pending ?? 0, icon: Clock, color: "#B8860B" },
    { title: "Approved Bookings", value: data?.bookings.approved ?? 0, icon: BookOpen, color: "#891D1A" },
    { title: "Maintenance Rooms", value: data?.rooms.maintenance ?? 0, icon: Wrench, color: "#891D1A" },
  ];
  const quickActions = [
    ["Manage Requests", "/admin/approvals"], ["Manage Rooms", "/admin/rooms"], ["Maintenance", "/admin/maintenance"],
    ["Issue Reports", "/admin/issue-reports"], ["Calendar", "/admin/calendar"], ["Audit Logs", "/admin/audit-logs"],
  ];

  return <div className="space-y-6" style={DM_SANS}>
    <div><h1 style={{ ...PLAYFAIR, fontSize: 28, fontWeight: 600 }} className="text-foreground">Admin Dashboard</h1><p className="text-sm mt-1" style={{ color: "#5E657B" }}>System-wide overview of the classroom booking platform</p></div>
    {error && <div className="rounded-xl p-4 flex gap-3" style={{ background: "rgba(137,29,26,0.06)", border: "1px solid rgba(137,29,26,0.2)", color: "#891D1A" }}><AlertTriangle className="w-5 h-5" /><p className="text-sm">{error}</p></div>}
    <div className="bg-card rounded-xl p-4 shadow-sm flex flex-wrap gap-2">{quickActions.map(([label, path]) => <button key={path} onClick={() => navigate(path)} className="px-4 py-2 rounded-lg text-sm font-medium border hover:bg-[#891D1A]/5" style={{ borderColor: "rgba(137,29,26,0.25)", color: "#891D1A" }}>{label}</button>)}</div>
    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">{stats.map((stat) => <div key={stat.title} className="bg-card rounded-xl p-5 shadow-sm" style={{ borderLeft: `3px solid ${stat.color}` }}><div className="flex items-center justify-between mb-3"><p className="text-xs text-[#5E657B]">{stat.title}</p><div className="w-9 h-9 rounded-lg flex items-center justify-center" style={{ background: `${stat.color}18` }}><stat.icon className="w-4 h-4" style={{ color: stat.color }} /></div></div><p className="text-[#210706] dark:text-[#F1E6D2]" style={{ ...PLAYFAIR, fontSize: 32, fontWeight: 700 }}>{stat.value}</p></div>)}</div>
    <div className="grid grid-cols-1 xl:grid-cols-5 gap-6">
      <div className="xl:col-span-3 bg-card rounded-xl shadow-sm overflow-hidden"><div className="px-5 py-4 border-b border-border"><h3 style={{ ...PLAYFAIR, fontSize: 16, fontWeight: 600 }}>Recent Activity</h3></div><div className="p-5 space-y-4">{data?.recent_activity.length ? data.recent_activity.map((item) => <div key={item.id} className="flex items-start gap-3"><div className="w-9 h-9 rounded-full flex items-center justify-center text-sm font-semibold text-white" style={{ background: "#891D1A" }}>{(item.user_name || "S").charAt(0)}</div><div><p className="text-sm font-semibold text-foreground">{item.user_name || "System"}</p><p className="text-xs mt-1" style={{ color: "#5E657B" }}>{actionLabel(item.action)}{item.target_type ? ` ${item.target_type} #${item.target_id || ""}` : ""} · {item.created_at}</p></div></div>) : <p className="text-sm" style={{ color: "#5E657B" }}>No audit activity recorded yet.</p>}</div></div>
      <div className="xl:col-span-2 bg-card rounded-xl shadow-sm overflow-hidden"><div className="px-5 py-4 border-b border-border flex items-center gap-2"><TrendingUp className="w-4 h-4" style={{ color: "#891D1A" }} /><h3 style={{ ...PLAYFAIR, fontSize: 16, fontWeight: 600 }}>Bookings by Role</h3></div><div className="p-5"><ResponsiveContainer width="100%" height={220}><BarChart data={data?.bookings_by_role || []} margin={{ left: -20 }}><CartesianGrid strokeDasharray="3 3" stroke="rgba(94,101,123,0.15)" /><XAxis dataKey="role" tick={{ fill: "#5E657B", fontSize: 12 }} /><YAxis allowDecimals={false} tick={{ fill: "#5E657B", fontSize: 12 }} /><Tooltip /><Bar dataKey="count" name="Bookings" fill="#891D1A" radius={[4, 4, 0, 0]} /></BarChart></ResponsiveContainer></div></div>
    </div>
  </div>;
}
