import { DoorOpen, BookOpen, Clock, AlertTriangle, TrendingUp } from "lucide-react";
import { useNavigate } from "react-router";
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  Legend,
} from "recharts";

const PLAYFAIR = { fontFamily: "'Playfair Display', serif" } as const;
const DM_SANS = { fontFamily: "'DM Sans', sans-serif" } as const;

const stats = [
  {
    title: "Total Rooms",
    value: "42",
    icon: DoorOpen,
    borderColor: "#5E657B",
    iconBg: "rgba(94,101,123,0.1)",
    iconColor: "#5E657B",
  },
  {
    title: "Rooms Available Now",
    value: "28",
    icon: DoorOpen,
    borderColor: "#3B6E4A",
    iconBg: "rgba(59,110,74,0.1)",
    iconColor: "#3B6E4A",
  },
  {
    title: "Pending Requests",
    value: "7",
    icon: Clock,
    borderColor: "#B8860B",
    iconBg: "rgba(184,134,11,0.1)",
    iconColor: "#B8860B",
  },
  {
    title: "Active Bookings",
    value: "18",
    icon: BookOpen,
    borderColor: "#891D1A",
    iconBg: "rgba(137,29,26,0.1)",
    iconColor: "#891D1A",
  },
  {
    title: "Conflicts Today",
    value: "2",
    icon: AlertTriangle,
    borderColor: "#891D1A",
    iconBg: "rgba(137,29,26,0.1)",
    iconColor: "#891D1A",
  },
];

const recentActivity = [
  { user: "Dr. Sarah Johnson", action: "booked", room: "Room A-301", time: "2 min ago", type: "faculty" },
  { user: "Engineering Club", action: "requested", room: "Auditorium B", time: "15 min ago", type: "club" },
  { user: "Admin", action: "approved booking for", room: "Lab C-105", time: "1 hr ago", type: "admin" },
  { user: "Michael Chen", action: "requested", room: "Room D-202", time: "2 hr ago", type: "student" },
  { user: "Admin", action: "set maintenance for", room: "Room E-101", time: "3 hr ago", type: "maintenance" },
];

const weeklyData = [
  { day: "Mon", bookings: 12, approved: 10 },
  { day: "Tue", bookings: 19, approved: 15 },
  { day: "Wed", bookings: 8, approved: 7 },
  { day: "Thu", bookings: 22, approved: 18 },
  { day: "Fri", bookings: 15, approved: 12 },
  { day: "Sat", bookings: 6, approved: 5 },
  { day: "Sun", bookings: 3, approved: 3 },
];

function roleColor(type: string) {
  switch (type) {
    case "faculty": return "#891D1A";
    case "club": return "#5E657B";
    case "student": return "#B8860B";
    case "admin": return "#210706";
    default: return "#5E657B";
  }
}

function roleLabel(type: string) {
  switch (type) {
    case "faculty": return "Faculty";
    case "club": return "Club";
    case "student": return "Student";
    case "admin": return "Admin";
    default: return "";
  }
}

export function Dashboard() {
  const navigate = useNavigate();
  const quickActions = [
    { label: "Manage Requests", path: "/admin/approvals" },
    { label: "Manage Rooms", path: "/admin/rooms" },
    { label: "Maintenance", path: "/admin/maintenance" },
    { label: "Issue Reports", path: "/admin/issue-reports" },
    { label: "Calendar", path: "/admin/calendar" },
    { label: "Settings", path: "/admin/settings" },
  ];

  return (
    <div className="space-y-6" style={DM_SANS}>
      <div>
        <h1 style={{ ...PLAYFAIR, fontSize: 28, fontWeight: 600 }} className="text-foreground">
          Admin Dashboard
        </h1>
        <p className="text-sm mt-1" style={{ color: "#5E657B" }}>
          System-wide overview of the classroom booking platform
        </p>
      </div>

      <div className="bg-card rounded-xl p-4 shadow-sm flex flex-wrap gap-2">
        {quickActions.map((action) => (
          <button
            key={action.path}
            onClick={() => navigate(action.path)}
            className="px-4 py-2 rounded-lg text-sm font-medium border hover:bg-[#891D1A]/5"
            style={{ borderColor: "rgba(137,29,26,0.25)", color: "#891D1A" }}
          >
            {action.label}
          </button>
        ))}
      </div>

      {/* Conflict Alert */}
      <div
        className="rounded-xl p-4 flex items-start gap-3"
        style={{ background: "rgba(137,29,26,0.06)", border: "1px solid rgba(137,29,26,0.2)" }}
      >
        <AlertTriangle className="w-5 h-5 mt-0.5 flex-shrink-0" style={{ color: "#891D1A" }} />
        <div>
          <p className="text-sm font-semibold" style={{ color: "#891D1A" }}>
            2 Scheduling Conflicts Detected
          </p>
          <p className="text-sm mt-1 text-[#5E657B]">
            Room D-202 has overlapping bookings for Apr 3, 2026. Immediate action required.
          </p>
        </div>
      </div>

      {/* Stat Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        {stats.map((stat) => (
          <div
            key={stat.title}
            className="bg-card rounded-xl p-5 shadow-sm"
            style={{ borderLeft: `3px solid ${stat.borderColor}` }}
          >
            <div className="flex items-center justify-between mb-3">
              <p className="text-xs text-[#5E657B] leading-tight">{stat.title}</p>
              <div
                className="w-9 h-9 rounded-lg flex items-center justify-center"
                style={{ background: stat.iconBg }}
              >
                <stat.icon className="w-4.5 h-4.5" style={{ color: stat.iconColor }} />
              </div>
            </div>
            <p
              className="text-[#210706] dark:text-[#F1E6D2]"
              style={{ ...PLAYFAIR, fontSize: 32, fontWeight: 700, lineHeight: 1 }}
            >
              {stat.value}
            </p>
          </div>
        ))}
      </div>

      <div className="grid grid-cols-1 xl:grid-cols-5 gap-6">
        {/* Activity Feed */}
        <div className="xl:col-span-3 bg-card rounded-xl shadow-sm overflow-hidden">
          <div className="px-5 py-4 border-b border-border">
            <h3 style={{ ...PLAYFAIR, fontSize: 16, fontWeight: 600 }} className="text-foreground">
              Recent Activity
            </h3>
          </div>
          <div className="p-5 space-y-4">
            {recentActivity.map((item, i) => (
              <div key={i} className="flex items-start gap-3">
                <div
                  className="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0 text-sm font-semibold text-white"
                  style={{ background: roleColor(item.type) }}
                >
                  {item.user.charAt(0)}
                </div>
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-2 flex-wrap">
                    <span className="text-sm font-semibold text-foreground">{item.user}</span>
                    <span className="text-sm text-[#5E657B]">{item.action}</span>
                    <span
                      className="text-xs px-2 py-0.5 rounded-full text-white"
                      style={{ background: "#5E657B" }}
                    >
                      {item.room}
                    </span>
                  </div>
                  <div className="flex items-center gap-2 mt-1">
                    <span
                      className="text-xs px-2 py-0.5 rounded-full font-medium"
                      style={{
                        background: roleColor(item.type) + "18",
                        color: roleColor(item.type),
                      }}
                    >
                      {roleLabel(item.type)}
                    </span>
                    <span className="text-xs text-[#5E657B]">{item.time}</span>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Weekly Chart */}
        <div className="xl:col-span-2 bg-card rounded-xl shadow-sm overflow-hidden">
          <div className="px-5 py-4 border-b border-border flex items-center gap-2">
            <TrendingUp className="w-4 h-4" style={{ color: "#891D1A" }} />
            <h3 style={{ ...PLAYFAIR, fontSize: 16, fontWeight: 600 }} className="text-foreground">
              Bookings This Week
            </h3>
          </div>
          <div className="p-5">
            <ResponsiveContainer width="100%" height={200}>
              <BarChart data={weeklyData} margin={{ top: 0, right: 0, left: -20, bottom: 0 }}>
                <CartesianGrid strokeDasharray="3 3" stroke="rgba(94,101,123,0.15)" />
                <XAxis dataKey="day" tick={{ fill: "#5E657B", fontSize: 12 }} axisLine={false} tickLine={false} />
                <YAxis tick={{ fill: "#5E657B", fontSize: 12 }} axisLine={false} tickLine={false} />
                <Tooltip
                  contentStyle={{
                    background: "#fff",
                    border: "1px solid rgba(137,29,26,0.15)",
                    borderRadius: 8,
                    fontFamily: "'DM Sans', sans-serif",
                  }}
                />
                <Legend wrapperStyle={{ fontSize: 12, color: "#5E657B" }} />
                <Bar dataKey="bookings" name="Total" fill="#891D1A" radius={[4, 4, 0, 0]} />
                <Bar dataKey="approved" name="Approved" fill="#3B6E4A" radius={[4, 4, 0, 0]} />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </div>
      </div>
    </div>
  );
}
