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
} from "lucide-react";
import { Button } from "./ui/button";
import { Input } from "./ui/input";
import { useTheme } from "../context/ThemeContext";
import { useAuth } from "../context/AuthContext";

const adminNavigation = [
  { name: "Dashboard", href: "/admin", icon: LayoutDashboard },
  { name: "Calendar", href: "/admin/calendar", icon: CalendarDays },
  { name: "Rooms", href: "/admin/rooms", icon: DoorOpen },
  { name: "Bookings", href: "/admin/bookings", icon: BookOpen },
  { name: "Approvals", href: "/admin/approvals", icon: CheckSquare },
  { name: "Notifications", href: "/admin/notifications", icon: Bell },
  { name: "Admin Settings", href: "/admin/settings", icon: Settings },
  { name: "Profile", href: "/admin/profile", icon: User },
];

const studentNavigation = [
  { name: "Dashboard", href: "/student", icon: LayoutDashboard },
  { name: "Browse Rooms", href: "/student/rooms", icon: DoorOpen },
  { name: "My Bookings", href: "/student/bookings", icon: BookOpen },
  { name: "Calendar", href: "/student/calendar", icon: CalendarDays },
  { name: "Notifications", href: "/student/notifications", icon: Bell },
  { name: "Profile", href: "/student/profile", icon: User },
];

const facultyNavigation = [
  { name: "Dashboard", href: "/faculty", icon: LayoutDashboard },
  { name: "Calendar", href: "/faculty/calendar", icon: CalendarDays },
  { name: "Browse Rooms", href: "/faculty/rooms", icon: DoorOpen },
  { name: "My Classes", href: "/faculty/bookings", icon: BookOpen },
  { name: "Approvals", href: "/faculty/approvals", icon: CheckSquare },
  { name: "Notifications", href: "/faculty/notifications", icon: Bell },
  { name: "Profile", href: "/faculty/profile", icon: User },
];

export function Layout() {
  const { theme, toggleTheme } = useTheme();
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = () => {
    logout();
    navigate("/auth");
  };

  const handleNewBooking = () => {
    if (user?.role === "faculty") {
      navigate("/faculty/new-booking");
      return;
    }

    if (user?.role === "admin") {
      navigate("/admin/new-booking");
      return;
    }

    navigate("/student/new-booking");
  };

  const handleNotifications = () => {
    if (user?.role === "faculty") {
      navigate("/faculty/notifications");
      return;
    }

    if (user?.role === "admin") {
      navigate("/admin/notifications");
      return;
    }

    navigate("/student/notifications");
  };

  const navigation =
    user?.role === "admin"
      ? adminNavigation
      : user?.role === "faculty"
        ? facultyNavigation
        : studentNavigation;

  const quickActionText =
    user?.role === "faculty" ? "Quick Reserve" : "New Booking";

  return (
    <div className="flex h-screen bg-gray-50 dark:bg-gray-950 transition-colors">
      <aside className="w-64 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 flex flex-col transition-colors">
        <div className="h-16 flex items-center px-6 border-b border-gray-200 dark:border-gray-800">
          <div className="flex items-center gap-2">
            <div className="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
              <DoorOpen className="w-5 h-5 text-white" />
            </div>
            <span className="font-semibold text-gray-900 dark:text-white">ClassRoom</span>
          </div>
        </div>

        <nav className="flex-1 px-3 py-4 space-y-1">
          {navigation.map((item) => (
            <NavLink
              key={item.name}
              to={item.href}
              end={item.href === "/admin" || item.href === "/student" || item.href === "/faculty"}
              className={({ isActive }) =>
                `flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-colors ${
                  isActive
                    ? "bg-blue-50 dark:bg-blue-950 text-blue-700 dark:text-blue-300 font-medium"
                    : "text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800"
                }`
              }
            >
              <item.icon className="w-5 h-5" />
              {item.name}
            </NavLink>
          ))}
        </nav>

        <div className="p-4 border-t border-gray-200 dark:border-gray-800 space-y-3">
          <div className="flex items-center gap-3 px-2">
            <div className="w-9 h-9 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center">
              <User className="w-5 h-5 text-gray-600 dark:text-gray-300" />
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-sm font-medium text-gray-900 dark:text-white truncate">
                {user?.name || "User"}
              </p>
              <p className="text-xs text-gray-500 dark:text-gray-400 truncate">
                {user?.email || "user@university.edu"}
              </p>
            </div>
          </div>

          <Button
            variant="outline"
            size="sm"
            onClick={handleLogout}
            className="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:text-gray-300 gap-2"
          >
            <LogOut className="w-4 h-4" />
            Logout
          </Button>
        </div>
      </aside>

      <div className="flex-1 flex flex-col overflow-hidden">
        <header className="h-16 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between px-6 transition-colors">
          <div className="flex-1 max-w-xl">
            <div className="relative">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
              <Input
                placeholder="Search rooms, bookings, or users..."
                className="pl-9 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 dark:text-white rounded-xl"
              />
            </div>
          </div>

          <div className="flex items-center gap-3">
            <Button
              variant="ghost"
              size="icon"
              className="rounded-xl dark:text-gray-300"
              onClick={toggleTheme}
            >
              {theme === "light" ? <Moon className="w-5 h-5" /> : <Sun className="w-5 h-5" />}
            </Button>

            <Button
              variant="ghost"
              size="icon"
              className="rounded-xl dark:text-gray-300"
              onClick={handleNotifications}
            >
              <Bell className="w-5 h-5" />
            </Button>

            <Button
              onClick={handleNewBooking}
              className={`${
                user?.role === "faculty"
                  ? "bg-purple-600 hover:bg-purple-700"
                  : "bg-blue-600 hover:bg-blue-700"
              } rounded-xl gap-2`}
            >
              <Plus className="w-4 h-4" />
              {quickActionText}
            </Button>
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