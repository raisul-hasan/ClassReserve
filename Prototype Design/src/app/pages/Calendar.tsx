import { useEffect, useState } from "react";
import { ChevronLeft, ChevronRight, X } from "lucide-react";
import { getCalendarEvents } from "../services/classReserveService";
import { useAuth } from "../context/AuthContext";
import type { CalendarConflictStatus, CalendarEvent, CalendarEventType } from "../types/classReserve";

const PLAYFAIR = { fontFamily: "'Playfair Display', serif" } as const;
const DM_SANS = { fontFamily: "'DM Sans', sans-serif" } as const;

type ViewMode = "month" | "week" | "day";
type FilterType = "all" | "faculty" | "club" | "student" | "maintenance" | "mine" | "pending" | "approved";

const MONTH_NAMES = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
const DAY_NAMES = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];

const FILTER_PILLS: { key: FilterType; label: string }[] = [
  { key: "all", label: "All Events" },
  { key: "faculty", label: "Faculty Reservations" },
  { key: "club", label: "Club Events" },
  { key: "student", label: "Student Bookings" },
  { key: "maintenance", label: "Maintenance" },
  { key: "mine", label: "My Bookings" },
  { key: "pending", label: "Pending" },
  { key: "approved", label: "Approved" },
];

function timeLabel(event: CalendarEvent) {
  if (!event.startTime && !event.endTime) return "All day";
  return `${event.startTime || "--"} - ${event.endTime || "--"}`;
}

function eventFilterType(event: CalendarEvent): Exclude<FilterType, "all" | "mine" | "pending" | "approved"> {
  if (event.eventType === "maintenance") return "maintenance";
  if (event.eventType === "faculty_reservation") return "faculty";
  if (event.eventType === "club_event") return "club";
  return "student";
}

function eventTypeLabel(type: CalendarEventType) {
  switch (type) {
    case "faculty_reservation": return "Faculty Reservation";
    case "club_event": return "Club Event";
    case "student_booking": return "Student Booking";
    case "maintenance": return "Maintenance";
  }
}

function conflictLabel(status: CalendarConflictStatus) {
  switch (status) {
    case "conflict_detected": return "Conflict detected";
    case "maintenance_conflict": return "Maintenance conflict";
    default: return "No conflict";
  }
}

function eventChipColor(event: CalendarEvent) {
  const type = eventFilterType(event);
  if (event.conflictStatus === "conflict_detected") return { bg: "#B8860B", text: "#fff", border: "#6F4E00" };
  if (event.status === "pending") return { bg: "#F1E6D2", text: "#891D1A", border: "#B8860B" };
  switch (type) {
    case "faculty": return { bg: "#891D1A", text: "#fff", border: "#891D1A" };
    case "club": return { bg: "#5E657B", text: "#fff", border: "#5E657B" };
    case "student": return { bg: "#B8860B", text: "#fff", border: "#B8860B" };
    case "maintenance": return { bg: "#210706", text: "#F1E6D2", border: "#210706" };
  }
}

function statusLabel(status: string) {
  switch (status) {
    case "approved": return { label: "Approved", color: "#3B6E4A" };
    case "pending": return { label: "Pending Review", color: "#B8860B" };
    case "maintenance": return { label: "Maintenance Block", color: "#210706" };
    default: return { label: status, color: "#5E657B" };
  }
}

function getDaysInMonth(year: number, month: number) {
  return new Date(year, month + 1, 0).getDate();
}

function getFirstDayOfMonth(year: number, month: number) {
  return new Date(year, month, 1).getDay();
}

export function Calendar() {
  const today = new Date();
  const { user } = useAuth();
  const [events, setEvents] = useState<CalendarEvent[]>([]);
  const [viewMode, setViewMode] = useState<ViewMode>("month");
  const [currentYear, setCurrentYear] = useState(2026);
  const [currentMonth, setCurrentMonth] = useState(5);
  const [selectedDay, setSelectedDay] = useState<number | null>(null);
  const [drawerOpen, setDrawerOpen] = useState(false);
  const [activeFilter, setActiveFilter] = useState<FilterType>("all");
  const [selectedEvent, setSelectedEvent] = useState<CalendarEvent | null>(null);

  useEffect(() => {
    let mounted = true;
    getCalendarEvents({ role: user?.role, userId: user?.id, email: user?.email }).then((items) => {
      if (mounted) setEvents(items);
    });
    return () => { mounted = false; };
  }, [user?.role, user?.id, user?.email]);

  const daysInMonth = getDaysInMonth(currentYear, currentMonth);
  const firstDay = getFirstDayOfMonth(currentYear, currentMonth);
  const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, "0")}-${String(today.getDate()).padStart(2, "0")}`;

  const prevMonth = () => {
    if (currentMonth === 0) { setCurrentYear((y) => y - 1); setCurrentMonth(11); }
    else setCurrentMonth((m) => m - 1);
  };
  const nextMonth = () => {
    if (currentMonth === 11) { setCurrentYear((y) => y + 1); setCurrentMonth(0); }
    else setCurrentMonth((m) => m + 1);
  };

  const dayStr = (day: number) =>
    `${currentYear}-${String(currentMonth + 1).padStart(2, "0")}-${String(day).padStart(2, "0")}`;

  const isOwnEvent = (event: CalendarEvent) => {
    if (!user) return false;
    return Boolean(
      (event.requesterId && user.id && event.requesterId === user.id) ||
      (event.requesterEmail && event.requesterEmail.toLowerCase() === user.email.toLowerCase())
    );
  };

  const isRoleVisible = (event: CalendarEvent) => {
    if (!user || user.role === "admin") return true;
    if (event.status === "maintenance" || event.status === "approved") return true;
    if (isOwnEvent(event) && event.status === "pending") return true;
    if (user.role === "faculty" && event.status === "pending" && ["student", "club"].includes(event.requesterRole)) return true;
    return false;
  };

  const filteredEvents = events.filter((event) => {
    if (!isRoleVisible(event)) return false;
    if (activeFilter === "all") return true;
    if (activeFilter === "mine") return isOwnEvent(event);
    if (activeFilter === "pending") return event.status === "pending";
    if (activeFilter === "approved") return event.status === "approved";
    return eventFilterType(event) === activeFilter;
  });

  const eventsForDay = (day: number) => filteredEvents.filter((e) => e.date === dayStr(day));

  const selectedEvents = selectedDay ? eventsForDay(selectedDay) : [];

  const handleDayClick = (day: number) => {
    setSelectedDay(day);
    setSelectedEvent(null);
    setDrawerOpen(true);
  };

  const handleEventClick = (ev: CalendarEvent, e: React.MouseEvent) => {
    e.stopPropagation();
    setSelectedEvent(ev);
  };

  // Build calendar grid
  const totalCells = Math.ceil((firstDay + daysInMonth) / 7) * 7;
  const cells: (number | null)[] = [];
  for (let i = 0; i < firstDay; i++) cells.push(null);
  for (let d = 1; d <= daysInMonth; d++) cells.push(d);
  while (cells.length < totalCells) cells.push(null);

  return (
    <div className="space-y-5 relative" style={DM_SANS}>
      <div className="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h1 style={{ ...PLAYFAIR, fontSize: 28, fontWeight: 600 }} className="text-foreground">
            Calendar
          </h1>
          <p className="text-sm mt-1" style={{ color: "#5E657B" }}>
            Room bookings and availability overview
          </p>
        </div>

        <div className="flex items-center gap-3 flex-wrap">
          {/* Legend */}
          <div className="flex items-center gap-3">
            {[
              { label: "Faculty", color: "#891D1A" },
              { label: "Club", color: "#5E657B" },
              { label: "Student", color: "#B8860B" },
              { label: "Maintenance", color: "#210706" },
            ].map((l) => (
              <div key={l.label} className="flex items-center gap-1">
                <div className="w-2.5 h-2.5 rounded-sm" style={{ background: l.color }} />
                <span className="text-xs" style={{ color: "#5E657B" }}>{l.label}</span>
              </div>
            ))}
          </div>

          {/* View toggle */}
          <div
            className="flex rounded-full overflow-hidden border"
            style={{ borderColor: "rgba(137,29,26,0.25)" }}
          >
            {(["month", "week", "day"] as ViewMode[]).map((v) => (
              <button
                key={v}
                onClick={() => setViewMode(v)}
                className="px-3 py-1.5 text-xs font-medium capitalize transition-colors"
                style={
                  viewMode === v
                    ? { background: "#891D1A", color: "#F1E6D2" }
                    : { color: "#5E657B" }
                }
              >
                {v}
              </button>
            ))}
          </div>
        </div>
      </div>

      {/* Filter pills */}
      <div className="flex items-center gap-2 flex-wrap">
        {FILTER_PILLS.map((pill) => {
          const active = activeFilter === pill.key;
          return (
            <button
              key={pill.key}
              onClick={() => setActiveFilter(pill.key)}
              className="px-3 py-1.5 rounded-full text-xs font-medium border transition-all"
              style={
                active
                  ? { background: "#891D1A", borderColor: "#891D1A", color: "#fff" }
                  : { background: "transparent", borderColor: "rgba(137,29,26,0.25)", color: "#5E657B" }
              }
            >
              {pill.label}
            </button>
          );
        })}
      </div>

      {/* Nav */}
      <div className="bg-card rounded-xl shadow-sm overflow-hidden">
        <div className="flex items-center justify-between px-5 py-4 border-b border-border">
          <button onClick={prevMonth} className="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-[#891D1A]/10" style={{ color: "#5E657B" }}>
            <ChevronLeft className="w-4 h-4" />
          </button>
          <h2 style={{ ...PLAYFAIR, fontSize: 18, fontWeight: 600 }} className="text-foreground">
            {MONTH_NAMES[currentMonth]} {currentYear}
          </h2>
          <button onClick={nextMonth} className="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-[#891D1A]/10" style={{ color: "#5E657B" }}>
            <ChevronRight className="w-4 h-4" />
          </button>
        </div>

        {/* Day headers */}
        <div className="grid grid-cols-7 border-b border-border">
          {DAY_NAMES.map((d) => (
            <div key={d} className="py-2 text-center text-xs font-semibold" style={{ color: "#5E657B" }}>
              {d}
            </div>
          ))}
        </div>

        {/* Calendar cells */}
        <div className="grid grid-cols-7">
          {cells.map((day, idx) => {
            if (day === null) {
              return <div key={`empty-${idx}`} className="min-h-[80px] border-b border-r border-border" />;
            }
            const ds = dayStr(day);
            const isToday = ds === todayStr;
            const dayEvents = eventsForDay(day);
            const isSelected = selectedDay === day && drawerOpen;
            return (
              <div
                key={day}
                className="min-h-[80px] border-b border-r border-border p-1.5 cursor-pointer hover:bg-[#891D1A]/4 transition-colors"
                style={isSelected ? { background: "rgba(137,29,26,0.05)" } : undefined}
                onClick={() => handleDayClick(day)}
              >
                <div className="flex items-center justify-end mb-1">
                  <span
                    className="w-6 h-6 rounded-full flex items-center justify-center text-xs font-medium"
                    style={
                      isToday
                        ? { background: "#891D1A", color: "#fff" }
                        : { color: "#210706" }
                    }
                  >
                    {day}
                  </span>
                </div>
                <div className="space-y-0.5">
                  {dayEvents.slice(0, 2).map((ev) => {
                    const c = eventChipColor(ev);
                    return (
                      <div
                        key={ev.id}
                        className="text-xs px-1.5 py-0.5 rounded truncate cursor-pointer hover:opacity-80 transition-opacity border"
                        style={{ background: c.bg, color: c.text, borderColor: c.border }}
                        onClick={(e) => handleEventClick(ev, e)}
                        title={`${timeLabel(ev)} ${ev.title} - ${ev.roomName}`}
                      >
                        <span className="font-semibold">{ev.startTime}</span> {ev.title} · {ev.roomName}
                      </div>
                    );
                  })}
                  {dayEvents.length > 2 && (
                    <div className="text-xs" style={{ color: "#5E657B" }}>
                      +{dayEvents.length - 2} more
                    </div>
                  )}
                </div>
              </div>
            );
          })}
        </div>
      </div>

      {/* Side Drawer */}
      {drawerOpen && selectedDay && (
        <div className="fixed inset-0 z-40" onClick={() => { setDrawerOpen(false); setSelectedEvent(null); }}>
          <div
            className="absolute right-0 top-0 h-full w-80 bg-card shadow-2xl flex flex-col"
            style={{ borderLeft: "1px solid rgba(137,29,26,0.15)" }}
            onClick={(e) => e.stopPropagation()}
          >
            <div className="flex items-center justify-between px-5 py-4 border-b border-border">
              <h3 style={{ ...PLAYFAIR, fontSize: 16, fontWeight: 600 }} className="text-foreground">
                {selectedEvent ? selectedEvent.title : `${MONTH_NAMES[currentMonth]} ${selectedDay}`}
              </h3>
              <button
                onClick={() => { setDrawerOpen(false); setSelectedEvent(null); }}
                className="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-[#891D1A]/10"
                style={{ color: "#5E657B" }}
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            {selectedEvent ? (
              /* Event detail view */
              <div className="flex-1 overflow-y-auto p-5 space-y-4">
                <button
                  onClick={() => setSelectedEvent(null)}
                  className="text-xs flex items-center gap-1"
                  style={{ color: "#891D1A" }}
                >
                  ← Back to day view
                </button>
                <div
                  className="rounded-xl p-4 space-y-3"
                  style={{ background: eventChipColor(selectedEvent).bg + "10", border: `1px solid ${eventChipColor(selectedEvent).border}30` }}
                >
                  <div>
                    <p className="text-xs mb-1" style={{ color: "#5E657B" }}>Title</p>
                    <p className="text-sm font-semibold text-foreground">{selectedEvent.title}</p>
                  </div>
                  <div>
                    <p className="text-xs mb-1" style={{ color: "#5E657B" }}>Room</p>
                    <p className="text-sm font-semibold text-foreground">{selectedEvent.roomName}</p>
                  </div>
                  <div>
                    <p className="text-xs mb-1" style={{ color: "#5E657B" }}>Requester</p>
                    <p className="text-sm text-foreground">{selectedEvent.requesterName}</p>
                  </div>
                  <div>
                    <p className="text-xs mb-1" style={{ color: "#5E657B" }}>Requester Role</p>
                    <p className="text-sm text-foreground capitalize">{selectedEvent.requesterRole}</p>
                  </div>
                  <div>
                    <p className="text-xs mb-1" style={{ color: "#5E657B" }}>Date</p>
                    <p className="text-sm text-foreground">{selectedEvent.date}</p>
                  </div>
                  <div>
                    <p className="text-xs mb-1" style={{ color: "#5E657B" }}>Time</p>
                    <p className="text-sm text-foreground">{timeLabel(selectedEvent)}</p>
                  </div>
                  <div>
                    <p className="text-xs mb-1" style={{ color: "#5E657B" }}>Type</p>
                    <span
                      className="inline-block text-xs px-2 py-0.5 rounded-full text-white"
                      style={{ background: eventChipColor(selectedEvent).border }}
                    >
                      {eventTypeLabel(selectedEvent.eventType)}
                    </span>
                  </div>
                  <div>
                    <p className="text-xs mb-1" style={{ color: "#5E657B" }}>Status</p>
                    <span
                      className="inline-block text-xs px-2 py-0.5 rounded-full text-white"
                      style={{ background: statusLabel(selectedEvent.status).color }}
                    >
                      {statusLabel(selectedEvent.status).label}
                    </span>
                  </div>
                  <div>
                    <p className="text-xs mb-1" style={{ color: "#5E657B" }}>Priority</p>
                    <p className="text-sm font-semibold capitalize" style={{ color: eventChipColor(selectedEvent).border }}>
                      {selectedEvent.priority}
                    </p>
                  </div>
                  <div>
                    <p className="text-xs mb-1" style={{ color: "#5E657B" }}>Conflict Status</p>
                    <p className="text-sm text-foreground">{conflictLabel(selectedEvent.conflictStatus)}</p>
                  </div>
                </div>
              </div>
            ) : (
              /* Day events list */
              <div className="flex-1 overflow-y-auto p-4 space-y-3">
                {selectedEvents.length === 0 ? (
                  <div className="py-8 text-center text-sm" style={{ color: "#5E657B" }}>
                    No bookings on this day.
                  </div>
                ) : (
                  selectedEvents.map((ev) => {
                    const c = eventChipColor(ev);
                    const sl = statusLabel(ev.status);
                    return (
                      <div
                        key={ev.id}
                        className="rounded-xl p-4 cursor-pointer hover:opacity-90 transition-opacity"
                        style={{ background: c.bg + "12", borderLeft: `3px solid ${c.border}` }}
                        onClick={() => setSelectedEvent(ev)}
                      >
                        <p className="text-sm font-semibold text-foreground" style={PLAYFAIR}>{ev.title}</p>
                        <p className="text-xs mt-0.5" style={{ color: "#5E657B" }}>Room {ev.roomName}</p>
                        <p className="text-xs mt-0.5" style={{ color: "#5E657B" }}>{timeLabel(ev)}</p>
                        <div className="flex items-center gap-2 mt-2 flex-wrap">
                          <span
                            className="inline-block text-xs px-2 py-0.5 rounded-full text-white capitalize"
                            style={{ background: c.border }}
                          >
                            {eventTypeLabel(ev.eventType)}
                          </span>
                          <span
                            className="inline-block text-xs px-2 py-0.5 rounded-full text-white"
                            style={{ background: sl.color }}
                          >
                            {sl.label}
                          </span>
                        </div>
                        {ev.priority && (
                          <p className="text-xs mt-1.5 font-semibold capitalize" style={{ color: c.border }}>
                            Priority: {ev.priority}
                          </p>
                        )}
                        {ev.conflictStatus !== "no_conflict" && (
                          <p className="text-xs mt-1 font-semibold" style={{ color: "#B8860B" }}>
                            {conflictLabel(ev.conflictStatus)}
                          </p>
                        )}
                      </div>
                    );
                  })
                )}
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
}
