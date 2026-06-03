import { useEffect, useMemo, useState } from "react";
import { AlertTriangle, Clock, Check, X } from "lucide-react";
import { approveBooking, getPendingApprovals, rejectBooking } from "../services/classReserveService";
import { useAuth } from "../context/AuthContext";

const PLAYFAIR = { fontFamily: "'Playfair Display', serif" } as const;
const DM_SANS = { fontFamily: "'DM Sans', sans-serif" } as const;

type ApprovalRequest = {
  id: number;
  eventName: string;
  user: string;
  role: string;
  priority: "low" | "medium" | "high";
  room: string;
  building: string;
  date: string;
  time: string;
  hasConflict: boolean;
  conflictWith?: string;
  requestedAt: string;
  description?: string;
  status: "pending" | "approved" | "rejected";
};

const initialRequests: ApprovalRequest[] = [
  { id: 1, eventName: "Study Group Session", user: "Michael Chen", role: "Student", priority: "low", room: "Lab C-105", building: "Building C", date: "Apr 2, 2026", time: "3:00 PM – 5:00 PM", hasConflict: false, requestedAt: "2 hours ago", description: "Weekly study group for the CS final exam preparation.", status: "pending" },
  { id: 2, eventName: "Dance Club Practice", user: "Dance Club", role: "Club", priority: "medium", room: "Room E-101", building: "Building E", date: "Apr 5, 2026", time: "4:00 PM – 6:00 PM", hasConflict: false, requestedAt: "5 hours ago", description: "Regular weekly practice session for the upcoming performance.", status: "pending" },
  { id: 3, eventName: "Research Seminar", user: "Dr. Robert Smith", role: "Faculty", priority: "high", room: "Auditorium B", building: "Building B", date: "Apr 3, 2026", time: "2:00 PM – 4:00 PM", hasConflict: false, requestedAt: "1 day ago", description: "Faculty research seminar open to graduate students.", status: "pending" },
  { id: 4, eventName: "Workshop", user: "Tech Club", role: "Club", priority: "medium", room: "Room D-202", building: "Building D", date: "Apr 3, 2026", time: "2:00 PM – 3:00 PM", hasConflict: true, conflictWith: "CS Lecture already scheduled", requestedAt: "3 hours ago", description: "Technology workshop for club members.", status: "pending" },
  { id: 5, eventName: "Tutorial Session", user: "Lisa Anderson", role: "Student", priority: "low", room: "Room B-205", building: "Building B", date: "Apr 4, 2026", time: "11:00 AM – 12:00 PM", hasConflict: false, requestedAt: "4 hours ago", status: "pending" },
  { id: 6, eventName: "Department Meeting", user: "Prof. Jennifer White", role: "Faculty", priority: "high", room: "Room A-301", building: "Building A", date: "Apr 6, 2026", time: "10:00 AM – 12:00 PM", hasConflict: false, requestedAt: "6 hours ago", status: "pending" },
  { id: 7, eventName: "Math Lecture", user: "Dr. Sarah Johnson", role: "Faculty", priority: "high", room: "Room A-302", building: "Building A", date: "Mar 30, 2026", time: "10:00 AM – 12:00 PM", hasConflict: false, requestedAt: "2 days ago", status: "approved" },
  { id: 8, eventName: "Club Fair", user: "Student Council", role: "Club", priority: "medium", room: "Auditorium B", building: "Building B", date: "Mar 28, 2026", time: "1:00 PM – 5:00 PM", hasConflict: false, requestedAt: "3 days ago", status: "rejected" },
];

function priorityStyle(p: string, role: string) {
  if (p === "high") return { bg: "#891D1A", label: `HIGH — ${role.toUpperCase()}` };
  if (p === "medium") return { bg: "#5E657B", label: `MEDIUM — ${role.toUpperCase()}` };
  return { bg: "#B8860B", label: `NORMAL — ${role.toUpperCase()}` };
}

export function Approvals() {
  const [requests, setRequests] = useState<ApprovalRequest[]>(initialRequests);
  const [isLoading, setIsLoading] = useState(true);
  const { user } = useAuth();

  useEffect(() => {
    getPendingApprovals(user?.role || "faculty")
      .then((items) => {
        setRequests(items.map((item) => ({
          id: item.id,
          eventName: item.title,
          user: item.requesterName,
          role: item.requesterRole.charAt(0).toUpperCase() + item.requesterRole.slice(1),
          priority: item.priority === "high" ? "high" : item.priority === "medium" ? "medium" : "low",
          room: item.roomName,
          building: item.building || "Campus",
          date: item.date,
          time: `${item.startTime} - ${item.endTime}`,
          hasConflict: item.conflictStatus === "conflict" || item.conflictStatus === "maintenance",
          conflictWith: item.conflictStatus === "maintenance" ? "Room is under maintenance" : "Possible booking conflict",
          requestedAt: "From API",
          description: "",
          status: item.status === "approved" || item.status === "rejected" ? item.status : "pending",
        })));
      })
      .finally(() => setIsLoading(false));
  }, [user?.role]);

  const pending = useMemo(() => requests.filter((r) => r.status === "pending"), [requests]);
  const reviewed = useMemo(() => requests.filter((r) => r.status !== "pending"), [requests]);

  const approve = async (id: number) => {
    await approveBooking(id);
    setRequests((prev) => prev.map((r) => (r.id === id ? { ...r, status: "approved" } : r)));
  };
  const reject = async (id: number) => {
    const reason = window.prompt("Reason for rejection?", "Not available at the requested time.") || "Rejected";
    await rejectBooking(id, reason);
    setRequests((prev) => prev.map((r) => (r.id === id ? { ...r, status: "rejected" } : r)));
  };

  return (
    <div className="space-y-5" style={DM_SANS}>
      <div className="flex items-start justify-between gap-4 flex-wrap">
        <div>
          <h1 style={{ ...PLAYFAIR, fontSize: 28, fontWeight: 600 }} className="text-foreground">
            Approvals
          </h1>
          <p className="text-sm mt-1" style={{ color: "#5E657B" }}>
            {isLoading ? "Loading approval queue..." : "Review and action pending booking requests"}
          </p>
        </div>
        <div
          className="px-3 py-1.5 rounded-full text-sm font-medium"
          style={{ background: "rgba(184,134,11,0.1)", color: "#B8860B" }}
        >
          {pending.length} pending
        </div>
      </div>

      <div className="grid grid-cols-1 xl:grid-cols-5 gap-6">
        {/* Pending — wider column */}
        <div className="xl:col-span-3 space-y-3">
          <h3 className="text-sm font-semibold" style={{ color: "#5E657B" }}>
            PENDING REQUESTS ({pending.length})
          </h3>

          {pending.map((req) => {
            const ps = priorityStyle(req.priority, req.role);
            return (
              <div
                key={req.id}
                className="bg-card rounded-xl shadow-sm overflow-hidden"
                style={{ borderLeft: `3px solid #B8860B` }}
              >
                <div className="p-5">
                  <div className="flex items-start justify-between gap-3 mb-3">
                    <div className="flex-1 min-w-0">
                      <h4 className="font-semibold text-foreground" style={PLAYFAIR}>
                        {req.eventName}
                      </h4>
                      <div className="flex items-center gap-2 mt-1 flex-wrap">
                        <span className="text-xs" style={{ color: "#5E657B" }}>{req.user}</span>
                        <span
                          className="text-xs px-2 py-0.5 rounded-full font-medium"
                          style={{ background: ps.bg + "20", color: ps.bg }}
                        >
                          {ps.label}
                        </span>
                        <span className="text-xs" style={{ color: "#5E657B" }}>{req.requestedAt}</span>
                      </div>
                    </div>
                  </div>

                  {req.description && (
                    <p className="text-xs mb-3 line-clamp-2" style={{ color: "#5E657B" }}>
                      {req.description}
                    </p>
                  )}

                  <div className="grid grid-cols-2 gap-2 text-xs mb-3">
                    <div style={{ color: "#5E657B" }}>
                      Room: <span className="font-medium text-foreground">{req.room}</span>
                    </div>
                    <div style={{ color: "#5E657B" }}>
                      Date: <span className="font-medium text-foreground">{req.date}</span>
                    </div>
                    <div className="col-span-2 flex items-center gap-1" style={{ color: "#5E657B" }}>
                      <Clock className="w-3 h-3" /> {req.time}
                    </div>
                  </div>

                  {req.hasConflict && (
                    <div
                      className="rounded-lg p-3 mb-3 flex items-start gap-2"
                      style={{ background: "rgba(184,134,11,0.08)", border: "1px solid rgba(184,134,11,0.3)" }}
                    >
                      <AlertTriangle className="w-4 h-4 flex-shrink-0 mt-0.5" style={{ color: "#B8860B" }} />
                      <div>
                        <p className="text-xs font-semibold" style={{ color: "#B8860B" }}>
                          ⚠ Conflict detected
                        </p>
                        <p className="text-xs mt-0.5" style={{ color: "#5E657B" }}>
                          {req.conflictWith}
                        </p>
                      </div>
                    </div>
                  )}

                  <div className="flex gap-2">
                    <button
                      onClick={() => reject(req.id)}
                      className="flex-1 py-2 rounded-lg text-sm font-medium text-white flex items-center justify-center gap-1"
                      style={{ background: "#891D1A" }}
                      onMouseEnter={(e) => (e.currentTarget.style.background = "#6b1513")}
                      onMouseLeave={(e) => (e.currentTarget.style.background = "#891D1A")}
                    >
                      <X className="w-4 h-4" /> Reject
                    </button>
                    <button
                      onClick={() => approve(req.id)}
                      disabled={req.hasConflict}
                      className="flex-1 py-2 rounded-lg text-sm font-medium text-white flex items-center justify-center gap-1 disabled:opacity-40 disabled:cursor-not-allowed"
                      style={{ background: "#3B6E4A" }}
                      onMouseEnter={(e) => { if (!req.hasConflict) e.currentTarget.style.background = "#2d5437"; }}
                      onMouseLeave={(e) => { if (!req.hasConflict) e.currentTarget.style.background = "#3B6E4A"; }}
                    >
                      <Check className="w-4 h-4" /> Approve
                    </button>
                  </div>
                </div>
              </div>
            );
          })}

          {pending.length === 0 && (
            <div className="bg-card rounded-xl p-8 text-center shadow-sm">
              <Check className="w-10 h-10 mx-auto mb-2" style={{ color: "#3B6E4A", opacity: 0.5 }} />
              <p className="text-sm" style={{ color: "#5E657B" }}>All caught up! No pending requests.</p>
            </div>
          )}
        </div>

        {/* Recently Reviewed — narrower column */}
        <div className="xl:col-span-2 space-y-3">
          <h3 className="text-sm font-semibold" style={{ color: "#5E657B" }}>
            RECENTLY REVIEWED ({reviewed.length})
          </h3>

          {reviewed.map((req) => {
            const isApproved = req.status === "approved";
            return (
              <div
                key={req.id}
                className="bg-card rounded-xl shadow-sm overflow-hidden"
                style={{ borderLeft: `3px solid ${isApproved ? "#3B6E4A" : "#891D1A"}` }}
              >
                <div className="p-4">
                  <div className="flex items-start justify-between gap-2 mb-1">
                    <p className="text-sm font-semibold text-foreground" style={PLAYFAIR}>
                      {req.eventName}
                    </p>
                    <span
                      className="text-xs px-2 py-0.5 rounded-full text-white font-medium flex-shrink-0"
                      style={{ background: isApproved ? "#3B6E4A" : "#891D1A" }}
                    >
                      {isApproved ? "Approved" : "Rejected"}
                    </span>
                  </div>
                  <p className="text-xs" style={{ color: "#5E657B" }}>{req.user} · {req.room}</p>
                  <p className="text-xs mt-0.5" style={{ color: "#5E657B" }}>
                    {req.date} · {req.time}
                  </p>
                </div>
              </div>
            );
          })}
        </div>
      </div>
    </div>
  );
}
