import { useEffect, useMemo, useState } from "react";
import { AlertTriangle, Check, Clock, X } from "lucide-react";
import { approveBooking, getPendingApprovals, rejectBooking } from "../services/classReserveService";
import { useAuth } from "../context/AuthContext";
import type { Booking } from "../types/classReserve";

const PLAYFAIR = { fontFamily: "'Playfair Display', serif" } as const;
const DM_SANS = { fontFamily: "'DM Sans', sans-serif" } as const;

export function Approvals() {
  const { user } = useAuth();
  const [requests, setRequests] = useState<Booking[]>([]);
  const [statusFilter, setStatusFilter] = useState("pending");
  const [selected, setSelected] = useState<Booking | null>(null);
  const [rejecting, setRejecting] = useState<Booking | null>(null);
  const [reason, setReason] = useState("");
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const isAdmin = user?.role === "admin";

  const load = () => getPendingApprovals(user?.role || "faculty")
    .then((items) => { setRequests(items); setError(""); })
    .catch((failure) => setError(failure instanceof Error ? failure.message : "Could not load approval requests."))
    .finally(() => setLoading(false));

  useEffect(() => { load(); }, [user?.role]);

  const visible = useMemo(() => requests.filter((item) => statusFilter === "all" || item.status === statusFilter), [requests, statusFilter]);
  const pending = requests.filter((item) => item.status === "pending");
  const reviewed = requests.filter((item) => item.status === "approved" || item.status === "rejected");

  const approve = async (id: number) => {
    try { await approveBooking(id); await load(); } catch (failure) { setError(failure instanceof Error ? failure.message : "Approval failed."); }
  };
  const reject = async () => {
    if (!rejecting || !reason.trim()) return;
    try { await rejectBooking(rejecting.id, reason.trim()); setRejecting(null); setReason(""); await load(); }
    catch (failure) { setError(failure instanceof Error ? failure.message : "Rejection failed."); }
  };

  const requestCard = (request: Booking, actions = false) => <div key={request.id} className="bg-card rounded-xl shadow-sm p-5" style={{ borderLeft: `3px solid ${request.status === "approved" ? "#3B6E4A" : request.status === "rejected" ? "#891D1A" : "#B8860B"}` }}>
    <div className="flex items-start justify-between gap-3"><div><h4 className="font-semibold text-foreground" style={PLAYFAIR}>{request.title}</h4><p className="text-xs mt-1" style={{ color: "#5E657B" }}>{request.requesterName} · {request.requesterRole}</p></div><span className="text-xs px-2 py-1 rounded-full text-white capitalize" style={{ background: request.status === "approved" ? "#3B6E4A" : request.status === "rejected" ? "#891D1A" : "#B8860B" }}>{request.status}</span></div>
    <div className="mt-3 text-xs space-y-1" style={{ color: "#5E657B" }}><p>{request.roomName}, {request.building}</p><p className="flex items-center gap-1"><Clock className="w-3 h-3" />{request.date} · {request.startTime} - {request.endTime}</p>{request.conflictStatus !== "clear" && <p className="flex items-center gap-1" style={{ color: "#B8860B" }}><AlertTriangle className="w-3 h-3" />{request.conflictStatus === "maintenance" ? "Maintenance conflict" : "Booking conflict"}</p>}</div>
    {request.reviewedBy && <div className="mt-3 pt-3 border-t border-border text-xs" style={{ color: "#5E657B" }}><p>Reviewed by {request.reviewedBy}{request.reviewedAt ? ` on ${request.reviewedAt}` : ""}</p>{request.rejectionReason && <p className="mt-1" style={{ color: "#891D1A" }}>Reason: {request.rejectionReason}</p>}</div>}
    <div className="flex gap-2 mt-3"><button onClick={() => setSelected(request)} className="px-3 py-2 rounded-lg text-xs border" style={{ borderColor: "rgba(137,29,26,0.25)", color: "#5E657B" }}>View Details</button>{actions && <><button onClick={() => { setRejecting(request); setReason(""); }} className="flex-1 py-2 rounded-lg text-sm text-white flex justify-center gap-1" style={{ background: "#891D1A" }}><X className="w-4 h-4" />Reject</button><button onClick={() => approve(request.id)} className="flex-1 py-2 rounded-lg text-sm text-white flex justify-center gap-1" style={{ background: "#3B6E4A" }}><Check className="w-4 h-4" />Approve</button></>}</div>
  </div>;

  return <div className="space-y-5" style={DM_SANS}>
    <div className="flex justify-between gap-4"><div><h1 style={{ ...PLAYFAIR, fontSize: 28, fontWeight: 600 }}>{isAdmin ? "Manage Requests" : "Approvals"}</h1><p className="text-sm mt-1" style={{ color: "#5E657B" }}>{loading ? "Loading approval queue..." : "Review database-backed reservation requests"}</p></div><span className="h-fit px-3 py-1.5 rounded-full text-sm" style={{ background: "rgba(184,134,11,0.1)", color: "#B8860B" }}>{pending.length} pending</span></div>
    {error && <div className="rounded-xl p-4 text-sm" style={{ background: "rgba(137,29,26,0.06)", border: "1px solid rgba(137,29,26,0.2)", color: "#891D1A" }}>{error}</div>}
    <div className="grid grid-cols-1 xl:grid-cols-5 gap-6"><div className="xl:col-span-3 space-y-3"><h3 className="text-sm font-semibold" style={{ color: "#5E657B" }}>PENDING REQUESTS ({pending.length})</h3>{pending.map((item) => requestCard(item, true))}{!pending.length && !loading && <div className="bg-card rounded-xl p-8 text-center text-sm" style={{ color: "#5E657B" }}>No pending requests.</div>}</div><div className="xl:col-span-2 space-y-3"><h3 className="text-sm font-semibold" style={{ color: "#5E657B" }}>RECENTLY REVIEWED ({reviewed.length})</h3>{reviewed.map((item) => requestCard(item))}</div></div>
    <div className="bg-card rounded-xl p-4 shadow-sm flex items-center gap-3"><label className="text-xs font-medium" style={{ color: "#5E657B" }}>Status</label><select value={statusFilter} onChange={(event) => setStatusFilter(event.target.value)} className="px-3 py-2 rounded-lg text-sm border bg-white dark:bg-[#3A1210]" style={{ borderColor: "rgba(137,29,26,0.2)" }}><option value="all">All</option><option value="pending">Pending</option><option value="approved">Approved</option><option value="rejected">Rejected</option></select><span className="text-xs" style={{ color: "#5E657B" }}>{visible.length} results</span></div>
    {rejecting && <div className="fixed inset-0 z-50 flex items-center justify-center p-4" style={{ background: "rgba(33,7,6,0.55)" }} onClick={() => setRejecting(null)}><div className="bg-card rounded-xl shadow-2xl w-full max-w-md p-6" onClick={(event) => event.stopPropagation()}><h2 style={{ ...PLAYFAIR, fontSize: 18, fontWeight: 600 }}>Reject Request</h2><p className="text-sm my-3" style={{ color: "#5E657B" }}>{rejecting.title}</p><textarea value={reason} onChange={(event) => setReason(event.target.value)} rows={4} placeholder="Reason for rejection..." className="w-full px-3 py-2 rounded-lg border bg-white dark:bg-[#3A1210]" /><div className="flex gap-3 mt-4"><button onClick={() => setRejecting(null)} className="flex-1 py-2 rounded-lg border">Cancel</button><button onClick={reject} disabled={!reason.trim()} className="flex-1 py-2 rounded-lg text-white disabled:opacity-40" style={{ background: "#891D1A" }}>Reject</button></div></div></div>}
    {selected && <div className="fixed inset-0 z-40" style={{ background: "rgba(33,7,6,0.4)" }} onClick={() => setSelected(null)}><div className="absolute right-0 top-0 h-full w-96 bg-card shadow-2xl p-5" onClick={(event) => event.stopPropagation()}><div className="flex justify-between"><h2 style={{ ...PLAYFAIR, fontSize: 18, fontWeight: 600 }}>Request Details</h2><button onClick={() => setSelected(null)}><X className="w-4 h-4" /></button></div><div className="mt-5 space-y-4">{[["Event", selected.title], ["Requester", `${selected.requesterName} (${selected.requesterRole})`], ["Room", `${selected.roomName}, ${selected.building}`], ["Date", selected.date], ["Time", `${selected.startTime} - ${selected.endTime}`], ["Status", selected.status], ["Reviewed by", selected.reviewedBy || "Not reviewed"], ["Reviewed at", selected.reviewedAt || "Not reviewed"], ["Rejection reason", selected.rejectionReason || "Not applicable"]].map(([label, value]) => <div key={label}><p className="text-xs font-semibold" style={{ color: "#5E657B" }}>{label}</p><p className="text-sm mt-1">{value}</p></div>)}</div></div></div>}
  </div>;
}
