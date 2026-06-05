import { useEffect, useMemo, useState } from "react";
import { Trash2, Wrench } from "lucide-react";
import { createMaintenanceBlock, getAvailableRooms, getMaintenanceBlocks } from "../services/classReserveService";
import type { MaintenanceBlock, Room } from "../types/classReserve";

const PLAYFAIR = { fontFamily: "'Playfair Display', serif" } as const;
const DM_SANS = { fontFamily: "'DM Sans', sans-serif" } as const;

const inputCls = "w-full px-3 py-2.5 rounded-lg border bg-white dark:bg-[#3A1210] dark:text-[#F1E6D2] outline-none focus:ring-2 focus:ring-[#891D1A]/30 text-sm";
const borderStyle = { borderColor: "rgba(137,29,26,0.2)" };

export function Maintenance() {
  const [rooms, setRooms] = useState<Room[]>([]);
  const [blocks, setBlocks] = useState<MaintenanceBlock[]>([]);
  const [roomId, setRoomId] = useState("");
  const [startDate, setStartDate] = useState("");
  const [startTime, setStartTime] = useState("08:00");
  const [endDate, setEndDate] = useState("");
  const [endTime, setEndTime] = useState("17:00");
  const [reason, setReason] = useState("");
  const [filterRoom, setFilterRoom] = useState("all");
  const [message, setMessage] = useState("");
  const [isSaving, setIsSaving] = useState(false);

  useEffect(() => {
    getAvailableRooms().then(setRooms).catch(() => setRooms([]));
    getMaintenanceBlocks().then(setBlocks).catch(() => setBlocks([]));
  }, []);

  const filteredBlocks = useMemo(() => {
    if (filterRoom === "all") return blocks;
    return blocks.filter((block) => String(block.roomId) === filterRoom || block.roomName === filterRoom);
  }, [blocks, filterRoom]);

  const selectedRoom = rooms.find((room) => String(room.id) === roomId);

  const resetForm = () => {
    setRoomId("");
    setStartDate("");
    setStartTime("08:00");
    setEndDate("");
    setEndTime("17:00");
    setReason("");
  };

  const handleBlockRoom = async () => {
    setMessage("");
    if (!selectedRoom || !startDate || !startTime || !endDate || !endTime || !reason.trim()) {
      setMessage("Please choose a room, date/time range, and reason.");
      return;
    }
    const start = `${startDate} ${startTime}:00`;
    const end = `${endDate} ${endTime}:00`;
    if (new Date(start.replace(" ", "T")) >= new Date(end.replace(" ", "T"))) {
      setMessage("End date/time must be after the start date/time.");
      return;
    }

    setIsSaving(true);
    try {
      const created = await createMaintenanceBlock({
        roomId: selectedRoom.id,
        roomName: selectedRoom.name,
        startDateTime: start,
        endDateTime: end,
        reason: reason.trim(),
      });
      setBlocks((prev) => [
        { id: Number((created as any).id || Date.now()), roomId: selectedRoom.id, roomName: selectedRoom.name, startDateTime: start, endDateTime: end, reason: reason.trim() },
        ...prev,
      ]);
      setMessage(`Maintenance block created for ${selectedRoom.name}.`);
      resetForm();
    } catch (error) {
      setMessage(error instanceof Error ? error.message : "Could not create maintenance block.");
    } finally {
      setIsSaving(false);
    }
  };

  const removeBlock = (id: number) => {
    setBlocks((prev) => prev.filter((block) => block.id !== id));
    setMessage("Maintenance block removed locally.");
  };

  return (
    <div className="space-y-5" style={DM_SANS}>
      <div>
        <h1 style={{ ...PLAYFAIR, fontSize: 28, fontWeight: 600 }} className="text-foreground">
          Maintenance
        </h1>
        <p className="text-sm mt-1" style={{ color: "#5E657B" }}>
          Block rooms for maintenance and review scheduled room downtime
        </p>
      </div>

      {message && (
        <div className="rounded-lg px-4 py-3 text-sm font-medium" style={{ background: "rgba(137,29,26,0.08)", color: "#891D1A" }}>
          {message}
        </div>
      )}

      <div className="bg-card rounded-xl p-5 shadow-sm space-y-4">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div className="space-y-1.5">
            <label className="text-sm font-medium" style={{ color: "#5E657B" }}>Room</label>
            <select value={roomId} onChange={(e) => setRoomId(e.target.value)} className={inputCls} style={borderStyle}>
              <option value="">Select room</option>
              {rooms.map((room) => <option key={room.id} value={room.id}>{room.name}</option>)}
            </select>
          </div>
          <div className="space-y-1.5">
            <label className="text-sm font-medium" style={{ color: "#5E657B" }}>Reason</label>
            <input value={reason} onChange={(e) => setReason(e.target.value)} className={inputCls} style={borderStyle} placeholder="Projector repair, cleaning, HVAC..." />
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div className="space-y-1.5">
              <label className="text-sm font-medium" style={{ color: "#5E657B" }}>Start Date</label>
              <input type="date" value={startDate} onChange={(e) => setStartDate(e.target.value)} className={inputCls} style={borderStyle} />
            </div>
            <div className="space-y-1.5">
              <label className="text-sm font-medium" style={{ color: "#5E657B" }}>Start Time</label>
              <input type="time" value={startTime} onChange={(e) => setStartTime(e.target.value)} className={inputCls} style={borderStyle} />
            </div>
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div className="space-y-1.5">
              <label className="text-sm font-medium" style={{ color: "#5E657B" }}>End Date</label>
              <input type="date" value={endDate} onChange={(e) => setEndDate(e.target.value)} className={inputCls} style={borderStyle} />
            </div>
            <div className="space-y-1.5">
              <label className="text-sm font-medium" style={{ color: "#5E657B" }}>End Time</label>
              <input type="time" value={endTime} onChange={(e) => setEndTime(e.target.value)} className={inputCls} style={borderStyle} />
            </div>
          </div>
        </div>
        <div className="flex justify-end gap-3">
          <button onClick={resetForm} className="px-4 py-2.5 rounded-lg text-sm font-medium border" style={{ borderColor: "rgba(137,29,26,0.3)", color: "#5E657B" }}>
            Cancel
          </button>
          <button onClick={handleBlockRoom} disabled={isSaving} className="px-5 py-2.5 rounded-lg text-sm font-medium text-white flex items-center gap-2 disabled:opacity-50" style={{ background: "#891D1A" }}>
            <Wrench className="w-4 h-4" />
            {isSaving ? "Blocking..." : "Block Room"}
          </button>
        </div>
      </div>

      <div className="bg-card rounded-xl shadow-sm overflow-hidden">
        <div className="px-5 py-4 border-b border-border flex items-center justify-between gap-3 flex-wrap">
          <h2 style={{ ...PLAYFAIR, fontSize: 18, fontWeight: 600 }} className="text-foreground">Maintenance Blocks</h2>
          <select value={filterRoom} onChange={(e) => setFilterRoom(e.target.value)} className="px-3 py-2 rounded-lg text-sm border bg-white dark:bg-[#3A1210] dark:text-[#F1E6D2]" style={borderStyle}>
            <option value="all">All Rooms</option>
            {rooms.map((room) => <option key={room.id} value={room.id}>{room.name}</option>)}
          </select>
        </div>
        <div className="divide-y divide-border">
          {filteredBlocks.length === 0 && <div className="py-10 text-center text-sm" style={{ color: "#5E657B" }}>No maintenance blocks match this filter.</div>}
          {filteredBlocks.map((block) => (
            <div key={block.id} className="px-5 py-4 flex items-start justify-between gap-4">
              <div>
                <p className="text-sm font-semibold text-foreground">{block.roomName}</p>
                <p className="text-xs mt-1" style={{ color: "#5E657B" }}>{block.startDateTime} to {block.endDateTime}</p>
                <p className="text-sm mt-2" style={{ color: "#5E657B" }}>{block.reason}</p>
              </div>
              <button onClick={() => removeBlock(block.id)} className="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-[#891D1A]/10" style={{ color: "#891D1A" }}>
                <Trash2 className="w-4 h-4" />
              </button>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}
