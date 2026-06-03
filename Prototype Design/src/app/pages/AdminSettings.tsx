import { useState } from "react";
import {
  Settings,
  DoorOpen,
  Users,
  ArrowUp,
  ArrowDown,
  Trash2,
  Plus,
  Lock,
  Wrench,
  Bell,
  Shield,
  Edit2,
  X,
  Check,
  AlertTriangle,
} from "lucide-react";

const PLAYFAIR = { fontFamily: "'Playfair Display', serif" } as const;
const DM_SANS = { fontFamily: "'DM Sans', sans-serif" } as const;

type Category = "general" | "rooms" | "users" | "priority" | "maintenance" | "notifications";

const CATEGORIES: { key: Category; label: string; icon: React.ElementType }[] = [
  { key: "general", label: "General", icon: Settings },
  { key: "rooms", label: "Rooms", icon: DoorOpen },
  { key: "users", label: "Users", icon: Users },
  { key: "priority", label: "Priority Rules", icon: Shield },
  { key: "maintenance", label: "Maintenance", icon: Wrench },
  { key: "notifications", label: "Notifications", icon: Bell },
];

type RoomType = "Lecture" | "Lab" | "Seminar" | "Auditorium";
type RoomStatus = "Active" | "Blocked" | "Maintenance";

type RoomItem = {
  id: number;
  name: string;
  building: string;
  floor: string;
  capacity: number;
  type: RoomType;
  equipment: string[];
  status: RoomStatus;
};

const EQUIPMENT_OPTIONS = ["Projector", "Whiteboard", "Wi-Fi", "Computers", "Audio System", "Stage", "TV Display", "Computer", "AC"];
const ROOM_TYPES: RoomType[] = ["Lecture", "Lab", "Seminar", "Auditorium"];

const initialRooms: RoomItem[] = [
  { id: 1, name: "Room A-301", building: "Building A", floor: "3rd Floor", capacity: 30, type: "Lecture", equipment: ["Projector", "Whiteboard", "Wi-Fi"], status: "Active" },
  { id: 2, name: "Room A-302", building: "Building A", floor: "3rd Floor", capacity: 25, type: "Seminar", equipment: ["Whiteboard", "Wi-Fi"], status: "Active" },
  { id: 3, name: "Lab C-105", building: "Building C", floor: "1st Floor", capacity: 25, type: "Lab", equipment: ["Computers", "Wi-Fi", "Projector"], status: "Active" },
  { id: 4, name: "Auditorium B", building: "Building B", floor: "Ground Floor", capacity: 200, type: "Auditorium", equipment: ["Audio System", "Projector", "Stage"], status: "Active" },
  { id: 5, name: "Room D-202", building: "Building D", floor: "2nd Floor", capacity: 40, type: "Lecture", equipment: ["Projector", "Wi-Fi"], status: "Maintenance" },
  { id: 6, name: "Room E-101", building: "Building E", floor: "1st Floor", capacity: 20, type: "Seminar", equipment: ["TV Display", "Wi-Fi"], status: "Active" },
  { id: 7, name: "Room B-205", building: "Building B", floor: "2nd Floor", capacity: 45, type: "Lecture", equipment: ["Projector", "Whiteboard", "Wi-Fi", "Computer"], status: "Active" },
];

type PriorityTier = { id: number; role: string; color: string; description: string };
const initialTiers: PriorityTier[] = [
  { id: 1, role: "Faculty", color: "#891D1A", description: "Highest priority — auto-approved when possible" },
  { id: 2, role: "Club", color: "#5E657B", description: "Medium priority — reviewed within 24 hours" },
  { id: 3, role: "Student", color: "#B8860B", description: "Standard priority — reviewed within 48 hours" },
];

type MaintenanceEntry = { id: number; room: string; startDate: string; endDate: string; reason: string };

function Toggle({ checked, onChange }: { checked: boolean; onChange: (v: boolean) => void }) {
  return (
    <button
      onClick={() => onChange(!checked)}
      className="w-11 h-6 rounded-full transition-colors relative flex-shrink-0"
      style={{ background: checked ? "#891D1A" : "#5E657B" }}
    >
      <div
        className="w-4 h-4 bg-white rounded-full absolute top-1 transition-all"
        style={{ left: checked ? "calc(100% - 20px)" : "4px" }}
      />
    </button>
  );
}

function statusBadge(status: RoomStatus) {
  switch (status) {
    case "Active": return { bg: "#3B6E4A", label: "Active" };
    case "Blocked": return { bg: "#891D1A", label: "Blocked" };
    case "Maintenance": return { bg: "#B8860B", label: "Maintenance" };
  }
}

const emptyRoom: Omit<RoomItem, "id"> = {
  name: "", building: "", floor: "", capacity: 30,
  type: "Lecture", equipment: [], status: "Active",
};

export function AdminSettings() {
  const [activeCategory, setActiveCategory] = useState<Category>("general");
  const [rooms, setRooms] = useState<RoomItem[]>(initialRooms);
  const [autoApproveFaculty, setAutoApproveFaculty] = useState(false);
  const [conflictDetection, setConflictDetection] = useState(true);
  const [maintenanceBlock, setMaintenanceBlock] = useState(true);
  const [adminOverride, setAdminOverride] = useState(true);
  const [emailNotifs, setEmailNotifs] = useState(true);
  const [priorityTiers, setPriorityTiers] = useState<PriorityTier[]>(initialTiers);
  const [maintenanceEntries, setMaintenanceEntries] = useState<MaintenanceEntry[]>([
    { id: 1, room: "Room D-202", startDate: "2026-04-10", endDate: "2026-04-12", reason: "HVAC repairs" },
  ]);
  const [newMaint, setNewMaint] = useState({ room: "", startDate: "", endDate: "", reason: "" });

  // Room modal state
  const [roomModal, setRoomModal] = useState<{ open: boolean; mode: "add" | "edit"; data: Omit<RoomItem, "id">; editId?: number }>({
    open: false, mode: "add", data: { ...emptyRoom },
  });

  const openAddRoom = () => setRoomModal({ open: true, mode: "add", data: { ...emptyRoom } });
  const openEditRoom = (room: RoomItem) => setRoomModal({ open: true, mode: "edit", data: { name: room.name, building: room.building, floor: room.floor, capacity: room.capacity, type: room.type, equipment: [...room.equipment], status: room.status }, editId: room.id });
  const closeRoomModal = () => setRoomModal((prev) => ({ ...prev, open: false }));

  const toggleEquipment = (eq: string) => {
    setRoomModal((prev) => ({
      ...prev,
      data: {
        ...prev.data,
        equipment: prev.data.equipment.includes(eq)
          ? prev.data.equipment.filter((e) => e !== eq)
          : [...prev.data.equipment, eq],
      },
    }));
  };

  const saveRoom = () => {
    const { name, building, capacity } = roomModal.data;
    if (!name.trim() || !building.trim() || !capacity) {
      alert("Please fill all required fields.");
      return;
    }
    if (roomModal.mode === "add") {
      setRooms((prev) => [...prev, { id: Date.now(), ...roomModal.data }]);
    } else {
      setRooms((prev) => prev.map((r) => r.id === roomModal.editId ? { ...r, ...roomModal.data } : r));
    }
    closeRoomModal();
  };

  const deleteRoom = (id: number) => {
    if (confirm("Delete this room? This cannot be undone.")) {
      setRooms((prev) => prev.filter((r) => r.id !== id));
    }
  };

  const moveTier = (id: number, direction: "up" | "down") => {
    setPriorityTiers((prev) => {
      const idx = prev.findIndex((t) => t.id === id);
      if (direction === "up" && idx === 0) return prev;
      if (direction === "down" && idx === prev.length - 1) return prev;
      const next = [...prev];
      const swap = direction === "up" ? idx - 1 : idx + 1;
      [next[idx], next[swap]] = [next[swap], next[idx]];
      return next;
    });
  };

  const addMaintenance = () => {
    if (!newMaint.room || !newMaint.startDate || !newMaint.endDate) {
      alert("Please fill all required fields.");
      return;
    }
    setMaintenanceEntries((prev) => [...prev, { id: Date.now(), ...newMaint }]);
    setNewMaint({ room: "", startDate: "", endDate: "", reason: "" });
  };

  const deleteMaintenance = (id: number) =>
    setMaintenanceEntries((prev) => prev.filter((e) => e.id !== id));

  const inputCls =
    "w-full px-3 py-2 rounded-lg border bg-white dark:bg-[#3A1210] dark:text-[#F1E6D2] outline-none focus:ring-2 focus:ring-[#891D1A]/30 text-sm";

  return (
    <div style={DM_SANS}>
      <div className="mb-5">
        <h1 style={{ ...PLAYFAIR, fontSize: 28, fontWeight: 600 }} className="text-foreground">
          Admin Settings
        </h1>
        <p className="text-sm mt-1" style={{ color: "#5E657B" }}>
          Configure rooms, users, priorities, and system preferences
        </p>
      </div>

      <div className="flex gap-6">
        {/* Left category nav */}
        <div className="w-48 flex-shrink-0">
          <div className="bg-card rounded-xl shadow-sm overflow-hidden">
            {CATEGORIES.map((cat) => {
              const active = activeCategory === cat.key;
              return (
                <button
                  key={cat.key}
                  onClick={() => setActiveCategory(cat.key)}
                  className="w-full flex items-center gap-2.5 px-4 py-3 text-sm text-left border-l-2 transition-all"
                  style={
                    active
                      ? { borderColor: "#891D1A", color: "#891D1A", background: "rgba(137,29,26,0.05)" }
                      : { borderColor: "transparent", color: "#5E657B" }
                  }
                >
                  <cat.icon className="w-4 h-4 flex-shrink-0" />
                  {cat.label}
                </button>
              );
            })}
          </div>
        </div>

        {/* Right panel */}
        <div className="flex-1 min-w-0 space-y-5">
          {/* General */}
          {activeCategory === "general" && (
            <div className="bg-card rounded-xl shadow-sm overflow-hidden">
              <div className="px-5 py-4 border-b border-border">
                <h3 style={{ ...PLAYFAIR, fontSize: 16, fontWeight: 600 }} className="text-foreground">
                  General Settings
                </h3>
              </div>
              <div className="px-5 py-4 space-y-5">
                {[
                  { label: "Auto-approve Faculty Requests", desc: "Automatically approve booking requests from faculty members", checked: autoApproveFaculty, onChange: setAutoApproveFaculty },
                  { label: "Conflict Detection", desc: "Automatically detect and flag scheduling conflicts", checked: conflictDetection, onChange: setConflictDetection },
                ].map((item) => (
                  <div key={item.label} className="flex items-center justify-between gap-4 py-2 border-b border-border last:border-0">
                    <div>
                      <p className="text-sm font-medium text-foreground">{item.label}</p>
                      <p className="text-xs mt-0.5" style={{ color: "#5E657B" }}>{item.desc}</p>
                    </div>
                    <Toggle checked={item.checked} onChange={item.onChange} />
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Rooms */}
          {activeCategory === "rooms" && (
            <div className="bg-card rounded-xl shadow-sm overflow-hidden">
              <div className="px-5 py-4 border-b border-border flex items-center justify-between">
                <div>
                  <h3 style={{ ...PLAYFAIR, fontSize: 16, fontWeight: 600 }} className="text-foreground">
                    Room Management
                  </h3>
                  <p className="text-xs mt-0.5" style={{ color: "#5E657B" }}>{rooms.length} rooms configured</p>
                </div>
                <button
                  onClick={openAddRoom}
                  className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium text-white transition-colors"
                  style={{ background: "#891D1A" }}
                  onMouseEnter={(e) => (e.currentTarget.style.background = "#210706")}
                  onMouseLeave={(e) => (e.currentTarget.style.background = "#891D1A")}
                >
                  <Plus className="w-4 h-4" />
                  Add Room
                </button>
              </div>
              <div className="divide-y divide-border">
                {rooms.map((room) => {
                  const badge = statusBadge(room.status);
                  return (
                    <div key={room.id} className="px-5 py-4 flex items-start gap-4">
                      <div className="flex-1 min-w-0">
                        <div className="flex items-center gap-2 flex-wrap">
                          <p className="text-sm font-semibold text-foreground" style={PLAYFAIR}>{room.name}</p>
                          <span
                            className="text-xs px-2 py-0.5 rounded-full text-white font-medium"
                            style={{ background: badge.bg }}
                          >
                            {badge.label}
                          </span>
                        </div>
                        <p className="text-xs mt-0.5" style={{ color: "#5E657B" }}>
                          {room.building} · {room.floor} · {room.type} · {room.capacity} seats
                        </p>
                        {room.equipment.length > 0 && (
                          <div className="flex flex-wrap gap-1 mt-1.5">
                            {room.equipment.map((eq) => (
                              <span
                                key={eq}
                                className="text-xs px-1.5 py-0.5 rounded"
                                style={{ background: "rgba(241,230,210,0.6)", color: "#5E657B" }}
                              >
                                {eq}
                              </span>
                            ))}
                          </div>
                        )}
                      </div>
                      <div className="flex items-center gap-2 flex-shrink-0 pt-0.5">
                        <button
                          onClick={() => openEditRoom(room)}
                          className="flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium border transition-colors"
                          style={{ borderColor: "rgba(137,29,26,0.2)", color: "#5E657B" }}
                        >
                          <Edit2 className="w-3 h-3" /> Edit
                        </button>
                        <button
                          onClick={() => deleteRoom(room.id)}
                          className="w-7 h-7 rounded-lg flex items-center justify-center hover:bg-red-50 transition-colors"
                          style={{ color: "#891D1A" }}
                        >
                          <Trash2 className="w-3.5 h-3.5" />
                        </button>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          )}

          {/* Users */}
          {activeCategory === "users" && (
            <div className="bg-card rounded-xl shadow-sm overflow-hidden">
              <div className="px-5 py-4 border-b border-border">
                <h3 style={{ ...PLAYFAIR, fontSize: 16, fontWeight: 600 }} className="text-foreground">
                  User Management
                </h3>
              </div>
              <div className="px-5 py-6 text-sm" style={{ color: "#5E657B" }}>
                User management integration with your university's LDAP/SSO system.
                Direct user creation is handled by the IT department.
              </div>
            </div>
          )}

          {/* Priority Rules */}
          {activeCategory === "priority" && (
            <div className="space-y-4">
              <div className="bg-card rounded-xl shadow-sm overflow-hidden">
                <div className="px-5 py-4 border-b border-border">
                  <h3 style={{ ...PLAYFAIR, fontSize: 16, fontWeight: 600 }} className="text-foreground">
                    Priority Tiers
                  </h3>
                  <p className="text-xs mt-1" style={{ color: "#5E657B" }}>
                    Use arrows to reorder booking priority tiers
                  </p>
                </div>
                <div className="p-5 space-y-3">
                  {priorityTiers.map((tier, idx) => (
                    <div
                      key={tier.id}
                      className="flex items-center gap-4 rounded-xl p-4"
                      style={{ border: `2px solid ${tier.color}20`, background: tier.color + "08" }}
                    >
                      <div
                        className="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm flex-shrink-0"
                        style={{ background: tier.color }}
                      >
                        {idx + 1}
                      </div>
                      <div className="flex-1 min-w-0">
                        <p className="text-sm font-semibold" style={{ color: tier.color, ...PLAYFAIR }}>
                          {tier.role}
                        </p>
                        <p className="text-xs mt-0.5" style={{ color: "#5E657B" }}>{tier.description}</p>
                      </div>
                      <div className="flex flex-col gap-1">
                        <button
                          onClick={() => moveTier(tier.id, "up")}
                          disabled={idx === 0}
                          className="w-7 h-7 rounded flex items-center justify-center disabled:opacity-30 hover:bg-[#891D1A]/10"
                          style={{ color: "#5E657B" }}
                        >
                          <ArrowUp className="w-3.5 h-3.5" />
                        </button>
                        <button
                          onClick={() => moveTier(tier.id, "down")}
                          disabled={idx === priorityTiers.length - 1}
                          className="w-7 h-7 rounded flex items-center justify-center disabled:opacity-30 hover:bg-[#891D1A]/10"
                          style={{ color: "#5E657B" }}
                        >
                          <ArrowDown className="w-3.5 h-3.5" />
                        </button>
                      </div>
                    </div>
                  ))}
                </div>
              </div>

              {/* System Rules */}
              <div className="bg-card rounded-xl shadow-sm overflow-hidden">
                <div className="px-5 py-4 border-b border-border flex items-center gap-2">
                  <AlertTriangle className="w-4 h-4" style={{ color: "#B8860B" }} />
                  <h3 style={{ ...PLAYFAIR, fontSize: 16, fontWeight: 600 }} className="text-foreground">
                    System Rules
                  </h3>
                </div>
                <div className="px-5 py-4 space-y-5">
                  {[
                    {
                      label: "Conflict Detection Enabled",
                      desc: "Automatically detect and flag scheduling conflicts across all bookings",
                      checked: conflictDetection,
                      onChange: setConflictDetection,
                    },
                    {
                      label: "Maintenance Block Prevents Booking",
                      desc: "Rooms under maintenance cannot be booked by any user",
                      checked: maintenanceBlock,
                      onChange: setMaintenanceBlock,
                    },
                    {
                      label: "Admin Can Override Conflicts",
                      desc: "Administrators can approve bookings even when a scheduling conflict is detected",
                      checked: adminOverride,
                      onChange: setAdminOverride,
                    },
                  ].map((item) => (
                    <div key={item.label} className="flex items-center justify-between gap-4 py-2 border-b border-border last:border-0">
                      <div>
                        <p className="text-sm font-medium text-foreground">{item.label}</p>
                        <p className="text-xs mt-0.5" style={{ color: "#5E657B" }}>{item.desc}</p>
                      </div>
                      <Toggle checked={item.checked} onChange={item.onChange} />
                    </div>
                  ))}
                </div>
              </div>
            </div>
          )}

          {/* Maintenance Scheduler */}
          {activeCategory === "maintenance" && (
            <div className="space-y-4">
              <div className="bg-card rounded-xl shadow-sm overflow-hidden">
                <div className="px-5 py-4 border-b border-border">
                  <h3 style={{ ...PLAYFAIR, fontSize: 16, fontWeight: 600 }} className="text-foreground">
                    Schedule Maintenance
                  </h3>
                </div>
                <div className="px-5 py-4 space-y-4">
                  <div className="space-y-1.5">
                    <label className="text-sm font-medium" style={{ color: "#5E657B" }}>Room</label>
                    <select
                      value={newMaint.room}
                      onChange={(e) => setNewMaint((p) => ({ ...p, room: e.target.value }))}
                      className={inputCls}
                      style={{ borderColor: "rgba(137,29,26,0.2)" }}
                    >
                      <option value="">Select a room…</option>
                      {rooms.map((r) => (
                        <option key={r.id} value={r.name}>{r.name}</option>
                      ))}
                    </select>
                  </div>
                  <div className="grid grid-cols-2 gap-3">
                    <div className="space-y-1.5">
                      <label className="text-sm font-medium" style={{ color: "#5E657B" }}>Start Date</label>
                      <input
                        type="date"
                        value={newMaint.startDate}
                        onChange={(e) => setNewMaint((p) => ({ ...p, startDate: e.target.value }))}
                        className={inputCls}
                        style={{ borderColor: "rgba(137,29,26,0.2)" }}
                      />
                    </div>
                    <div className="space-y-1.5">
                      <label className="text-sm font-medium" style={{ color: "#5E657B" }}>End Date</label>
                      <input
                        type="date"
                        value={newMaint.endDate}
                        onChange={(e) => setNewMaint((p) => ({ ...p, endDate: e.target.value }))}
                        className={inputCls}
                        style={{ borderColor: "rgba(137,29,26,0.2)" }}
                      />
                    </div>
                  </div>
                  <div className="space-y-1.5">
                    <label className="text-sm font-medium" style={{ color: "#5E657B" }}>Reason</label>
                    <input
                      type="text"
                      placeholder="e.g. HVAC repairs"
                      value={newMaint.reason}
                      onChange={(e) => setNewMaint((p) => ({ ...p, reason: e.target.value }))}
                      className={inputCls}
                      style={{ borderColor: "rgba(137,29,26,0.2)" }}
                    />
                  </div>
                  <button
                    onClick={addMaintenance}
                    className="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-white"
                    style={{ background: "#891D1A" }}
                    onMouseEnter={(e) => (e.currentTarget.style.background = "#210706")}
                    onMouseLeave={(e) => (e.currentTarget.style.background = "#891D1A")}
                  >
                    <Plus className="w-4 h-4" />
                    Add Maintenance Block
                  </button>
                </div>
              </div>

              {maintenanceEntries.length > 0 && (
                <div className="bg-card rounded-xl shadow-sm overflow-hidden">
                  <div className="px-5 py-4 border-b border-border">
                    <h3 style={{ ...PLAYFAIR, fontSize: 16, fontWeight: 600 }} className="text-foreground">
                      Scheduled Maintenance
                    </h3>
                  </div>
                  <div className="divide-y divide-border">
                    {maintenanceEntries.map((entry) => (
                      <div key={entry.id} className="px-5 py-4 flex items-center gap-4">
                        <Wrench className="w-4 h-4 flex-shrink-0" style={{ color: "#B8860B" }} />
                        <div className="flex-1 min-w-0">
                          <p className="text-sm font-semibold text-foreground" style={PLAYFAIR}>{entry.room}</p>
                          <p className="text-xs mt-0.5" style={{ color: "#5E657B" }}>
                            {entry.startDate} → {entry.endDate}
                            {entry.reason && ` · ${entry.reason}`}
                          </p>
                        </div>
                        <button
                          onClick={() => deleteMaintenance(entry.id)}
                          className="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-red-50 transition-colors"
                          style={{ color: "#891D1A" }}
                        >
                          <Trash2 className="w-4 h-4" />
                        </button>
                      </div>
                    ))}
                  </div>
                </div>
              )}
            </div>
          )}

          {/* Notifications */}
          {activeCategory === "notifications" && (
            <div className="bg-card rounded-xl shadow-sm overflow-hidden">
              <div className="px-5 py-4 border-b border-border">
                <h3 style={{ ...PLAYFAIR, fontSize: 16, fontWeight: 600 }} className="text-foreground">
                  Notification Preferences
                </h3>
              </div>
              <div className="px-5 py-4 space-y-5">
                {[
                  { label: "Email Notifications", desc: "Send email alerts for booking status changes", checked: emailNotifs, onChange: setEmailNotifs },
                ].map((item) => (
                  <div key={item.label} className="flex items-center justify-between gap-4 py-2">
                    <div>
                      <p className="text-sm font-medium text-foreground">{item.label}</p>
                      <p className="text-xs mt-0.5" style={{ color: "#5E657B" }}>{item.desc}</p>
                    </div>
                    <Toggle checked={item.checked} onChange={item.onChange} />
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Add/Edit Room Modal */}
      {roomModal.open && (
        <div className="fixed inset-0 z-50 flex items-center justify-center" style={{ background: "rgba(0,0,0,0.4)" }}>
          <div
            className="bg-card rounded-2xl shadow-xl w-full max-w-lg mx-4 overflow-hidden"
            style={DM_SANS}
          >
            {/* Modal header */}
            <div className="flex items-center justify-between px-6 py-4 border-b border-border">
              <h2 style={{ ...PLAYFAIR, fontSize: 18, fontWeight: 600 }} className="text-foreground">
                {roomModal.mode === "add" ? "Add New Room" : "Edit Room"}
              </h2>
              <button
                onClick={closeRoomModal}
                className="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-[#891D1A]/10 transition-colors"
                style={{ color: "#5E657B" }}
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            {/* Modal body */}
            <div className="px-6 py-5 space-y-4 max-h-[70vh] overflow-y-auto">
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-1.5 col-span-2">
                  <label className="text-sm font-medium" style={{ color: "#5E657B" }}>
                    Room Name <span style={{ color: "#891D1A" }}>*</span>
                  </label>
                  <input
                    type="text"
                    placeholder="e.g. Room A-301"
                    value={roomModal.data.name}
                    onChange={(e) => setRoomModal((p) => ({ ...p, data: { ...p.data, name: e.target.value } }))}
                    className={inputCls}
                    style={{ borderColor: "rgba(137,29,26,0.2)" }}
                  />
                </div>

                <div className="space-y-1.5">
                  <label className="text-sm font-medium" style={{ color: "#5E657B" }}>
                    Building <span style={{ color: "#891D1A" }}>*</span>
                  </label>
                  <input
                    type="text"
                    placeholder="e.g. Building A"
                    value={roomModal.data.building}
                    onChange={(e) => setRoomModal((p) => ({ ...p, data: { ...p.data, building: e.target.value } }))}
                    className={inputCls}
                    style={{ borderColor: "rgba(137,29,26,0.2)" }}
                  />
                </div>

                <div className="space-y-1.5">
                  <label className="text-sm font-medium" style={{ color: "#5E657B" }}>Floor</label>
                  <input
                    type="text"
                    placeholder="e.g. 3rd Floor"
                    value={roomModal.data.floor}
                    onChange={(e) => setRoomModal((p) => ({ ...p, data: { ...p.data, floor: e.target.value } }))}
                    className={inputCls}
                    style={{ borderColor: "rgba(137,29,26,0.2)" }}
                  />
                </div>

                <div className="space-y-1.5">
                  <label className="text-sm font-medium" style={{ color: "#5E657B" }}>
                    Capacity <span style={{ color: "#891D1A" }}>*</span>
                  </label>
                  <input
                    type="number"
                    min={1}
                    placeholder="e.g. 30"
                    value={roomModal.data.capacity || ""}
                    onChange={(e) => setRoomModal((p) => ({ ...p, data: { ...p.data, capacity: Number(e.target.value) } }))}
                    className={inputCls}
                    style={{ borderColor: "rgba(137,29,26,0.2)" }}
                  />
                </div>

                <div className="space-y-1.5">
                  <label className="text-sm font-medium" style={{ color: "#5E657B" }}>Room Type</label>
                  <select
                    value={roomModal.data.type}
                    onChange={(e) => setRoomModal((p) => ({ ...p, data: { ...p.data, type: e.target.value as RoomType } }))}
                    className={inputCls}
                    style={{ borderColor: "rgba(137,29,26,0.2)" }}
                  >
                    {ROOM_TYPES.map((t) => <option key={t} value={t}>{t}</option>)}
                  </select>
                </div>

                <div className="space-y-1.5">
                  <label className="text-sm font-medium" style={{ color: "#5E657B" }}>Status</label>
                  <select
                    value={roomModal.data.status}
                    onChange={(e) => setRoomModal((p) => ({ ...p, data: { ...p.data, status: e.target.value as RoomStatus } }))}
                    className={inputCls}
                    style={{ borderColor: "rgba(137,29,26,0.2)" }}
                  >
                    <option value="Active">Active</option>
                    <option value="Blocked">Blocked</option>
                    <option value="Maintenance">Maintenance</option>
                  </select>
                </div>
              </div>

              <div className="space-y-2">
                <label className="text-sm font-medium" style={{ color: "#5E657B" }}>Equipment</label>
                <div className="flex flex-wrap gap-2">
                  {EQUIPMENT_OPTIONS.map((eq) => {
                    const selected = roomModal.data.equipment.includes(eq);
                    return (
                      <button
                        key={eq}
                        onClick={() => toggleEquipment(eq)}
                        className="flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium border transition-all"
                        style={
                          selected
                            ? { background: "#891D1A", borderColor: "#891D1A", color: "#fff" }
                            : { background: "transparent", borderColor: "rgba(137,29,26,0.25)", color: "#5E657B" }
                        }
                      >
                        {selected && <Check className="w-3 h-3" />}
                        {eq}
                      </button>
                    );
                  })}
                </div>
              </div>
            </div>

            {/* Modal footer */}
            <div className="flex justify-end gap-3 px-6 py-4 border-t border-border">
              <button
                onClick={closeRoomModal}
                className="px-4 py-2 rounded-lg text-sm font-medium border transition-colors"
                style={{ borderColor: "rgba(137,29,26,0.3)", color: "#5E657B" }}
              >
                Cancel
              </button>
              <button
                onClick={saveRoom}
                className="px-5 py-2 rounded-lg text-sm font-medium text-white transition-colors"
                style={{ background: "#891D1A" }}
                onMouseEnter={(e) => (e.currentTarget.style.background = "#210706")}
                onMouseLeave={(e) => (e.currentTarget.style.background = "#891D1A")}
              >
                {roomModal.mode === "add" ? "Add Room" : "Save Changes"}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
