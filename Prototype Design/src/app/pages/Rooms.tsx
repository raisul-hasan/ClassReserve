import { useState } from "react";
import { useNavigate } from "react-router";
import { Users, LayoutGrid, List, ExternalLink } from "lucide-react";
import { useAuth } from "../context/AuthContext";

const PLAYFAIR = { fontFamily: "'Playfair Display', serif" } as const;
const DM_SANS = { fontFamily: "'DM Sans', sans-serif" } as const;

const rooms = [
  { id: 1, name: "Room A-301", capacity: 30, status: "available", equipment: ["Projector", "Whiteboard", "Wi-Fi"], building: "Building A", type: "Lecture" },
  { id: 2, name: "Room A-302", capacity: 40, status: "booked", equipment: ["Projector", "Computer", "Wi-Fi"], building: "Building A", type: "Lecture" },
  { id: 3, name: "Lab C-105", capacity: 25, status: "available", equipment: ["Computers", "Wi-Fi", "Projector"], building: "Building C", type: "Lab" },
  { id: 4, name: "Auditorium B", capacity: 200, status: "available", equipment: ["Audio System", "Projector", "Stage"], building: "Building B", type: "Auditorium" },
  { id: 5, name: "Room D-202", capacity: 35, status: "maintenance", equipment: ["Whiteboard", "Wi-Fi"], building: "Building D", type: "Lecture" },
  { id: 6, name: "Room E-101", capacity: 20, status: "available", equipment: ["TV Display", "Wi-Fi"], building: "Building E", type: "Seminar" },
  { id: 7, name: "Lab C-106", capacity: 30, status: "booked", equipment: ["Computers", "Wi-Fi", "Printer"], building: "Building C", type: "Lab" },
  { id: 8, name: "Room B-205", capacity: 45, status: "available", equipment: ["Projector", "Whiteboard", "Wi-Fi", "Computer"], building: "Building B", type: "Lecture" },
];

function statusStyle(status: string) {
  switch (status) {
    case "available": return { bg: "#3B6E4A", label: "Available", border: "#3B6E4A" };
    case "booked": return { bg: "#891D1A", label: "Booked", border: "#891D1A" };
    case "maintenance": return { bg: "#B8860B", label: "Maintenance", border: "#B8860B" };
    default: return { bg: "#5E657B", label: status, border: "#5E657B" };
  }
}

export function Rooms() {
  const [capacityFilter, setCapacityFilter] = useState("all");
  const [statusFilter, setStatusFilter] = useState("all");
  const [buildingFilter, setBuildingFilter] = useState("all");
  const [viewMode, setViewMode] = useState<"grid" | "table">("grid");
  const navigate = useNavigate();
  const { user } = useAuth();

  const filtered = rooms.filter((r) => {
    if (capacityFilter !== "all" && r.capacity < parseInt(capacityFilter)) return false;
    if (statusFilter !== "all" && r.status !== statusFilter) return false;
    if (buildingFilter !== "all" && r.building !== buildingFilter) return false;
    return true;
  });

  const buildings = Array.from(new Set(rooms.map((r) => r.building)));

  const base = user?.role === "faculty" ? "/faculty" : user?.role === "admin" ? "/admin" : "/student";

  const handleBook = (roomName: string) => {
    navigate(`${base}/new-booking`, { state: { roomName } });
  };

  const handleViewDetail = (roomName: string) => {
    navigate(`${base}/rooms/${encodeURIComponent(roomName)}`);
  };

  const selectCls =
    "px-3 py-2 rounded-lg text-sm border bg-white dark:bg-[#3A1210] dark:text-[#F1E6D2] outline-none cursor-pointer";

  return (
    <div className="space-y-5" style={DM_SANS}>
      <div className="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h1 style={{ ...PLAYFAIR, fontSize: 28, fontWeight: 600 }} className="text-foreground">
            Rooms
          </h1>
          <p className="text-sm mt-1" style={{ color: "#5E657B" }}>
            Browse and manage classroom spaces
          </p>
        </div>

        <div className="flex items-center gap-2">
          <button
            onClick={() => setViewMode("grid")}
            className="w-9 h-9 rounded-lg flex items-center justify-center border transition-colors"
            style={
              viewMode === "grid"
                ? { background: "#891D1A", borderColor: "#891D1A", color: "#F1E6D2" }
                : { borderColor: "rgba(137,29,26,0.3)", color: "#5E657B" }
            }
          >
            <LayoutGrid className="w-4 h-4" />
          </button>
          <button
            onClick={() => setViewMode("table")}
            className="w-9 h-9 rounded-lg flex items-center justify-center border transition-colors"
            style={
              viewMode === "table"
                ? { background: "#891D1A", borderColor: "#891D1A", color: "#F1E6D2" }
                : { borderColor: "rgba(137,29,26,0.3)", color: "#5E657B" }
            }
          >
            <List className="w-4 h-4" />
          </button>
        </div>
      </div>

      {/* Filter Bar */}
      <div
        className="bg-card rounded-xl p-4 flex flex-wrap gap-3 items-end shadow-sm"
      >
        <div className="flex items-center gap-2">
          <label className="text-xs font-medium" style={{ color: "#5E657B" }}>Date</label>
          <input
            type="date"
            className="px-3 py-2 rounded-lg text-sm border bg-white dark:bg-[#3A1210] dark:text-[#F1E6D2] outline-none"
            style={{ borderColor: "rgba(137,29,26,0.2)" }}
          />
        </div>

        <div className="flex items-center gap-2">
          <label className="text-xs font-medium" style={{ color: "#5E657B" }}>Capacity</label>
          <select
            value={capacityFilter}
            onChange={(e) => setCapacityFilter(e.target.value)}
            className={selectCls}
            style={{ borderColor: "rgba(137,29,26,0.2)" }}
          >
            <option value="all">All sizes</option>
            <option value="20">20+ seats</option>
            <option value="30">30+ seats</option>
            <option value="40">40+ seats</option>
            <option value="100">100+ seats</option>
          </select>
        </div>

        <div className="flex items-center gap-2">
          <label className="text-xs font-medium" style={{ color: "#5E657B" }}>Status</label>
          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
            className={selectCls}
            style={{ borderColor: "rgba(137,29,26,0.2)" }}
          >
            <option value="all">All status</option>
            <option value="available">Available</option>
            <option value="booked">Booked</option>
            <option value="maintenance">Maintenance</option>
          </select>
        </div>

        <div className="flex items-center gap-2">
          <label className="text-xs font-medium" style={{ color: "#5E657B" }}>Building</label>
          <select
            value={buildingFilter}
            onChange={(e) => setBuildingFilter(e.target.value)}
            className={selectCls}
            style={{ borderColor: "rgba(137,29,26,0.2)" }}
          >
            <option value="all">All buildings</option>
            {buildings.map((b) => (
              <option key={b} value={b}>{b}</option>
            ))}
          </select>
        </div>

        <button
          className="px-4 py-2 rounded-lg text-sm font-medium text-white"
          style={{ background: "#891D1A" }}
          onClick={() => {
            setCapacityFilter("all");
            setStatusFilter("all");
            setBuildingFilter("all");
          }}
        >
          Reset
        </button>
      </div>

      {/* Grid View */}
      {viewMode === "grid" && (
        <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
          {filtered.map((room) => {
            const s = statusStyle(room.status);
            return (
              <div
                key={room.id}
                className="bg-card rounded-xl shadow-sm overflow-hidden flex flex-col"
                style={{ borderLeft: `3px solid ${s.border}` }}
              >
                <div className="p-5 flex-1">
                  <div className="flex items-start justify-between gap-2 mb-3">
                    <div>
                      <button
                        onClick={() => handleViewDetail(room.name)}
                        className="flex items-center gap-1 hover:underline text-left"
                      >
                        <h3 style={{ ...PLAYFAIR, fontSize: 16, fontWeight: 600 }} className="text-foreground">
                          {room.name}
                        </h3>
                        <ExternalLink className="w-3 h-3 opacity-50" style={{ color: "#891D1A" }} />
                      </button>
                      <span
                        className="inline-block text-xs px-2 py-0.5 rounded-full text-white mt-1"
                        style={{ background: "#5E657B" }}
                      >
                        {room.building}
                      </span>
                    </div>
                    <span
                      className="text-xs px-2.5 py-1 rounded-full text-white font-medium flex-shrink-0"
                      style={{ background: s.bg }}
                    >
                      {s.label}
                    </span>
                  </div>

                  <div className="flex items-center gap-2 mb-3 text-sm" style={{ color: "#5E657B" }}>
                    <Users className="w-4 h-4" />
                    <span>{room.capacity} seats</span>
                    <span className="text-xs">·</span>
                    <span className="text-xs">{room.type}</span>
                  </div>

                  <div className="flex flex-wrap gap-1.5">
                    {room.equipment.map((eq, i) => (
                      <span
                        key={i}
                        className="text-xs px-2 py-0.5 rounded-full"
                        style={{ background: "#F1E6D2", color: "#5E657B" }}
                      >
                        {eq}
                      </span>
                    ))}
                  </div>
                </div>

                <div className="px-5 pb-5">
                  <button
                    onClick={() => handleBook(room.name)}
                    disabled={room.status !== "available"}
                    className="w-full py-2 rounded-lg text-sm font-medium text-white transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                    style={{ background: "#891D1A" }}
                    onMouseEnter={(e) => { if (room.status === "available") e.currentTarget.style.background = "#210706"; }}
                    onMouseLeave={(e) => { if (room.status === "available") e.currentTarget.style.background = "#891D1A"; }}
                  >
                    {room.status === "available" ? "Book Now" : s.label}
                  </button>
                </div>
              </div>
            );
          })}

          {filtered.length === 0 && (
            <div className="col-span-full py-12 text-center text-sm" style={{ color: "#5E657B" }}>
              No rooms match your filters.
            </div>
          )}
        </div>
      )}

      {/* Table View */}
      {viewMode === "table" && (
        <div className="bg-card rounded-xl shadow-sm overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-border text-left">
                  <th className="px-5 py-3 font-medium text-xs" style={{ color: "#5E657B" }}>Room</th>
                  <th className="px-5 py-3 font-medium text-xs" style={{ color: "#5E657B" }}>Capacity</th>
                  <th className="px-5 py-3 font-medium text-xs" style={{ color: "#5E657B" }}>Status</th>
                  <th className="px-5 py-3 font-medium text-xs" style={{ color: "#5E657B" }}>Equipment</th>
                  <th className="px-5 py-3 font-medium text-xs text-right" style={{ color: "#5E657B" }}>Action</th>
                </tr>
              </thead>
              <tbody>
                {filtered.map((room) => {
                  const s = statusStyle(room.status);
                  return (
                    <tr key={room.id} className="border-b border-border last:border-0">
                      <td className="px-5 py-4">
                        <p className="font-semibold text-foreground" style={PLAYFAIR}>{room.name}</p>
                        <p className="text-xs mt-0.5" style={{ color: "#5E657B" }}>{room.building}</p>
                      </td>
                      <td className="px-5 py-4">
                        <div className="flex items-center gap-1.5" style={{ color: "#5E657B" }}>
                          <Users className="w-4 h-4" /> {room.capacity}
                        </div>
                      </td>
                      <td className="px-5 py-4">
                        <span className="text-xs px-2.5 py-1 rounded-full text-white" style={{ background: s.bg }}>
                          {s.label}
                        </span>
                      </td>
                      <td className="px-5 py-4">
                        <div className="flex flex-wrap gap-1">
                          {room.equipment.slice(0, 3).map((eq, i) => (
                            <span
                              key={i}
                              className="text-xs px-2 py-0.5 rounded-full"
                              style={{ background: "#F1E6D2", color: "#5E657B" }}
                            >
                              {eq}
                            </span>
                          ))}
                        </div>
                      </td>
                      <td className="px-5 py-4 text-right">
                        <button
                          onClick={() => handleBook(room.name)}
                          disabled={room.status !== "available"}
                          className="px-3 py-1.5 rounded-lg text-xs font-medium text-white disabled:opacity-40"
                          style={{ background: "#891D1A" }}
                        >
                          Book Now
                        </button>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </div>
  );
}
