import { useEffect, useState } from "react";
import { ChevronLeft, ChevronRight, X } from "lucide-react";
import { getCalendarEvents } from "../services/classReserveService";
import type { CalendarEvent } from "../types/classReserve";

const PLAYFAIR = { fontFamily: "'Playfair Display', serif" } as const;
const DM_SANS = { fontFamily: "'DM Sans', sans-serif" } as const;

type ViewMode = "month" | "week" | "day";
type FilterType = "all" | "faculty" | "club" | "student" | "maintenance";

const MONTH_NAMES = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
const DAY_NAMES = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];

const FILTER_PILLS: { key: FilterType; label: string }[] = [
  { key: "all", label: "All Events" },
  { key: "faculty", label: "Faculty Reservations" },
  { key: "club", label: "Club Events" },
  { key: "student", label: "Student Bookings" },
  { key: "maintenance", label: "Maintenance" },
];

type CalendarUiEvent = {
  id: number;
  title: string;
  room: string;
  date: string;
  type: FilterType;
  time: string;
  status: string;
  priority: string;
};

const initialEvents: CalendarUiEvent[] = [
  { id: 1, title: "Math 101", room: "A-301", date: "2026-04-01", type: "faculty", time: "10:00 AM", status: "approved", priority: "HIGH" },
  { id: 2, title: "Club Meeting", room: "B-205", date: "2026-04-01", type: "club", time: "2:00 PM", status: "pending", priority: "MEDIUM" },
  { id: 3, title: "Physics Lab", room: "C-105", date: "2026-04-02", type: "faculty", time: "9:00 AM", status: "approved", priority: "HIGH" },
  { id: 4, title: "Study Group", room: "A-301", date: "2026-04-02", type: "student", time: "3:00 PM", status: "pending", priority: "STANDARD" },
  { id: 5, title: "CS Lecture", room: "D-202", date: "2026-04-03", type: "faculty", time: "11:00 AM", status: "approved", priority: "HIGH" },
  { id: 6, title: "Workshop", room: "D-202", date: "2026-04-03", type: "club", time: "2:00 PM", status: "approved", priority: "MEDIUM" },
  { id: 7, title: "Research Seminar", room: "Aud-B", date: "2026-04-03", type: "faculty", time: "2:00 PM", status: "approved", priority: "HIGH" },
  { id: 8, title: "Guest Lecture", room: "Aud-B", date: "2026-04-04", type: "faculty", time: "2:00 PM", status: "approved", priority: "HIGH" },
  { id: 9, title: "Maintenance", room: "D-202", date: "2026-04-10", type: "maintenance", time: "All day", status: "maintenance", priority: "" },
  { id: 10, title: "Tutorial", room: "E-101", date: "2026-04-05", type: "student", time: "4:00 PM", status: "approved", priority: "STANDARD" },
  { id: 11, title: "Dance Practice", room: "E-101", date: "2026-04-05", type: "club", time: "6:00 PM", status: "pending", priority: "MEDIUM" },
  { id: 12, title: "Department Meeting", room: "A-301", date: "2026-04-06", type: "faculty", time: "10:00 AM", status: "approved", priority: "HIGH" },
];

function timeLabel(event: CalendarEvent) {
  if (!event.startTime && !event.endTime) return "All day";
  return `${event.startTime || "--"} - ${event.endTime || "--"}`;
}

function toUiEvent(event: CalendarEvent): CalendarUiEvent {
  const type = event.status === "maintenance" ? "maintenance" : (event.ownerRole || "student");
  return {
    id: event.id,
    title: event.title,
    room: event.roomName,
    date: event.date,
    type,
    time: timeLabel(event),
    status: event.status,
    priority: type === "faculty" ? "HIGH" : type === "club" ? "MEDIUM" : type === "student" ? "STANDARD" : "",
  };
}

function eventChipColor(type: string) {
  switch (type) {
    case "faculty": return { bg: "#891D1A", text: "#fff" };
    case "club": return { bg: "#5E657B", text: "#fff" };
    case "student": return { bg: "#B8860B", text: "#fff" };
    case "maintenance": return { bg: "#210706", text: "#F1E6D2" };
    default: return { bg: "#5E657B", text: "#fff" };
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
  const [events, setEvents] = useState<CalendarUiEvent[]>(initialEvents);
  const [viewMode, setViewMode] = useState<ViewMode>("month");
  const [currentYear, setCurrentYear] = useState(today.getFullYear());
  const [currentMonth, setCurrentMonth] = useState(today.getMonth());
  const [selectedDay, setSelectedDay] = useState<number | null>(null);
  const [drawerOpen, setDrawerOpen] = useState(false);
  const [activeFilter, setActiveFilter] = useState<FilterType>("all");
  const [selectedEvent, setSelectedEvent] = useState<CalendarUiEvent | null>(null);

  useEffect(() => {
    let mounted = true;
    getCalendarEvents().then((items) => {
      if (mounted) setEvents(items.map(toUiEvent));
    });
    return () => { mounted = false; };
  }, []);

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

  const filteredEvents = activeFilter === "all"
    ? events
    : events.filter((e) => e.type === activeFilter);

  const eventsForDay = (day: number) => filteredEvents.filter((e) => e.date === dayStr(day));

  const selectedEvents = selectedDay ? eventsForDay(selectedDay) : [];

  const handleDayClick = (day: number) => {
    setSelectedDay(day);
    setSelectedEvent(null);
    setDrawerOpen(true);
  };

  const handleEventClick = (ev: CalendarUiEvent, e: React.MouseEvent) => {
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
                    const c = eventChipColor(ev.type);
                    return (
                      <div
                        key={ev.id}
                        className="text-xs px-1.5 py-0.5 rounded truncate cursor-pointer hover:opacity-80 transition-opacity"
                        style={{ background: c.bg, color: c.text }}
                        onClick={(e) => handleEventClick(ev, e)}
                      >
                        {ev.title}
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
                  style={{ background: eventChipColor(selectedEvent.type).bg + "10", border: `1px solid ${eventChipColor(selectedEvent.type).bg}30` }}
                >
                  <div>
                    <p className="text-xs mb-1" style={{ color: "#5E657B" }}>Room</p>
                    <p className="text-sm font-semibold text-foreground">{selectedEvent.room}</p>
                  </div>
                  <div>
                    <p className="text-xs mb-1" style={{ color: "#5E657B" }}>Time</p>
                    <p className="text-sm text-foreground">{selectedEvent.time}</p>
                  </div>
                  <div>
                    <p className="text-xs mb-1" style={{ color: "#5E657B" }}>Type</p>
                    <span
                      className="inline-block text-xs px-2 py-0.5 rounded-full text-white capitalize"
                      style={{ background: eventChipColor(selectedEvent.type).bg }}
                    >
                      {selectedEvent.type}
                    </span>
                  </div>
                  {selectedEvent.status && (
                    <div>
                      <p className="text-xs mb-1" style={{ color: "#5E657B" }}>Status</p>
                      <span
                        className="inline-block text-xs px-2 py-0.5 rounded-full text-white"
                        style={{ background: statusLabel(selectedEvent.status).color }}
                      >
                        {statusLabel(selectedEvent.status).label}
                      </span>
                    </div>
                  )}
                  {selectedEvent.priority && (
                    <div>
                      <p className="text-xs mb-1" style={{ color: "#5E657B" }}>Priority</p>
                      <p className="text-sm font-semibold" style={{ color: eventChipColor(selectedEvent.type).bg }}>
                        {selectedEvent.priority}
                      </p>
                    </div>
                  )}
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
                    const c = eventChipColor(ev.type);
                    const sl = statusLabel(ev.status);
                    return (
                      <div
                        key={ev.id}
                        className="rounded-xl p-4 cursor-pointer hover:opacity-90 transition-opacity"
                        style={{ background: c.bg + "12", borderLeft: `3px solid ${c.bg}` }}
                        onClick={() => setSelectedEvent(ev)}
                      >
                        <p className="text-sm font-semibold text-foreground" style={PLAYFAIR}>{ev.title}</p>
                        <p className="text-xs mt-0.5" style={{ color: "#5E657B" }}>Room {ev.room}</p>
                        <p className="text-xs mt-0.5" style={{ color: "#5E657B" }}>{ev.time}</p>
                        <div className="flex items-center gap-2 mt-2 flex-wrap">
                          <span
                            className="inline-block text-xs px-2 py-0.5 rounded-full text-white capitalize"
                            style={{ background: c.bg }}
                          >
                            {ev.type}
                          </span>
                          <span
                            className="inline-block text-xs px-2 py-0.5 rounded-full text-white"
                            style={{ background: sl.color }}
                          >
                            {sl.label}
                          </span>
                        </div>
                        {ev.priority && (
                          <p className="text-xs mt-1.5 font-semibold" style={{ color: c.bg }}>
                            Priority: {ev.priority}
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
