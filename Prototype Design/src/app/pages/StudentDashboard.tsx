import { useMemo, useState } from "react";
import { useNavigate } from "react-router";
import { Users, CheckCircle, XCircle, Clock, Plus, BookOpen, ChevronRight } from "lucide-react";

const PLAYFAIR = { fontFamily: "'Playfair Display', serif" } as const;
const DM_SANS = { fontFamily: "'DM Sans', sans-serif" } as const;

type BookingStatus = "pending" | "approved" | "rejected" | "cancelled";

type Booking = {
  id: number;
  room: string;
  building: string;
  event: string;
  date: string;
  time: string;
  status: BookingStatus;
};

const initialBookings: Booking[] = [
  { id: 1, room: "Lab C-105", building: "Building C", event: "Study Group Session", date: "Apr 2, 2026", time: "3:00 PM – 5:00 PM", status: "pending" },
  { id: 2, room: "Room B-205", building: "Building B", event: "Project Meeting", date: "Apr 5, 2026", time: "2:00 PM – 4:00 PM", status: "approved" },
  { id: 3, room: "Room D-202", building: "Building D", event: "Team Presentation", date: "Mar 28, 2026", time: "1:00 PM – 2:00 PM", status: "rejected" },
];

const todayRooms = [
  { id: 1, name: "Room A-301", capacity: 30, status: "available" },
  { id: 2, name: "Lab C-105", capacity: 25, status: "available" },
  { id: 3, name: "Auditorium B", capacity: 200, status: "booked" },
  { id: 4, name: "Room E-101", capacity: 20, status: "available" },
  { id: 5, name: "Room B-205", capacity: 45, status: "available" },
];

const notifications = [
  { id: 1, type: "success", message: "Room B-205 booking approved", time: "2 hr ago" },
  { id: 2, type: "error", message: "Booking for Room D-202 was rejected", time: "1 day ago" },
  { id: 3, type: "pending", message: "Lab C-105 awaiting approval", time: "2 days ago" },
];

function statusStyle(status: BookingStatus) {
  switch (status) {
    case "approved": return { bg: "#3B6E4A", label: "Approved" };
    case "pending": return { bg: "#B8860B", label: "Pending" };
    case "rejected": return { bg: "#891D1A", label: "Rejected" };
    case "cancelled": return { bg: "#5E657B", label: "Cancelled" };
  }
}

function statusBorderColor(status: BookingStatus) {
  switch (status) {
    case "approved": return "#3B6E4A";
    case "pending": return "#B8860B";
    case "rejected": return "#891D1A";
    case "cancelled": return "#5E657B";
  }
}

export function StudentDashboard() {
  const navigate = useNavigate();
  const [activeTab, setActiveTab] = useState<"all" | BookingStatus>("all");
  const [bookings] = useState<Booking[]>(initialBookings);

  const filteredBookings = useMemo(() => {
    if (activeTab === "all") return bookings;
    return bookings.filter((b) => b.status === activeTab);
  }, [bookings, activeTab]);

  const tabs: { key: "all" | BookingStatus; label: string }[] = [
    { key: "all", label: "All" },
    { key: "pending", label: "Pending" },
    { key: "approved", label: "Approved" },
    { key: "rejected", label: "Rejected" },
    { key: "cancelled", label: "Cancelled" },
  ];

  return (
    <div className="space-y-6" style={DM_SANS}>
      {/* Header */}
      <div className="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h1 style={{ ...PLAYFAIR, fontSize: 28, fontWeight: 600 }} className="text-foreground">
            Student Dashboard
          </h1>
          <p className="text-sm mt-1" style={{ color: "#5E657B" }}>
            Find and reserve available classrooms
          </p>
        </div>
        <button
          onClick={() => navigate("/student/new-booking")}
          className="flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium text-[#F1E6D2]"
          style={{ background: "#891D1A" }}
          onMouseEnter={(e) => (e.currentTarget.style.background = "#210706")}
          onMouseLeave={(e) => (e.currentTarget.style.background = "#891D1A")}
        >
          <Plus className="w-4 h-4" />
          New Booking
        </button>
      </div>

      {/* Quick Availability Strip */}
      <div className="bg-card rounded-xl shadow-sm overflow-hidden">
        <div className="px-5 py-4 border-b border-border flex items-center justify-between">
          <h3 style={{ ...PLAYFAIR, fontSize: 16, fontWeight: 600 }} className="text-foreground">
            Today's Rooms
          </h3>
          <button
            className="text-sm font-medium flex items-center gap-1"
            style={{ color: "#891D1A" }}
            onClick={() => navigate("/student/rooms")}
          >
            View all <ChevronRight className="w-4 h-4" />
          </button>
        </div>
        <div className="p-4 flex gap-3 overflow-x-auto pb-4">
          {todayRooms.map((room) => (
            <div
              key={room.id}
              className="flex-shrink-0 w-44 rounded-xl p-4 border"
              style={{
                background: "#FFFFFF",
                borderColor: "rgba(137,29,26,0.12)",
                borderLeft: `3px solid ${room.status === "available" ? "#3B6E4A" : "#891D1A"}`,
              }}
            >
              <p className="text-sm font-semibold text-foreground truncate">{room.name}</p>
              <div className="flex items-center gap-1 mt-1 text-xs" style={{ color: "#5E657B" }}>
                <Users className="w-3 h-3" /> {room.capacity}
              </div>
              <div className="flex items-center justify-between mt-3">
                <span
                  className="text-xs px-2 py-0.5 rounded-full text-white"
                  style={{ background: room.status === "available" ? "#3B6E4A" : "#891D1A" }}
                >
                  {room.status === "available" ? "Available" : "Booked"}
                </span>
                {room.status === "available" && (
                  <button
                    className="text-xs font-medium"
                    style={{ color: "#891D1A" }}
                    onClick={() => navigate("/student/new-booking", { state: { roomName: room.name } })}
                  >
                    Book
                  </button>
                )}
              </div>
            </div>
          ))}
        </div>
      </div>

      <div className="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {/* My Bookings */}
        <div className="xl:col-span-2 bg-card rounded-xl shadow-sm overflow-hidden">
          <div className="px-5 pt-4 border-b border-border">
            <div className="flex items-center gap-2 mb-0">
              <BookOpen className="w-4 h-4" style={{ color: "#891D1A" }} />
              <h3 style={{ ...PLAYFAIR, fontSize: 16, fontWeight: 600 }} className="text-foreground">
                My Bookings
              </h3>
            </div>
            <div className="flex gap-0 mt-3 -mb-px">
              {tabs.map((tab) => (
                <button
                  key={tab.key}
                  onClick={() => setActiveTab(tab.key)}
                  className="px-3 py-2 text-sm border-b-2 transition-colors"
                  style={
                    activeTab === tab.key
                      ? { color: "#891D1A", borderColor: "#891D1A" }
                      : { color: "#5E657B", borderColor: "transparent" }
                  }
                >
                  {tab.label}
                </button>
              ))}
            </div>
          </div>

          <div className="p-4 space-y-3">
            {filteredBookings.length === 0 && (
              <div className="py-10 text-center">
                <BookOpen className="w-10 h-10 mx-auto mb-2 opacity-30" style={{ color: "#891D1A" }} />
                <p className="text-sm text-[#5E657B]">No bookings yet</p>
                <button
                  className="mt-3 text-sm font-medium"
                  style={{ color: "#891D1A" }}
                  onClick={() => navigate("/student/new-booking")}
                >
                  Book a Room →
                </button>
              </div>
            )}
            {filteredBookings.map((booking) => {
              const style = statusStyle(booking.status);
              return (
                <div
                  key={booking.id}
                  className="flex items-center gap-3 rounded-xl p-4 bg-background"
                  style={{ borderLeft: `3px solid ${statusBorderColor(booking.status)}` }}
                >
                  <div className="flex-1 min-w-0">
                    <p className="text-sm font-semibold text-foreground" style={PLAYFAIR}>
                      {booking.room}
                    </p>
                    <p className="text-xs mt-0.5" style={{ color: "#5E657B" }}>
                      {booking.building} · {booking.event}
                    </p>
                    <p className="text-xs mt-0.5" style={{ color: "#5E657B" }}>
                      {booking.date} · {booking.time}
                    </p>
                  </div>
                  <span
                    className="text-xs px-2.5 py-1 rounded-full text-white font-medium flex-shrink-0"
                    style={{ background: style.bg }}
                  >
                    {style.label}
                  </span>
                  {booking.status === "pending" && (
                    <button className="text-xs text-[#5E657B] hover:text-[#891D1A] flex-shrink-0">
                      Cancel
                    </button>
                  )}
                </div>
              );
            })}
          </div>
        </div>

        {/* Notifications */}
        <div className="bg-card rounded-xl shadow-sm overflow-hidden">
          <div className="px-5 py-4 border-b border-border">
            <h3 style={{ ...PLAYFAIR, fontSize: 16, fontWeight: 600 }} className="text-foreground">
              Notifications
            </h3>
          </div>
          <div className="p-4 space-y-3">
            {notifications.map((n) => {
              const icon =
                n.type === "success" ? (
                  <CheckCircle className="w-5 h-5" style={{ color: "#3B6E4A" }} />
                ) : n.type === "error" ? (
                  <XCircle className="w-5 h-5" style={{ color: "#891D1A" }} />
                ) : (
                  <Clock className="w-5 h-5" style={{ color: "#B8860B" }} />
                );
              return (
                <div key={n.id} className="flex gap-3">
                  <div className="flex-shrink-0 mt-0.5">{icon}</div>
                  <div>
                    <p className="text-sm text-foreground">{n.message}</p>
                    <p className="text-xs mt-0.5" style={{ color: "#5E657B" }}>{n.time}</p>
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      </div>
    </div>
  );
}
