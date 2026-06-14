import { createBrowserRouter, Navigate, useLocation } from "react-router";
import { Layout } from "./components/Layout";
import { useAuth, type UserRole } from "./context/AuthContext";
import { Auth } from "./pages/Auth";
import { Dashboard } from "./pages/Dashboard";
import { StudentDashboard } from "./pages/StudentDashboard";
import { FacultyDashboard } from "./pages/FacultyDashboard";
import { Calendar } from "./pages/Calendar";
import { Rooms } from "./pages/Rooms";
import { Bookings } from "./pages/Bookings";
import { Approvals } from "./pages/Approvals";
import { Notifications } from "./pages/Notifications";
import { AdminSettings } from "./pages/AdminSettings";
import { Profile } from "./pages/Profile";
import { NewBooking } from "./pages/NewBooking";
import { RoomDetail } from "./pages/RoomDetail";
import { BookingConfirmation } from "./pages/BookingConfirmation";
import { Forum } from "./pages/Forum";
import { IssueReports } from "./pages/IssueReports";
import { Maintenance } from "./pages/Maintenance";
import { AuditLogs } from "./pages/AuditLogs";

function RoleLayout({ role }: { role: UserRole }) {
  const { user, isLoading } = useAuth();
  const location = useLocation();

  if (isLoading) return null;
  if (!user) return <Navigate to="/auth" replace />;

  if (user.role !== role) {
    const subPath = location.pathname.split("/").slice(2).join("/");
    const destination = subPath === "profile" ? `/${user.role}/profile` : `/${user.role}`;
    return <Navigate to={destination} replace />;
  }

  return <Layout />;
}

export const router = createBrowserRouter([
  {
    path: "/auth",
    Component: Auth,
  },
  {
    path: "/",
    element: <Navigate to="/auth" replace />,
  },
  {
    path: "/student",
    element: <RoleLayout role="student" />,
    children: [
      { index: true, Component: StudentDashboard },
      { path: "rooms", Component: Rooms },
      { path: "rooms/:roomName", Component: RoomDetail },
      { path: "bookings", Component: Bookings },
      { path: "calendar", Component: Calendar },
      { path: "notifications", Component: Notifications },
      { path: "profile", Component: Profile },
      { path: "new-booking", Component: NewBooking },
      { path: "booking-confirmation", Component: BookingConfirmation },
      { path: "forum", Component: Forum },
    ],
  },
  {
    path: "/club",
    element: <RoleLayout role="club" />,
    children: [
      { index: true, Component: StudentDashboard },
      { path: "rooms", Component: Rooms },
      { path: "rooms/:roomName", Component: RoomDetail },
      { path: "bookings", Component: Bookings },
      { path: "calendar", Component: Calendar },
      { path: "notifications", Component: Notifications },
      { path: "profile", Component: Profile },
      { path: "new-booking", Component: NewBooking },
      { path: "booking-confirmation", Component: BookingConfirmation },
      { path: "forum", Component: Forum },
    ],
  },
  {
    path: "/faculty",
    element: <RoleLayout role="faculty" />,
    children: [
      { index: true, Component: FacultyDashboard },
      { path: "calendar", Component: Calendar },
      { path: "rooms", Component: Rooms },
      { path: "rooms/:roomName", Component: RoomDetail },
      { path: "bookings", Component: Bookings },
      { path: "maintenance", element: <Navigate to="/faculty" replace /> },
      { path: "approvals", Component: Approvals },
      { path: "notifications", Component: Notifications },
      { path: "profile", Component: Profile },
      { path: "new-booking", Component: NewBooking },
      { path: "booking-confirmation", Component: BookingConfirmation },
      { path: "forum", Component: Forum },
    ],
  },
  {
    path: "/admin",
    element: <RoleLayout role="admin" />,
    children: [
      { index: true, Component: Dashboard },
      { path: "calendar", Component: Calendar },
      { path: "rooms", Component: Rooms },
      { path: "rooms/:roomName", Component: RoomDetail },
      { path: "maintenance", Component: Maintenance },
      { path: "bookings", element: <Navigate to="/admin/approvals" replace /> },
      { path: "approvals", Component: Approvals },
      { path: "notifications", Component: Notifications },
      { path: "settings", Component: AdminSettings },
      { path: "profile", element: <Navigate to="/admin/settings" replace /> },
      { path: "new-booking", element: <Navigate to="/admin/approvals" replace /> },
      { path: "booking-confirmation", element: <Navigate to="/admin/approvals" replace /> },
      { path: "issue-reports", Component: IssueReports },
      { path: "audit-logs", Component: AuditLogs },
    ],
  },
]);
