import { useEffect, useState } from "react";
import { useLocation, useNavigate } from "react-router";
import { ArrowLeft, ArrowRight, Check, Users, Upload } from "lucide-react";
import { useAuth } from "../context/AuthContext";
import { createBooking, getAvailableRooms } from "../services/classReserveService";

const PLAYFAIR = { fontFamily: "'Playfair Display', serif" } as const;
const DM_SANS = { fontFamily: "'DM Sans', sans-serif" } as const;

const roomOptions = [
  { name: "Room A-301", capacity: 30, building: "Building A", type: "Lecture", status: "available", equipment: ["Projector", "Whiteboard", "Wi-Fi"] },
  { name: "Lab C-105", capacity: 25, building: "Building C", type: "Lab", status: "available", equipment: ["Computers", "Wi-Fi", "Projector"] },
  { name: "Auditorium B", capacity: 200, building: "Building B", type: "Auditorium", status: "available", equipment: ["Audio System", "Projector", "Stage"] },
  { name: "Room E-101", capacity: 20, building: "Building E", type: "Seminar", status: "available", equipment: ["TV Display", "Wi-Fi"] },
  { name: "Room B-205", capacity: 45, building: "Building B", type: "Lecture", status: "available", equipment: ["Projector", "Whiteboard", "Wi-Fi", "Computer"] },
];

function StepDot({ step, current }: { step: number; current: number }) {
  const active = step === current;
  const done = step < current;
  return (
    <div className="flex items-center gap-2">
      <div
        className="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold transition-all"
        style={{
          background: done ? "#3B6E4A" : active ? "#891D1A" : "rgba(94,101,123,0.15)",
          color: done || active ? "#fff" : "#5E657B",
        }}
      >
        {done ? <Check className="w-4 h-4" /> : step}
      </div>
      {step < 3 && (
        <div
          className="flex-1 h-0.5 min-w-[48px]"
          style={{ background: step < current ? "#3B6E4A" : "rgba(94,101,123,0.2)" }}
        />
      )}
    </div>
  );
}

export function NewBooking() {
  const navigate = useNavigate();
  const location = useLocation();
  const { user } = useAuth();

  const prefilledRoom = (location.state as { roomName?: string } | null)?.roomName || "";
  const hasPrefilledRoom = Boolean(prefilledRoom);

  const [step, setStep] = useState(1);
  const [date, setDate] = useState("");
  const [startTime, setStartTime] = useState("");
  const [endTime, setEndTime] = useState("");
  const [minCapacity, setMinCapacity] = useState(1);
  const [selectedRoom, setSelectedRoom] = useState(prefilledRoom);
  const [eventName, setEventName] = useState("");
  const [description, setDescription] = useState("");
  const [attendees, setAttendees] = useState("");
  const [attachment, setAttachment] = useState<File | null>(null);
  const [message, setMessage] = useState("");
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [availableRooms, setAvailableRooms] = useState(roomOptions);

  useEffect(() => {
    setAvailableRooms(roomOptions.filter((r) => r.status === "available" && r.capacity >= minCapacity));
  }, [minCapacity]);

  const base = user?.role === "faculty" ? "/faculty" : user?.role === "admin" ? "/admin" : user?.role === "club" ? "/club" : "/student";

  const handleBack = () => {
    if (step === 1) navigate(`${base}/rooms`);
    else setStep((s) => s - 1);
  };

  const handleNext = async () => {
    setMessage("");
    if (step === 1) {
      if (!date || !startTime || !endTime) { setMessage("Please select date and time."); return; }
      if (startTime >= endTime) { setMessage("End time must be after start time."); return; }
      if (!hasPrefilledRoom) {
        setIsSubmitting(true);
        try {
          const rooms = await getAvailableRooms({ date, startTime, endTime, minimumCapacity: minCapacity });
          setAvailableRooms(rooms.filter((room) => room.status === "available"));
        } catch {
          setAvailableRooms(roomOptions.filter((r) => r.status === "available" && r.capacity >= minCapacity));
        } finally {
          setIsSubmitting(false);
        }
      }
      setStep(hasPrefilledRoom ? 3 : 2);
      return;
    }
    if (step === 2) {
      if (!selectedRoom) { setMessage("Please select a room."); return; }
    }
    if (step === 3) {
      if (!eventName.trim() || !attendees) { setMessage("Please fill all required fields."); return; }
      setIsSubmitting(true);
      try {
        await createBooking({
          title: eventName,
          roomName: selectedRoom,
          date,
          startTime,
          endTime,
          attendees: Number(attendees),
          requesterRole: user?.role || "student",
          requesterName: user?.name || "Requester",
          requesterId: user?.id,
          requesterEmail: user?.email,
          hasDocument: Boolean(attachment),
          attachment,
          description,
        } as any);
        navigate(`${base}/booking-confirmation`, { state: { room: selectedRoom, date, time: `${startTime} - ${endTime}`, event: eventName } });
      } catch (error) {
        setMessage(error instanceof Error ? error.message : "Could not submit booking request.");
      } finally {
        setIsSubmitting(false);
      }
      return;
    }
    setStep((s) => s + 1);
  };

  const priorityLabel =
    user?.role === "faculty" ? "Faculty (High Priority)" :
    user?.role === "club" ? "Club (Medium Priority)" :
    user?.role === "admin" ? "Admin" : "Student (Standard)";

  return (
    <div className="space-y-6 max-w-2xl mx-auto" style={DM_SANS}>
      {/* Header */}
      <div className="flex items-center gap-3">
        <button
          onClick={handleBack}
          className="w-9 h-9 rounded-lg flex items-center justify-center border transition-colors hover:bg-[#891D1A]/10"
          style={{ borderColor: "rgba(137,29,26,0.3)", color: "#5E657B" }}
        >
          <ArrowLeft className="w-4 h-4" />
        </button>
        <div>
          <h1 style={{ ...PLAYFAIR, fontSize: 24, fontWeight: 600 }} className="text-foreground">
            New Booking
          </h1>
          <p className="text-sm mt-0.5" style={{ color: "#5E657B" }}>
            {hasPrefilledRoom ? `Reserve ${prefilledRoom}` : "Submit a classroom reservation request"}
          </p>
        </div>
      </div>

      {message && (
        <div
          className="rounded-lg px-4 py-3 text-sm font-medium"
          style={{ background: "rgba(137,29,26,0.08)", color: "#891D1A" }}
        >
          {message}
        </div>
      )}

      {/* Step progress */}
      <div className="bg-card rounded-xl p-5 shadow-sm">
        <div className="flex items-center">
          <StepDot step={1} current={step} />
          <StepDot step={2} current={step} />
          <StepDot step={3} current={step} />
        </div>
        <div className="flex justify-between mt-2">
          {(hasPrefilledRoom ? ["Choose Time", "Room Selected", "Booking Details"] : ["Find a Room", "Select Room", "Booking Details"]).map((label, i) => (
            <span
              key={label}
              className="text-xs"
              style={{ color: step === i + 1 ? "#891D1A" : "#5E657B", fontWeight: step === i + 1 ? 600 : 400 }}
            >
              {label}
            </span>
          ))}
        </div>
      </div>

      {/* Step 1 */}
      {step === 1 && (
        <div className="bg-card rounded-xl p-6 shadow-sm space-y-5">
          <h2 style={{ ...PLAYFAIR, fontSize: 20, fontWeight: 600 }} className="text-foreground">
            {hasPrefilledRoom ? `When do you need ${prefilledRoom}?` : "When do you need a room?"}
          </h2>

          {hasPrefilledRoom && (
            <div
              className="rounded-lg p-3 text-sm"
              style={{ background: "rgba(137,29,26,0.05)", border: "1px solid rgba(137,29,26,0.15)" }}
            >
              <span style={{ color: "#5E657B" }}>Selected room: </span>
              <span className="font-semibold text-foreground">{prefilledRoom}</span>
            </div>
          )}

          <div className="space-y-1.5">
            <label className="text-sm font-medium" style={{ color: "#5E657B" }}>Date</label>
            <input
              type="date"
              value={date}
              onChange={(e) => setDate(e.target.value)}
              className="w-full px-3 py-2.5 rounded-lg border bg-white dark:bg-[#3A1210] dark:text-[#F1E6D2] outline-none focus:ring-2 focus:ring-[#891D1A]/30"
              style={{ borderColor: "rgba(137,29,26,0.2)" }}
            />
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-1.5">
              <label className="text-sm font-medium" style={{ color: "#5E657B" }}>From</label>
              <input
                type="time"
                value={startTime}
                onChange={(e) => setStartTime(e.target.value)}
                className="w-full px-3 py-2.5 rounded-lg border bg-white dark:bg-[#3A1210] dark:text-[#F1E6D2] outline-none focus:ring-2 focus:ring-[#891D1A]/30"
                style={{ borderColor: "rgba(137,29,26,0.2)" }}
              />
            </div>
            <div className="space-y-1.5">
              <label className="text-sm font-medium" style={{ color: "#5E657B" }}>To</label>
              <input
                type="time"
                value={endTime}
                onChange={(e) => setEndTime(e.target.value)}
                className="w-full px-3 py-2.5 rounded-lg border bg-white dark:bg-[#3A1210] dark:text-[#F1E6D2] outline-none focus:ring-2 focus:ring-[#891D1A]/30"
                style={{ borderColor: "rgba(137,29,26,0.2)" }}
              />
            </div>
          </div>

          <div className="space-y-1.5">
            <label className="text-sm font-medium" style={{ color: "#5E657B" }}>
              Min. Capacity: <span style={{ color: "#891D1A" }}>{minCapacity}+</span>
            </label>
            <input
              type="range"
              min={1}
              max={200}
              value={minCapacity}
              onChange={(e) => setMinCapacity(Number(e.target.value))}
              className="w-full accent-[#891D1A]"
            />
            <div className="flex justify-between text-xs" style={{ color: "#5E657B" }}>
              <span>1</span><span>200</span>
            </div>
          </div>
        </div>
      )}

      {/* Step 2 */}
      {step === 2 && (
        <div className="bg-card rounded-xl p-6 shadow-sm space-y-4">
          <h2 style={{ ...PLAYFAIR, fontSize: 20, fontWeight: 600 }} className="text-foreground">
            Available Rooms
          </h2>
          <p className="text-sm" style={{ color: "#5E657B" }}>
            {date} · {startTime} – {endTime} · {minCapacity}+ seats
          </p>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
            {availableRooms.map((room) => {
              const isSelected = selectedRoom === room.name;
              return (
                <button
                  key={room.name}
                  onClick={() => setSelectedRoom(room.name)}
                  className="relative text-left rounded-xl p-4 border-2 transition-all"
                  style={{
                    borderColor: isSelected ? "#891D1A" : "rgba(137,29,26,0.15)",
                    background: isSelected ? "rgba(137,29,26,0.04)" : "#FFFFFF",
                  }}
                >
                  {isSelected && (
                    <div
                      className="absolute top-2 right-2 w-5 h-5 rounded-full flex items-center justify-center"
                      style={{ background: "#891D1A" }}
                    >
                      <Check className="w-3 h-3 text-white" />
                    </div>
                  )}
                  <p style={{ ...PLAYFAIR, fontSize: 15, fontWeight: 600 }} className="text-foreground">
                    {room.name}
                  </p>
                  <p className="text-xs mt-0.5" style={{ color: "#5E657B" }}>{room.building} · {room.type}</p>
                  <div className="flex items-center gap-1 mt-2 text-xs" style={{ color: "#5E657B" }}>
                    <Users className="w-3 h-3" /> {room.capacity} seats
                  </div>
                  <div className="flex flex-wrap gap-1 mt-2">
                    {room.equipment.slice(0, 3).map((eq, i) => (
                      <span
                        key={i}
                        className="text-xs px-1.5 py-0.5 rounded"
                        style={{ background: "#F1E6D2", color: "#5E657B" }}
                      >
                        {eq}
                      </span>
                    ))}
                  </div>
                </button>
              );
            })}
          </div>
        </div>
      )}

      {/* Step 3 */}
      {step === 3 && (
        <div className="bg-card rounded-xl p-6 shadow-sm space-y-5">
          <h2 style={{ ...PLAYFAIR, fontSize: 20, fontWeight: 600 }} className="text-foreground">
            Booking Details
          </h2>

          <div
            className="rounded-lg p-3 text-sm"
            style={{ background: "rgba(137,29,26,0.05)", border: "1px solid rgba(137,29,26,0.15)" }}
          >
            <span style={{ color: "#5E657B" }}>Room: </span>
            <span className="font-semibold text-foreground">{selectedRoom}</span>
            <span className="mx-2 text-[#5E657B]">·</span>
            <span style={{ color: "#5E657B" }}>{date}</span>
            <span className="mx-2 text-[#5E657B]">·</span>
            <span style={{ color: "#5E657B" }}>{startTime} – {endTime}</span>
          </div>

          <div className="space-y-1.5">
            <label className="text-sm font-medium" style={{ color: "#5E657B" }}>
              Event Title <span style={{ color: "#891D1A" }}>*</span>
            </label>
            <input
              type="text"
              placeholder="e.g. AI Club Workshop"
              value={eventName}
              onChange={(e) => setEventName(e.target.value)}
              className="w-full px-3 py-2.5 rounded-lg border bg-white dark:bg-[#3A1210] dark:text-[#F1E6D2] outline-none focus:ring-2 focus:ring-[#891D1A]/30"
              style={{ borderColor: "rgba(137,29,26,0.2)" }}
            />
          </div>

          <div className="space-y-1.5">
            <label className="text-sm font-medium" style={{ color: "#5E657B" }}>Description</label>
            <textarea
              placeholder="Briefly describe the event or meeting purpose…"
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              rows={3}
              className="w-full px-3 py-2.5 rounded-lg border bg-white dark:bg-[#3A1210] dark:text-[#F1E6D2] outline-none focus:ring-2 focus:ring-[#891D1A]/30 resize-none"
              style={{ borderColor: "rgba(137,29,26,0.2)" }}
            />
          </div>

          <div className="space-y-1.5">
            <label className="text-sm font-medium" style={{ color: "#5E657B" }}>
              Expected Attendees <span style={{ color: "#891D1A" }}>*</span>
            </label>
            <input
              type="number"
              min={1}
              placeholder="Number of attendees"
              value={attendees}
              onChange={(e) => setAttendees(e.target.value)}
              className="w-full px-3 py-2.5 rounded-lg border bg-white dark:bg-[#3A1210] dark:text-[#F1E6D2] outline-none focus:ring-2 focus:ring-[#891D1A]/30"
              style={{ borderColor: "rgba(137,29,26,0.2)" }}
            />
          </div>

          {/* File upload */}
          <div
            className="relative rounded-lg border-2 border-dashed p-6 text-center"
            style={{ borderColor: "rgba(137,29,26,0.3)" }}
          >
            <Upload className="w-8 h-8 mx-auto mb-2" style={{ color: "#891D1A" }} />
            <p className="text-sm font-medium" style={{ color: "#5E657B" }}>
              {attachment ? attachment.name : "Drag & drop files here, or "}
              {!attachment && <span style={{ color: "#891D1A" }} className="cursor-pointer">browse</span>}
            </p>
            <p className="text-xs mt-1" style={{ color: "#A89B8A" }}>
              {attachment ? "This file will be attached to your booking request." : "Supporting documents, event briefs, etc."}
            </p>
            <input
              type="file"
              className="absolute inset-0 opacity-0 cursor-pointer"
              onChange={(e) => setAttachment(e.target.files?.[0] || null)}
            />
            {attachment && (
              <button
                type="button"
                onClick={(e) => { e.stopPropagation(); setAttachment(null); }}
                className="relative z-10 mt-3 text-xs font-medium"
                style={{ color: "#891D1A" }}
              >
                Remove file
              </button>
            )}
          </div>

          {/* Priority note */}
          <div
            className="rounded-lg p-3 text-sm"
            style={{ background: "rgba(94,101,123,0.08)", border: "1px solid rgba(94,101,123,0.15)" }}
          >
            <span className="font-medium" style={{ color: "#5E657B" }}>Priority: </span>
            <span className="font-semibold" style={{ color: "#210706" }}>{priorityLabel}</span>
            <p className="text-xs mt-1" style={{ color: "#5E657B" }}>
              Your booking will be processed at this priority level.
            </p>
          </div>
        </div>
      )}

      {/* Navigation */}
      <div className="flex justify-between">
        <button
          onClick={handleBack}
          className="px-5 py-2.5 rounded-full text-sm font-medium border transition-colors"
          style={{ borderColor: "rgba(137,29,26,0.3)", color: "#5E657B" }}
        >
          {step === 1 ? "Cancel" : "Back"}
        </button>
        <button
          onClick={handleNext}
          disabled={isSubmitting}
          className="px-6 py-2.5 rounded-full text-sm font-medium text-white flex items-center gap-2 transition-colors"
          style={{ background: "#891D1A" }}
          onMouseEnter={(e) => (e.currentTarget.style.background = "#210706")}
          onMouseLeave={(e) => (e.currentTarget.style.background = "#891D1A")}
        >
          {isSubmitting ? (step === 1 ? "Searching..." : "Submitting...") : step === 1 ? (hasPrefilledRoom ? "Continue" : "Search Availability") : step === 3 ? "Submit Booking Request" : "Continue"}
          {step < 3 && <ArrowRight className="w-4 h-4" />}
        </button>
      </div>
    </div>
  );
}
