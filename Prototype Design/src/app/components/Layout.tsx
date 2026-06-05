import { useEffect, useState } from "react";
import { Outlet, NavLink, useNavigate } from "react-router";
import {
  LayoutDashboard,
  CalendarDays,
  DoorOpen,
  BookOpen,
  CheckSquare,
  Bell,
  Settings,
  User,
  Search,
  Plus,
  Sun,
  Moon,
  LogOut,
  MessageSquare,
  Flag,
  Wrench,
} from "lucide-react";
import { Input } from "./ui/input";
import { useTheme } from "../context/ThemeContext";
import { useAuth } from "../context/AuthContext";
import { getNotifications } from "../services/classReserveService";

const adminNavigation = [
  { name: "Dashboard", href: "/admin", icon: LayoutDashboard },
  { name: "Manage Requests", href: "/admin/approvals", icon: CheckSquare },
  { name: "Manage Rooms", href: "/admin/rooms", icon: DoorOpen },
  { name: "Maintenance", href: "/admin/maintenance", icon: Wrench },
  { name: "Issue Reports", href: "/admin/issue-reports", icon: Flag },
  { name: "Calendar", href: "/admin/calendar", icon: CalendarDays },
  { name: "Notifications", href: "/admin/notifications", icon: Bell },
  { name: "Settings", href: "/admin/settings", icon: Settings },
];

const studentNavigation = [
  { name: "Dashboard", href: "/student", icon: LayoutDashboard },
  { name: "Search Rooms", href: "/student/rooms", icon: DoorOpen },
  { name: "New Booking", href: "/student/new-booking", icon: Plus },
  { name: "My Bookings", href: "/student/bookings", icon: BookOpen },
  { name: "Calendar", href: "/student/calendar", icon: CalendarDays },
  { name: "Classroom Forum", href: "/student/forum", icon: MessageSquare },
  { name: "Notifications", href: "/student/notifications", icon: Bell },
  { name: "Profile", href: "/student/profile", icon: User },
];

const clubNavigation = [
  { name: "Dashboard", href: "/club", icon: LayoutDashboard },
  { name: "Search Rooms", href: "/club/rooms", icon: DoorOpen },
  { name: "Event Booking", href: "/club/new-booking", icon: Plus },
  { name: "My Bookings", href: "/club/bookings", icon: BookOpen },
  { name: "Calendar", href: "/club/calendar", icon: CalendarDays },
  { name: "Classroom Forum", href: "/club/forum", icon: MessageSquare },
  { name: "Notifications", href: "/club/notifications", icon: Bell },
  { name: "Profile", href: "/club/profile", icon: User },
];

const facultyNavigation = [
  { name: "Dashboard", href: "/faculty", icon: LayoutDashboard },
  { name: "Reserve Room", href: "/faculty/rooms", icon: DoorOpen },
  { name: "Approvals", href: "/faculty/approvals", icon: CheckSquare },
  { name: "My Bookings", href: "/faculty/bookings", icon: BookOpen },
  { name: "Calendar", href: "/faculty/calendar", icon: CalendarDays },
  { name: "Classroom Forum", href: "/faculty/forum", icon: MessageSquare },
  { name: "Notifications", href: "/faculty/notifications", icon: Bell },
  { name: "Profile", href: "/faculty/profile", icon: User },
];

function getRoleLabel(role: string) {
  if (role === "admin") return "Admin";
  if (role === "faculty") return "Faculty";
  if (role === "club") return "Club";
  return "Student";
}

function getInitials(name: string) {
  return name
    .split(" ")
    .map((n) => n[0])
    .join("")
    .toUpperCase()
    .slice(0, 2);
}

export function Layout() {
  const { theme, toggleTheme } = useTheme();
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const [unreadCount, setUnreadCount] = useState(0);
  const [globalSearch, setGlobalSearch] = useState("");

  useEffect(() => {
    getNotifications({ role: user?.role, userId: user?.id, email: user?.email })
      .then((items) => setUnreadCount(items.filter((item) => item.unread).length))
      .catch(() => setUnreadCount(0));
  }, [user?.role, user?.id, user?.email]);

  const handleLogout = () => {
    logout();
    navigate("/auth", { replace: true });
  };

  const handleNewBooking = () => {
    if (user?.role === "faculty") { navigate("/faculty/new-booking"); return; }
    if (user?.role === "admin") { navigate("/admin/new-booking"); return; }
    if (user?.role === "club") { navigate("/club/new-booking"); return; }
    navigate("/student/new-booking");
  };

  const handleNotifications = () => {
    if (user?.role === "faculty") { navigate("/faculty/notifications"); return; }
    if (user?.role === "admin") { navigate("/admin/notifications"); return; }
    if (user?.role === "club") { navigate("/club/notifications"); return; }
    navigate("/student/notifications");
  };

  const handleGlobalSearch = (e: React.FormEvent) => {
    e.preventDefault();
    const query = globalSearch.trim();
    const normalized = query.toLowerCase();
    if (!query) return;
    const roleBase = `/${user?.role || "student"}`;

    if (normalized.includes("issue") || normalized.includes("forum") || normalized.includes("problem")) {
      navigate(user?.role === "admin" ? "/admin/issue-reports" : `${roleBase}/forum`);
      return;
    }
    if (normalized.includes("booking") || normalized.includes("request") || normalized.includes("approval")) {
      navigate(user?.role === "faculty" || user?.role === "admin" ? `${roleBase}/approvals` : `${roleBase}/bookings`);
      return;
    }
    if (normalized.includes("calendar") || normalized.includes("schedule")) {
      navigate(`${roleBase}/calendar`);
      return;
    }
    if (normalized.includes("maintenance") && user?.role === "admin") {
      navigate("/admin/maintenance");
      return;
    }
    navigate(`${roleBase}/rooms`, { state: { searchQuery: query } });
  };

  const navigation =
    user?.role === "admin" ? adminNavigation :
    user?.role === "faculty" ? facultyNavigation :
    user?.role === "club" ? clubNavigation :
    studentNavigation;

  const quickActionText = user?.role === "faculty" ? "Quick Reserve" : "New Booking";

  return (
    <div className="flex h-screen bg-background transition-colors">
      {/* Sidebar */}
      <aside
        className="w-64 flex flex-col flex-shrink-0 transition-colors"
        style={{ background: theme === "dark" ? "#180503" : "#210706" }}
      >
        {/* Logo */}
        <div className="h-16 flex items-center px-5 border-b border-white/10">
          <div className="flex items-center gap-3">
            <div
              className="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0"
              style={{ background: "#891D1A" }}
            >
              <DoorOpen className="w-5 h-5 text-white" />
            </div>
            <span
              className="text-white text-xl"
              style={{ fontFamily: "'Playfair Display', serif", fontWeight: 700 }}
            >
              ClassReserve
            </span>
          </div>
        </div>

        {/* Nav */}
        <nav className="flex-1 px-3 py-5 space-y-0.5 overflow-y-auto">
          {navigation.map((item) => (
            <NavLink
              key={item.name}
              to={item.href}
              end={item.href === "/admin" || item.href === "/student" || item.href === "/club" || item.href === "/faculty"}
              className={({ isActive }) =>
                `group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-all relative ${
                  isActive
                    ? "bg-[#891D1A]/10 text-white border-l-4 border-[#891D1A] pl-2"
                    : "text-[#F1E6D2]/65 hover:text-[#F1E6D2]/90 hover:bg-white/5 border-l-4 border-transparent pl-2"
                }`
              }
            >
              <item.icon className="w-4.5 h-4.5 flex-shrink-0" />
              {item.name}
            </NavLink>
          ))}
        </nav>

        {/* User Card */}
        <div className="p-4 border-t border-white/10 space-y-3">
          <div className="flex items-center gap-3 px-1">
            <div
              className="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0 text-sm font-semibold text-white"
              style={{ background: "#891D1A" }}
            >
              {getInitials(user?.name || "U")}
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-sm font-medium text-white truncate">{user?.name || "User"}</p>
              <span
                className="inline-block text-xs text-[#F1E6D2]/70 px-2 py-0.5 rounded-full mt-0.5"
                style={{ background: "#5E657B", fontFamily: "'DM Sans', sans-serif" }}
              >
                {getRoleLabel(user?.role || "student")}
              </span>
            </div>
          </div>

          <button
            onClick={handleLogout}
            className="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-[#F1E6D2]/60 hover:text-[#891D1A] hover:bg-white/5 transition-colors"
            style={{ fontFamily: "'DM Sans', sans-serif" }}
          >
            <LogOut className="w-4 h-4" />
            Sign Out
          </button>
        </div>
      </aside>

      {/* Main */}
      <div className="flex-1 flex flex-col overflow-hidden">
        {/* Header */}
        <header
          className="h-16 flex items-center justify-between px-6 transition-colors flex-shrink-0"
          style={{
            background: theme === "dark" ? "#2E0E0C" : "#F1E6D2",
            borderBottom: "1px solid rgba(137,29,26,0.15)",
          }}
        >
          <div className="flex-1 max-w-md">
            <form className="relative" onSubmit={handleGlobalSearch}>
              <Search
                className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4"
                style={{ color: "#891D1A" }}
              />
              <Input
                placeholder="Search rooms, bookings…"
                value={globalSearch}
                onChange={(e) => setGlobalSearch(e.target.value)}
                className="pl-9 bg-white dark:bg-[#3A1210] border-0 rounded-full shadow-sm"
                style={{
                  color: theme === "dark" ? "#F1E6D2" : "#210706",
                  fontFamily: "'DM Sans', sans-serif",
                }}
              />
            </form>
          </div>

          <div className="flex items-center gap-2 ml-4">
            <button
              onClick={toggleTheme}
              className="w-9 h-9 rounded-lg flex items-center justify-center transition-colors hover:bg-[#891D1A]/10"
              style={{ color: "#5E657B" }}
            >
              {theme === "light" ? <Moon className="w-5 h-5" /> : <Sun className="w-5 h-5" />}
            </button>

            <button
              onClick={handleNotifications}
              className="w-9 h-9 rounded-lg flex items-center justify-center transition-colors hover:bg-[#891D1A]/10 relative"
              style={{ color: "#5E657B" }}
            >
              <Bell className="w-5 h-5" />
              {unreadCount > 0 && (
                <span
                  className="absolute top-1 right-1 min-w-4 h-4 px-1 rounded-full text-[10px] leading-4 text-white text-center"
                  style={{ background: "#891D1A" }}
                >
                  {unreadCount > 9 ? "9+" : unreadCount}
                </span>
              )}
            </button>

            <button
              onClick={handleNewBooking}
              className="flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium text-[#F1E6D2] transition-colors"
              style={{
                background: "#891D1A",
                fontFamily: "'DM Sans', sans-serif",
              }}
              onMouseEnter={(e) => (e.currentTarget.style.background = "#210706")}
              onMouseLeave={(e) => (e.currentTarget.style.background = "#891D1A")}
            >
              <Plus className="w-4 h-4" />
              {quickActionText}
            </button>
          </div>
        </header>

        <main className="flex-1 overflow-auto">
          <div className="p-6">
            <Outlet />
          </div>
        </main>
      </div>
    </div>
  );
}
