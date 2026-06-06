import { useEffect, useMemo, useState } from "react";
import { useSearchParams } from "react-router";
import { Clock, BookOpen, Shield, FileText, X } from "lucide-react";
import { cancelBooking as cancelBookingRequest, getMyBookings } from "../services/classReserveService";
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
  const [searchQuery, setSearchQuery] = useState("");
  const [bookings, setBookings] = useState<BookingRow[]>([]);
  const [selectedBooking, setSelectedBooking] = useState<BookingRow | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [searchParams] = useSearchParams();
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

  useEffect(() => {
    const bookingId = Number(searchParams.get("bookingId"));
    if (!bookingId || bookings.length === 0) return;
    const match = bookings.find((booking) => booking.id === bookingId);
    if (match) {
      setSelectedBooking(match);
    }
  }, [searchParams, bookings]);

  const filtered = useMemo(() => {
    const byStatus = activeTab === "all" ? bookings : bookings.filter((b) => b.status === activeTab);
    const q = searchQuery.trim().toLowerCase();
    if (!q) return byStatus;
    return byStatus.filter((b) => [b.eventName, b.user, b.room, b.building, b.date, b.time, b.status, b.userRole].some((value) => String(value).toLowerCase().includes(q)));
  }, [bookings, activeTab, searchQuery]);

  const cancelBooking = async (id: number) => {
    const target = bookings.find((booking) => booking.id === id);
    if (target?.status !== "pending") return;
    if (!window.confirm("Cancel this pending booking request?")) return;
    await cancelBookingRequest(id);
    setBookings((prev) => prev.map((booking) => booking.id === id ? { ...booking, status: "cancelled" } : booking));
    setSelectedBooking((prev) => prev?.id === id ? { ...prev, status: "cancelled" } : prev);
  };

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

      <div className="bg-card rounded-xl p-4 shadow-sm">
        <input
          value={searchQuery}
          onChange={(e) => setSearchQuery(e.target.value)}
          placeholder="Search bookings by event, room, requester, date, or status..."
          className="w-full px-3 py-2.5 rounded-lg border bg-white dark:bg-[#3A1210] dark:text-[#F1E6D2] outline-none focus:ring-2 focus:ring-[#891D1A]/30 text-sm"
          style={{ borderColor: "rgba(137,29,26,0.2)" }}
        />
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
                    onClick={() => setSelectedBooking(b)}
                    className="text-xs font-medium px-2.5 py-1 rounded-lg border transition-colors"
                    style={{ borderColor: "rgba(137,29,26,0.25)", color: "#5E657B" }}
                  >
                    View Details
                  </button>
                  {b.status === "pending" && (
                    <button onClick={() => cancelBooking(b.id)} className="text-xs font-medium" style={{ color: "#891D1A" }}>
                      Cancel
                    </button>
                  )}
                </div>
              </div>
            );
          })}
        </div>
      </div>

      {selectedBooking && (
        <div className="fixed inset-0 z-40" style={{ background: "rgba(33,7,6,0.4)" }} onClick={() => setSelectedBooking(null)}>
          <div className="absolute right-0 top-0 h-full w-96 bg-card shadow-2xl flex flex-col" style={{ borderLeft: "1px solid rgba(137,29,26,0.15)" }} onClick={(e) => e.stopPropagation()}>
            <div className="flex items-center justify-between px-5 py-4 border-b border-border">
              <h2 style={{ ...PLAYFAIR, fontSize: 18, fontWeight: 600 }} className="text-foreground">Booking Details</h2>
              <button onClick={() => setSelectedBooking(null)} className="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-[#891D1A]/10" style={{ color: "#5E657B" }}>
                <X className="w-4 h-4" />
              </button>
            </div>
            <div className="p-5 space-y-4">
              {[
                ["Event", selectedBooking.eventName],
                ["Requester", `${selectedBooking.user} (${selectedBooking.userRole})`],
                ["Room", `${selectedBooking.room}, ${selectedBooking.building}`],
                ["Date", selectedBooking.date],
                ["Time", selectedBooking.time],
                ["Status", statusStyle(selectedBooking.status).label],
                ["Priority", priorityForRole(selectedBooking.userRole).label],
                ["Document", selectedBooking.hasDoc ? "Attached" : "Not attached"],
              ].map(([label, value]) => (
                <div key={label}>
                  <p className="text-xs font-semibold mb-0.5" style={{ color: "#5E657B" }}>{label}</p>
                  <p className="text-sm text-foreground">{value}</p>
                </div>
              ))}
              {selectedBooking.status === "pending" && (
                <button onClick={() => cancelBooking(selectedBooking.id)} className="w-full py-2.5 rounded-lg text-sm font-medium text-white" style={{ background: "#891D1A" }}>
                  Cancel Request
                </button>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
