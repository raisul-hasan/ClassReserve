import { useNavigate, useParams } from "react-router";
import { ArrowLeft, Users, Wifi, Monitor, Volume2, Cpu, PenLine, Wind } from "lucide-react";
import { useAuth } from "../context/AuthContext";

const PLAYFAIR = { fontFamily: "'Playfair Display', serif" } as const;
const DM_SANS = { fontFamily: "'DM Sans', sans-serif" } as const;

const roomData: Record<string, {
  name: string; building: string; floor: string; capacity: number;
  status: string; type: string;
  equipment: { label: string; icon: React.ElementType }[];
}> = {
  "Room A-301": {
    name: "Room A-301", building: "Building A", floor: "3rd Floor",
    capacity: 30, status: "available", type: "Lecture Room",
    equipment: [
      { label: "Projector", icon: Monitor },
      { label: "Wi-Fi", icon: Wifi },
      { label: "Whiteboard", icon: PenLine },
      { label: "AC", icon: Wind },
    ],
  },
  "Lab C-105": {
    name: "Lab C-105", building: "Building C", floor: "1st Floor",
    capacity: 25, status: "available", type: "Computer Lab",
    equipment: [
      { label: "Computers", icon: Cpu },
      { label: "Wi-Fi", icon: Wifi },
      { label: "Projector", icon: Monitor },
      { label: "AC", icon: Wind },
    ],
  },
  "Auditorium B": {
    name: "Auditorium B", building: "Building B", floor: "Ground Floor",
    capacity: 200, status: "available", type: "Auditorium",
    equipment: [
      { label: "Audio System", icon: Volume2 },
      { label: "Projector", icon: Monitor },
      { label: "Wi-Fi", icon: Wifi },
      { label: "AC", icon: Wind },
    ],
  },
};

const DAYS = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];
const SLOTS = ["8 AM", "10 AM", "12 PM", "2 PM", "4 PM", "6 PM"];

const sampleAvailability = [
  [1, 1, 0, 0, 1, 1],
  [0, 1, 1, 0, 0, 1],
  [1, 0, 0, 1, 1, 1],
  [1, 1, 1, 0, 0, 1],
  [0, 0, 1, 1, 1, 1],
  [1, 1, 1, 1, 0, 1],
  [1, 1, 0, 1, 1, 1],
];

function statusStyle(status: string) {
  if (status === "available") return { bg: "#3B6E4A", label: "Available" };
  if (status === "booked") return { bg: "#891D1A", label: "Booked" };
  return { bg: "#B8860B", label: "Maintenance" };
}

export function RoomDetail() {
  const { roomName } = useParams<{ roomName: string }>();
  const navigate = useNavigate();
  const { user } = useAuth();

  const decodedName = decodeURIComponent(roomName || "");
  const room = roomData[decodedName];
  const base = user?.role === "faculty" ? "/faculty" : user?.role === "admin" ? "/admin" : "/student";

  if (!room) {
    return (
      <div className="text-center py-20" style={DM_SANS}>
        <p className="text-lg text-foreground">Room not found.</p>
        <button
          className="mt-4 text-sm font-medium"
          style={{ color: "#891D1A" }}
          onClick={() => navigate(-1)}
        >
          Go back
        </button>
      </div>
    );
  }

  const ss = statusStyle(room.status);

  return (
    <div className="space-y-6 max-w-3xl" style={DM_SANS}>
      <div className="flex items-center gap-3">
        <button
          onClick={() => navigate(-1)}
          className="w-9 h-9 rounded-lg flex items-center justify-center border hover:bg-[#891D1A]/10 transition-colors"
          style={{ borderColor: "rgba(137,29,26,0.3)", color: "#5E657B" }}
        >
          <ArrowLeft className="w-4 h-4" />
        </button>
        <div>
          <h1 style={{ ...PLAYFAIR, fontSize: 28, fontWeight: 600 }} className="text-foreground">
            {room.name}
          </h1>
          <p className="text-sm mt-0.5" style={{ color: "#5E657B" }}>
            {room.building} · {room.floor} · {room.type}
          </p>
        </div>
      </div>

      <div className="bg-card rounded-xl shadow-sm overflow-hidden">
        <div className="p-6">
          <div className="flex items-center gap-4 flex-wrap mb-6">
            <span
              className="text-sm px-3 py-1.5 rounded-full text-white font-medium"
              style={{ background: ss.bg }}
            >
              {ss.label}
            </span>
            <div className="flex items-center gap-1.5 text-sm" style={{ color: "#5E657B" }}>
              <Users className="w-4 h-4" />
              {room.capacity} seats
            </div>
          </div>

          <h3 style={{ ...PLAYFAIR, fontSize: 16, fontWeight: 600 }} className="text-foreground mb-3">
            Equipment & Amenities
          </h3>
          <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
            {room.equipment.map((eq) => (
              <div
                key={eq.label}
                className="flex flex-col items-center gap-2 p-4 rounded-xl"
                style={{ background: "rgba(137,29,26,0.05)", border: "1px solid rgba(137,29,26,0.1)" }}
              >
                <eq.icon className="w-5 h-5" style={{ color: "#891D1A" }} />
                <span className="text-xs font-medium" style={{ color: "#5E657B" }}>{eq.label}</span>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Weekly availability grid */}
      <div className="bg-card rounded-xl shadow-sm overflow-hidden">
        <div className="px-5 py-4 border-b border-border">
          <h3 style={{ ...PLAYFAIR, fontSize: 16, fontWeight: 600 }} className="text-foreground">
            Weekly Availability
          </h3>
        </div>
        <div className="p-5 overflow-x-auto">
          <div className="min-w-[480px]">
            <div className="grid grid-cols-8 gap-1 mb-1">
              <div className="text-xs" style={{ color: "#5E657B" }} />
              {DAYS.map((d) => (
                <div key={d} className="text-center text-xs font-medium" style={{ color: "#5E657B" }}>
                  {d}
                </div>
              ))}
            </div>
            {SLOTS.map((slot, si) => (
              <div key={slot} className="grid grid-cols-8 gap-1 mb-1">
                <div className="text-xs flex items-center" style={{ color: "#5E657B" }}>{slot}</div>
                {DAYS.map((_, di) => {
                  const avail = sampleAvailability[di][si];
                  return (
                    <div
                      key={di}
                      className="h-8 rounded"
                      style={{ background: avail ? "#3B6E4A" : "#891D1A", opacity: avail ? 0.7 : 0.5 }}
                      title={avail ? "Available" : "Booked"}
                    />
                  );
                })}
              </div>
            ))}
            <div className="flex items-center gap-4 mt-3 text-xs" style={{ color: "#5E657B" }}>
              <div className="flex items-center gap-1.5">
                <div className="w-3 h-3 rounded" style={{ background: "#3B6E4A" }} /> Available
              </div>
              <div className="flex items-center gap-1.5">
                <div className="w-3 h-3 rounded" style={{ background: "#891D1A" }} /> Booked
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Sticky Book CTA */}
      <div className="sticky bottom-6">
        <button
          onClick={() => navigate(`${base}/new-booking`, { state: { roomName: room.name } })}
          disabled={room.status !== "available"}
          className="w-full py-3 rounded-full text-[#F1E6D2] font-medium text-sm disabled:opacity-40 disabled:cursor-not-allowed transition-colors shadow-lg"
          style={{ ...PLAYFAIR, background: "#891D1A", fontSize: 15 }}
          onMouseEnter={(e) => { if (room.status === "available") e.currentTarget.style.background = "#210706"; }}
          onMouseLeave={(e) => { if (room.status === "available") e.currentTarget.style.background = "#891D1A"; }}
        >
          Book This Room
        </button>
      </div>
    </div>
  );
}
