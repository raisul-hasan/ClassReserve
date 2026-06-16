import { createBrowserRouter, Navigate } from "react-router";
import { Layout } from "./components/Layout";
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
    Component: Layout,
    children: [
      { index: true, Component: StudentDashboard },
      { path: "rooms", Component: Rooms },
      { path: "bookings", Component: Bookings },
      { path: "calendar", Component: Calendar },
      { path: "notifications", Component: Notifications },
      { path: "profile", Component: Profile },
      { path: "new-booking", Component: NewBooking },
    ],
  },
  {
    path: "/faculty",
    Component: Layout,
    children: [
      { index: true, Component: FacultyDashboard },
      { path: "calendar", Component: Calendar },
      { path: "rooms", Component: Rooms },
      { path: "bookings", Component: Bookings },
      { path: "approvals", Component: Approvals },
      { path: "notifications", Component: Notifications },
      { path: "profile", Component: Profile },
      { path: "new-booking", Component: NewBooking },
    ],
  },
  {
    path: "/admin",
    Component: Layout,
    children: [
      { index: true, Component: Dashboard },
      { path: "calendar", Component: Calendar },
      { path: "rooms", Component: Rooms },
      { path: "bookings", Component: Bookings },
      { path: "approvals", Component: Approvals },
      { path: "notifications", Component: Notifications },
      { path: "settings", Component: AdminSettings },
      { path: "profile", Component: Profile },
      { path: "new-booking", Component: NewBooking },
    ],
  },
]);