import { useEffect, useState } from "react";
import { AlertCircle, Check, ChevronRight, Clock, Plus, Users, X } from "lucide-react";
import { useNavigate } from "react-router";
import { useAuth } from "../context/AuthContext";
import { approveBooking, getAvailableRooms, getDashboardStats, getMyBookings, getPendingApprovals, rejectBooking } from "../services/classReserveService";
import type { Booking, DashboardStats, Room } from "../types/classReserve";

const PLAYFAIR = { fontFamily: "'Playfair Display', serif" } as const;
const DM_SANS = { fontFamily: "'DM Sans', sans-serif" } as const;

export function FacultyDashboard() {
  const navigate = useNavigate();
  const { user } = useAuth();
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [rooms, setRooms] = useState<Room[]>([]);
  const [requests, setRequests] = useState<Booking[]>([]);
  const [reservations, setReservations] = useState<Booking[]>([]);
  const [error, setError] = useState("");

  const refresh = () => Promise.all([
    getDashboardStats(), getAvailableRooms(), getPendingApprovals("faculty"),
    getMyBookings({ role: "faculty", userId: user?.id, email: user?.email }),
  ]).then(([dashboard, roomRows, approvalRows, bookingRows]) => {
    setStats(dashboard); setRooms(roomRows); setRequests(approvalRows.filter((item) => item.status === "pending")); setReservations(bookingRows); setError("");
  }).catch((reason) => setError(reason instanceof Error ? reason.message : "Could not load faculty dashboard data."));

  useEffect(() => { refresh(); }, [user?.id, user?.email]);

  const approve = async (id: number) => { try { await approveBooking(id); await refresh(); } catch (reason) { setError(reason instanceof Error ? reason.message : "Approval failed."); } };
  const reject = async (id: number) => {
    const reason = window.prompt("Reason for rejection:");
    if (!reason?.trim()) return;
    try { await rejectBooking(id, reason.trim()); await refresh(); } catch (failure) { setError(failure instanceof Error ? failure.message : "Rejection failed."); }
  };

  return <div className="space-y-6" style={DM_SANS}>
    <div className="flex items-center justify-between flex-wrap gap-4"><div><h1 style={{ ...PLAYFAIR, fontSize: 28, fontWeight: 600 }} className="text-foreground">Faculty Dashboard</h1><p className="text-sm mt-1" style={{ color: "#5E657B" }}>Manage your classes and review booking requests</p></div><button onClick={() => navigate("/faculty/new-booking")} className="flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium text-[#F1E6D2]" style={{ background: "#891D1A" }}><Plus className="w-4 h-4" />Reserve Room</button></div>
    {error && <div className="rounded-xl p-4 flex gap-3" style={{ background: "rgba(137,29,26,0.06)", border: "1px solid rgba(137,29,26,0.2)", color: "#891D1A" }}><AlertCircle className="w-5 h-5" /><p className="text-sm">{error}</p></div>}
    <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">{[["Total Rooms", stats?.rooms.total || 0], ["Available", stats?.rooms.available || 0], ["Pending Requests", stats?.bookings.pending || 0], ["Approved Bookings", stats?.bookings.approved || 0]].map(([label, value]) => <div key={String(label)} className="bg-card rounded-xl p-5 shadow-sm" style={{ borderLeft: "3px solid #891D1A" }}><p className="text-xs" style={{ color: "#5E657B" }}>{label}</p><p className="mt-2 text-2xl font-semibold text-foreground" style={PLAYFAIR}>{value}</p></div>)}</div>
    <div className="bg-card rounded-xl shadow-sm overflow-hidden"><div className="px-5 py-4 border-b border-border flex items-center justify-between"><h3 style={{ ...PLAYFAIR, fontSize: 16, fontWeight: 600 }}>Today's Rooms</h3><button className="text-sm font-medium flex items-center gap-1" style={{ color: "#891D1A" }} onClick={() => navigate("/faculty/rooms")}>View all <ChevronRight className="w-4 h-4" /></button></div><div className="p-4 flex gap-3 overflow-x-auto">{rooms.slice(0, 6).map((room) => <div key={room.id} className="flex-shrink-0 w-44 rounded-xl p-4 bg-white" style={{ border: "1px solid rgba(137,29,26,0.1)", borderLeft: `3px solid ${room.status === "available" ? "#3B6E4A" : "#891D1A"}` }}><p className="text-sm font-semibold truncate">{room.name}</p><p className="flex items-center gap-1 mt-1 text-xs" style={{ color: "#5E657B" }}><Users className="w-3 h-3" />{room.capacity}</p><button className="mt-3 text-xs font-medium" style={{ color: "#891D1A" }} onClick={() => navigate("/faculty/new-booking", { state: { roomName: room.name, roomId: room.id, startAtStep: 2 } })}>{room.status === "available" ? "Reserve" : room.status}</button></div>)}</div></div>
    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div className="bg-card rounded-xl shadow-sm overflow-hidden"><div className="px-5 py-4 border-b border-border"><h3 style={{ ...PLAYFAIR, fontSize: 16, fontWeight: 600 }}>Pending Student Requests</h3></div><div className="p-4 space-y-3">{requests.length ? requests.map((request) => <div key={request.id} className="rounded-xl p-4 bg-white" style={{ border: "1px solid rgba(137,29,26,0.12)" }}><p className="text-sm font-semibold">{request.title}</p><p className="text-xs mt-1" style={{ color: "#5E657B" }}>{request.requesterName} · {request.roomName}</p><p className="text-xs mt-1 flex items-center gap-1" style={{ color: "#5E657B" }}><Clock className="w-3 h-3" />{request.date} · {request.startTime} - {request.endTime}</p><div className="flex gap-2 mt-3"><button onClick={() => reject(request.id)} className="flex-1 py-2 rounded-lg text-sm text-white flex items-center justify-center gap-1" style={{ background: "#891D1A" }}><X className="w-4 h-4" />Reject</button><button onClick={() => approve(request.id)} className="flex-1 py-2 rounded-lg text-sm text-white flex items-center justify-center gap-1" style={{ background: "#3B6E4A" }}><Check className="w-4 h-4" />Approve</button></div></div>) : <p className="text-sm" style={{ color: "#5E657B" }}>No pending requests.</p>}</div></div>
      <div className="bg-card rounded-xl shadow-sm overflow-hidden"><div className="px-5 py-4 border-b border-border"><h3 style={{ ...PLAYFAIR, fontSize: 16, fontWeight: 600 }}>My Reservations</h3></div><div className="p-4 space-y-3">{reservations.length ? reservations.slice(0, 6).map((booking) => <div key={booking.id} className="rounded-xl p-4 bg-white" style={{ borderLeft: `3px solid ${booking.status === "approved" ? "#3B6E4A" : "#B8860B"}` }}><p className="text-sm font-semibold">{booking.title}</p><p className="text-xs mt-1" style={{ color: "#5E657B" }}>{booking.roomName} · {booking.date} · {booking.startTime} - {booking.endTime}</p><p className="text-xs mt-1 capitalize" style={{ color: "#891D1A" }}>{booking.status}</p></div>) : <p className="text-sm" style={{ color: "#5E657B" }}>No reservations found.</p>}</div></div>
    </div>
  </div>;
}
