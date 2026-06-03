import { useState } from "react";
import { useNavigate } from "react-router";
import { Check, X, Clock, AlertCircle, Users, ChevronRight, Plus, Calendar } from "lucide-react";

const PLAYFAIR = { fontFamily: "'Playfair Display', serif" } as const;
const DM_SANS = { fontFamily: "'DM Sans', sans-serif" } as const;

type RequestItem = {
  id: number;
  room: string;
  requester: string;
  role: "Student" | "Club";
  event: string;
  date: string;
  time: string;
  priority: "low" | "medium";
};

type Reservation = {
  id: number;
  room: string;
  course: string;
  date: string;
  time: string;
  recurring: string;
};

const initialRequests: RequestItem[] = [
  { id: 1, room: "Lab C-105", requester: "Michael Chen", role: "Student", event: "Study Group Session", date: "Apr 2, 2026", time: "3:00 PM – 5:00 PM", priority: "low" },
  { id: 2, room: "Room E-101", requester: "Dance Club", role: "Club", event: "Dance Practice", date: "Apr 5, 2026", time: "4:00 PM – 6:00 PM", priority: "medium" },
  { id: 3, room: "Room B-205", requester: "Lisa Anderson", role: "Student", event: "Tutorial Session", date: "Apr 4, 2026", time: "11:00 AM – 12:00 PM", priority: "low" },
];

const initialReservations: Reservation[] = [
  { id: 1, room: "Room A-301", course: "Math 101", date: "Apr 1, 2026", time: "10:00 AM – 12:00 PM", recurring: "Weekly" },
  { id: 2, room: "Lab C-106", course: "Physics Lab", date: "Apr 2, 2026", time: "9:00 AM – 12:00 PM", recurring: "Weekly" },
  { id: 3, room: "Auditorium B", course: "Guest Lecture Series", date: "Apr 4, 2026", time: "1:00 PM – 3:00 PM", recurring: "One-time" },
];

const todayRooms = [
  { id: 1, name: "Room A-301", capacity: 30, status: "available" },
  { id: 2, name: "Lab C-105", capacity: 25, status: "booked" },
  { id: 3, name: "Auditorium B", capacity: 200, status: "available" },
  { id: 4, name: "Room E-101", capacity: 20, status: "available" },
];

const weekDays = ["Mon", "Tue", "Wed", "Thu", "Fri"];
const timeSlots = ["9", "10", "11", "12", "1"];
const calendarBookings = [
  { day: 0, slot: 0, course: "Math 101" },
  { day: 1, slot: 1, course: "Physics Lab" },
  { day: 2, slot: 2, course: "Math 101" },
  { day: 3, slot: 0, course: "Guest Lecture" },
];

export function FacultyDashboard() {
  const navigate = useNavigate();
  const [pendingRequests, setPendingRequests] = useState<RequestItem[]>(initialRequests);
  const [reservations, setReservations] = useState<Reservation[]>(initialReservations);

  const handleApprove = (id: number) => {
    const item = pendingRequests.find((r) => r.id === id);
    if (!item) return;
    setPendingRequests((prev) => prev.filter((r) => r.id !== id));
    setReservations((prev) => [
      { id: Date.now(), room: item.room, course: item.event, date: item.date, time: item.time, recurring: "Approved" },
      ...prev,
    ]);
    alert(`Approved: ${item.event}`);
  };

  const handleReject = (id: number) => {
    const item = pendingRequests.find((r) => r.id === id);
    if (!item) return;
    setPendingRequests((prev) => prev.filter((r) => r.id !== id));
    alert(`Rejected: ${item.event}`);
  };

  const priorityStyle = (priority: string, role: string) => {
    if (priority === "medium") return { bg: "#5E657B", label: `MEDIUM — ${role}` };
    return { bg: "#B8860B", label: `NORMAL — ${role}` };
  };

  return (
    <div className="space-y-6" style={DM_SANS}>
      <div className="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h1 style={{ ...PLAYFAIR, fontSize: 28, fontWeight: 600 }} className="text-foreground">
            Faculty Dashboard
          </h1>
          <p className="text-sm mt-1" style={{ color: "#5E657B" }}>
            Manage your classes and review booking requests
          </p>
        </div>
        <button
          onClick={() => navigate("/faculty/new-booking")}
          className="flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium text-[#F1E6D2]"
          style={{ background: "#891D1A" }}
          onMouseEnter={(e) => (e.currentTarget.style.background = "#210706")}
          onMouseLeave={(e) => (e.currentTarget.style.background = "#891D1A")}
        >
          <Plus className="w-4 h-4" />
          Quick Reserve
        </button>
      </div>

      {/* Priority Notice */}
      <div
        className="rounded-xl p-4 flex items-start gap-3"
        style={{ background: "rgba(137,29,26,0.06)", border: "1px solid rgba(137,29,26,0.2)" }}
      >
        <AlertCircle className="w-5 h-5 mt-0.5 flex-shrink-0" style={{ color: "#891D1A" }} />
        <div>
          <p className="text-sm font-semibold" style={{ color: "#891D1A" }}>
            Faculty Priority Booking Active
          </p>
          <p className="text-sm mt-0.5" style={{ color: "#5E657B" }}>
            Your booking requests have high priority and are typically approved first.
          </p>
        </div>
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
            onClick={() => navigate("/faculty/rooms")}
          >
            View all <ChevronRight className="w-4 h-4" />
          </button>
        </div>
        <div className="p-4 flex gap-3 overflow-x-auto pb-4">
          {todayRooms.map((room) => (
            <div
              key={room.id}
              className="flex-shrink-0 w-44 rounded-xl p-4"
              style={{
                background: "#FFFFFF",
                borderLeft: `3px solid ${room.status === "available" ? "#3B6E4A" : "#891D1A"}`,
                border: "1px solid rgba(137,29,26,0.1)",
                borderLeftWidth: 3,
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
                    onClick={() => navigate("/faculty/new-booking", { state: { roomName: room.name } })}
                  >
                    Book
                  </button>
                )}
              </div>
            </div>
          ))}
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Pending Requests */}
        <div className="lg:col-span-2 bg-card rounded-xl shadow-sm overflow-hidden">
          <div className="px-5 py-4 border-b border-border">
            <h3 style={{ ...PLAYFAIR, fontSize: 16, fontWeight: 600 }} className="text-foreground">
              Pending Student Requests
            </h3>
            <p className="text-xs mt-1" style={{ color: "#5E657B" }}>
              Review and action booking requests from students and clubs
            </p>
          </div>

          <div className="p-4 space-y-3">
            {pendingRequests.map((req) => {
              const ps = priorityStyle(req.priority, req.role);
              return (
                <div
                  key={req.id}
                  className="rounded-xl p-4"
                  style={{ border: "1px solid rgba(137,29,26,0.12)", background: "#FFFFFF" }}
                >
                  <div className="flex items-start justify-between gap-3 mb-3">
                    <div>
                      <p className="text-sm font-semibold text-foreground">{req.event}</p>
                      <p className="text-xs mt-0.5" style={{ color: "#5E657B" }}>{req.requester}</p>
                    </div>
                    <span
                      className="text-xs px-2 py-0.5 rounded-full text-white font-medium flex-shrink-0"
                      style={{ background: ps.bg }}
                    >
                      {ps.label}
                    </span>
                  </div>

                  <div className="grid grid-cols-2 gap-2 text-xs mb-3" style={{ color: "#5E657B" }}>
                    <div><span>Room: </span><span className="font-medium text-foreground">{req.room}</span></div>
                    <div><span>Date: </span><span className="font-medium text-foreground">{req.date}</span></div>
                    <div className="col-span-2 flex items-center gap-1">
                      <Clock className="w-3 h-3" /> {req.time}
                    </div>
                  </div>

                  <div className="flex gap-2">
                    <button
                      onClick={() => handleReject(req.id)}
                      className="flex-1 py-1.5 rounded-lg text-sm font-medium border flex items-center justify-center gap-1 transition-colors hover:bg-red-50"
                      style={{ borderColor: "#891D1A", color: "#891D1A" }}
                    >
                      <X className="w-4 h-4" /> Reject
                    </button>
                    <button
                      onClick={() => handleApprove(req.id)}
                      className="flex-1 py-1.5 rounded-lg text-sm font-medium text-white flex items-center justify-center gap-1 transition-colors"
                      style={{ background: "#3B6E4A" }}
                      onMouseEnter={(e) => (e.currentTarget.style.background = "#2d5437")}
                      onMouseLeave={(e) => (e.currentTarget.style.background = "#3B6E4A")}
                    >
                      <Check className="w-4 h-4" /> Approve
                    </button>
                  </div>
                </div>
              );
            })}
            {pendingRequests.length === 0 && (
              <div className="py-8 text-center text-sm" style={{ color: "#5E657B" }}>
                No pending requests.
              </div>
            )}
          </div>
        </div>

        {/* Week overview */}
        <div className="bg-card rounded-xl shadow-sm overflow-hidden">
          <div className="px-5 py-4 border-b border-border flex items-center gap-2">
            <Calendar className="w-4 h-4" style={{ color: "#891D1A" }} />
            <h3 style={{ ...PLAYFAIR, fontSize: 16, fontWeight: 600 }} className="text-foreground">
              This Week
            </h3>
          </div>
          <div className="p-4">
            <div className="space-y-2">
              {weekDays.map((day, di) => (
                <div key={day} className="flex items-center gap-2">
                  <div className="w-10 text-xs font-medium" style={{ color: "#5E657B" }}>{day}</div>
                  <div className="flex-1 flex gap-1">
                    {timeSlots.map((_, si) => {
                      const b = calendarBookings.find((cb) => cb.day === di && cb.slot === si);
                      return (
                        <div
                          key={si}
                          className="h-8 flex-1 rounded"
                          style={
                            b
                              ? { background: "rgba(137,29,26,0.15)", border: "1px solid rgba(137,29,26,0.3)" }
                              : { background: "rgba(94,101,123,0.08)", border: "1px solid rgba(94,101,123,0.15)" }
                          }
                          title={b?.course}
                        />
                      );
                    })}
                  </div>
                </div>
              ))}
            </div>
            <div className="mt-3 flex gap-3 text-xs" style={{ color: "#5E657B" }}>
              <div className="flex items-center gap-1">
                <div className="w-3 h-3 rounded" style={{ background: "rgba(137,29,26,0.3)" }} />
                Booked
              </div>
              <div className="flex items-center gap-1">
                <div className="w-3 h-3 rounded" style={{ background: "rgba(94,101,123,0.15)" }} />
                Free
              </div>
            </div>

            {/* Reservations */}
            <div className="mt-5 space-y-2">
              <p className="text-xs font-semibold" style={{ color: "#5E657B" }}>MY CLASSES</p>
              {reservations.slice(0, 3).map((r) => (
                <div
                  key={r.id}
                  className="p-3 rounded-lg"
                  style={{ background: "rgba(137,29,26,0.05)", border: "1px solid rgba(137,29,26,0.1)" }}
                >
                  <p className="text-xs font-semibold text-foreground">{r.course}</p>
                  <p className="text-xs mt-0.5" style={{ color: "#5E657B" }}>{r.room}</p>
                  <p className="text-xs mt-0.5" style={{ color: "#5E657B" }}>{r.date} · {r.time}</p>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
