import { useEffect, useMemo, useState } from "react";
import { Clock, BookOpen, Shield, FileText } from "lucide-react";
import { getMyBookings } from "../services/classReserveService";
import { useAuth } from "../context/AuthContext";

const PLAYFAIR = { fontFamily: "'Playfair Display', serif" } as const;
const DM_SANS = { fontFamily: "'DM Sans', sans-serif" } as const;

type Status = "approved" | "pending" | "rejected" | "cancelled";
type RoleType = "Faculty" | "Club" | "Student";
type BookingRow = {
  id: number;
  eventName: string;
  userRole: RoleType;
  user: string;
  room: string;
  building: string;
  date: string;
  time: string;
  status: Status;
  hasDoc: boolean;
};

const fallbackBookings = [
  { id: 1, eventName: "Math 101 Lecture", userRole: "Faculty" as RoleType, user: "Dr. Sarah Johnson", room: "Room A-301", building: "Building A", date: "Apr 1, 2026", time: "10:00 AM – 12:00 PM", status: "approved" as Status, hasDoc: true },
  { id: 2, eventName: "Engineering Club Meeting", userRole: "Club" as RoleType, user: "Engineering Club", room: "Room B-205", building: "Building B", date: "Apr 1, 2026", time: "2:00 PM – 4:00 PM", status: "approved" as Status, hasDoc: false },
  { id: 3, eventName: "Study Group Session", userRole: "Student" as RoleType, user: "Michael Chen", room: "Lab C-105", building: "Building C", date: "Apr 2, 2026", time: "3:00 PM – 5:00 PM", status: "pending" as Status, hasDoc: false },
  { id: 4, eventName: "Physics Lab", userRole: "Faculty" as RoleType, user: "Prof. David Lee", room: "Lab C-106", building: "Building C", date: "Apr 2, 2026", time: "9:00 AM – 12:00 PM", status: "approved" as Status, hasDoc: true },
  { id: 5, eventName: "Project Presentation", userRole: "Student" as RoleType, user: "Emma Wilson", room: "Room D-202", building: "Building D", date: "Apr 3, 2026", time: "1:00 PM – 2:00 PM", status: "rejected" as Status, hasDoc: false },
  { id: 6, eventName: "Guest Lecture Series", userRole: "Faculty" as RoleType, user: "Dr. Maria Garcia", room: "Auditorium B", building: "Building B", date: "Apr 4, 2026", time: "2:00 PM – 5:00 PM", status: "approved" as Status, hasDoc: true },
  { id: 7, eventName: "Dance Club Practice", userRole: "Club" as RoleType, user: "Dance Club", room: "Room E-101", building: "Building E", date: "Apr 5, 2026", time: "4:00 PM – 6:00 PM", status: "pending" as Status, hasDoc: false },
  { id: 8, eventName: "Tutorial Session", userRole: "Student" as RoleType, user: "James Brown", room: "Room A-302", building: "Building A", date: "Apr 5, 2026", time: "10:00 AM – 11:00 AM", status: "cancelled" as Status, hasDoc: false },
];

function statusStyle(s: Status) {
  switch (s) {
    case "approved": return { bg: "#3B6E4A", label: "Approved" };
    case "pending": return { bg: "#B8860B", label: "Pending" };
    case "rejected": return { bg: "#891D1A", label: "Rejected" };
    case "cancelled": return { bg: "#5E657B", label: "Cancelled" };
  }
}

function roleStyle(r: RoleType) {
  switch (r) {
    case "Faculty": return { bg: "rgba(137,29,26,0.1)", color: "#891D1A" };
    case "Club": return { bg: "rgba(94,101,123,0.1)", color: "#5E657B" };
    case "Student": return { bg: "rgba(184,134,11,0.1)", color: "#B8860B" };
  }
}

function priorityForRole(r: RoleType): { label: string; color: string } {
  switch (r) {
    case "Faculty": return { label: "HIGH", color: "#891D1A" };
    case "Club": return { label: "MEDIUM", color: "#5E657B" };
    case "Student": return { label: "STANDARD", color: "#B8860B" };
  }
}

type Tab = "all" | Status;
const tabs: { key: Tab; label: string }[] = [
  { key: "all", label: "All" },
  { key: "pending", label: "Pending" },
  { key: "approved", label: "Approved" },
  { key: "rejected", label: "Rejected" },
  { key: "cancelled", label: "Cancelled" },
];

export function Bookings() {
  const [activeTab, setActiveTab] = useState<Tab>("all");
  const [bookings, setBookings] = useState<BookingRow[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const { user } = useAuth();

  useEffect(() => {
    getMyBookings({ role: user?.role || "student", userId: user?.id, email: user?.email })
      .then((items) => {
        setBookings(items.map((item) => ({
          id: item.id,
          eventName: item.title,
          userRole: (item.requesterRole.charAt(0).toUpperCase() + item.requesterRole.slice(1)) as RoleType,
          user: item.requesterName,
          room: item.roomName,
          building: item.building || "Campus",
          date: item.date,
          time: `${item.startTime} - ${item.endTime}`,
          status: item.status as Status,
          hasDoc: item.hasDocument,
        })));
      })
      .finally(() => setIsLoading(false));
  }, [user?.role, user?.id, user?.email]);

  const filtered = useMemo(() => {
    if (activeTab === "all") return bookings;
    return bookings.filter((b) => b.status === activeTab);
  }, [bookings, activeTab]);

  return (
    <div className="space-y-5" style={DM_SANS}>
      <div>
        <h1 style={{ ...PLAYFAIR, fontSize: 28, fontWeight: 600 }} className="text-foreground">
          Bookings
        </h1>
        <p className="text-sm mt-1" style={{ color: "#5E657B" }}>
          {isLoading ? "Loading booking requests..." : "All room booking requests and their current status"}
        </p>
      </div>

      {/* Tab bar */}
      <div className="bg-card rounded-xl shadow-sm overflow-hidden">
        <div className="flex border-b border-border px-4">
          {tabs.map((tab) => {
            const count = tab.key === "all" ? bookings.length : bookings.filter((b) => b.status === tab.key).length;
            return (
              <button
                key={tab.key}
                onClick={() => setActiveTab(tab.key)}
                className="px-4 py-3 text-sm border-b-2 transition-colors flex items-center gap-1.5"
                style={
                  activeTab === tab.key
                    ? { color: "#891D1A", borderColor: "#891D1A" }
                    : { color: "#5E657B", borderColor: "transparent" }
                }
              >
                {tab.label}
                <span
                  className="text-xs px-1.5 py-0.5 rounded-full"
                  style={
                    activeTab === tab.key
                      ? { background: "rgba(137,29,26,0.1)", color: "#891D1A" }
                      : { background: "rgba(94,101,123,0.1)", color: "#5E657B" }
                  }
                >
                  {count}
                </span>
              </button>
            );
          })}
        </div>

        <div className="p-4 space-y-3">
          {filtered.length === 0 && (
            <div className="py-10 text-center">
              <BookOpen className="w-10 h-10 mx-auto mb-2 opacity-20" style={{ color: "#891D1A" }} />
              <p className="text-sm" style={{ color: "#5E657B" }}>
                {activeTab === "pending" ? "No pending requests." : activeTab === "approved" ? "No approved bookings yet." : "No bookings yet."}
              </p>
            </div>
          )}

          {filtered.map((b) => {
            const ss = statusStyle(b.status);
            const rs = roleStyle(b.userRole);
            const priority = priorityForRole(b.userRole);
            return (
              <div
                key={b.id}
                className="flex items-start gap-3 rounded-xl p-4 bg-background"
                style={{ borderLeft: `3px solid ${ss.bg}` }}
              >
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-2 flex-wrap">
                    <p className="text-sm font-semibold text-foreground" style={PLAYFAIR}>
                      {b.eventName}
                    </p>
                    <span
                      className="text-xs px-2 py-0.5 rounded-full font-medium"
                      style={{ background: rs.bg, color: rs.color }}
                    >
                      {b.userRole}
                    </span>
                    {/* Priority badge */}
                    <span
                      className="flex items-center gap-1 text-xs px-2 py-0.5 rounded-full font-semibold"
                      style={{ background: priority.color + "15", color: priority.color }}
                    >
                      <Shield className="w-3 h-3" />
                      {priority.label}
                    </span>
                    {/* Document indicator */}
                    {b.hasDoc && (
                      <span
                        className="flex items-center gap-1 text-xs px-2 py-0.5 rounded-full"
                        style={{ background: "rgba(94,101,123,0.1)", color: "#5E657B" }}
                      >
                        <FileText className="w-3 h-3" />
                        Doc
                      </span>
                    )}
                  </div>
                  <p className="text-xs mt-0.5" style={{ color: "#5E657B" }}>
                    {b.user} · {b.room}, {b.building}
                  </p>
                  <div className="flex items-center gap-1 mt-0.5 text-xs" style={{ color: "#5E657B" }}>
                    <Clock className="w-3 h-3" />
                    {b.date} · {b.time}
                  </div>
                </div>
                <div className="flex items-center gap-2 flex-shrink-0 pt-0.5">
                  <span
                    className="text-xs px-2.5 py-1 rounded-full text-white font-medium"
                    style={{ background: ss.bg }}
                  >
                    {ss.label}
                  </span>
                  <button
                    className="text-xs font-medium px-2.5 py-1 rounded-lg border transition-colors"
                    style={{ borderColor: "rgba(137,29,26,0.25)", color: "#5E657B" }}
                  >
                    View Details
                  </button>
                  {b.status === "pending" && (
                    <button className="text-xs font-medium" style={{ color: "#891D1A" }}>
                      Cancel
                    </button>
                  )}
                </div>
              </div>
            );
          })}
        </div>
      </div>
    </div>
  );
}
