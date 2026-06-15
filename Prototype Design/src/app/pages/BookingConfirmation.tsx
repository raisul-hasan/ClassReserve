import { useLocation, useNavigate } from "react-router";
import { CheckCircle, CalendarDays, DoorOpen, Clock } from "lucide-react";
import { useAuth } from "../context/AuthContext";

const PLAYFAIR = { fontFamily: "'Playfair Display', serif" } as const;
const DM_SANS = { fontFamily: "'DM Sans', sans-serif" } as const;

export function BookingConfirmation() {
  const location = useLocation();
  const navigate = useNavigate();
  const { user } = useAuth();

  const state = (location.state as { room?: string; date?: string; time?: string; event?: string } | null) || {};
  const base = user?.role === "faculty" ? "/faculty" : user?.role === "admin" ? "/admin" : "/student";

  const isPending = user?.role === "student";

  return (
    <div className="flex items-center justify-center min-h-[60vh]" style={DM_SANS}>
      <div className="max-w-md w-full space-y-6">
        <div
          className="bg-card rounded-2xl shadow-sm p-8 text-center"
        >
          {/* Animated checkmark */}
          <div
            className="w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-5"
            style={{ background: isPending ? "rgba(184,134,11,0.1)" : "rgba(59,110,74,0.1)" }}
          >
            <CheckCircle
              className="w-10 h-10"
              style={{ color: isPending ? "#B8860B" : "#3B6E4A" }}
            />
          </div>

          <h1
            className="text-foreground mb-2"
            style={{ ...PLAYFAIR, fontSize: 24, fontWeight: 600 }}
          >
            {isPending ? "Request Submitted!" : "Booking Confirmed!"}
          </h1>
          <p className="text-sm mb-6" style={{ color: "#5E657B" }}>
            {isPending
              ? "Your booking request has been submitted and is awaiting approval."
              : "Your room has been successfully booked."}
          </p>

          {/* Summary */}
          <div
            className="rounded-xl p-4 text-left space-y-3 mb-6"
            style={{ background: "rgba(241,230,210,0.5)", border: "1px solid rgba(137,29,26,0.15)" }}
          >
            {state.event && (
              <div className="flex items-center gap-3 text-sm">
                <div
                  className="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                  style={{ background: "rgba(137,29,26,0.1)" }}
                >
                  <DoorOpen className="w-4 h-4" style={{ color: "#891D1A" }} />
                </div>
                <div>
                  <p className="text-xs" style={{ color: "#5E657B" }}>Event</p>
                  <p className="font-medium text-foreground">{state.event}</p>
                </div>
              </div>
            )}
            {state.room && (
              <div className="flex items-center gap-3 text-sm">
                <div
                  className="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                  style={{ background: "rgba(137,29,26,0.1)" }}
                >
                  <DoorOpen className="w-4 h-4" style={{ color: "#891D1A" }} />
                </div>
                <div>
                  <p className="text-xs" style={{ color: "#5E657B" }}>Room</p>
                  <p className="font-medium text-foreground">{state.room}</p>
                </div>
              </div>
            )}
            {state.date && (
              <div className="flex items-center gap-3 text-sm">
                <div
                  className="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                  style={{ background: "rgba(137,29,26,0.1)" }}
                >
                  <CalendarDays className="w-4 h-4" style={{ color: "#891D1A" }} />
                </div>
                <div>
                  <p className="text-xs" style={{ color: "#5E657B" }}>Date</p>
                  <p className="font-medium text-foreground">{state.date}</p>
                </div>
              </div>
            )}
            {state.time && (
              <div className="flex items-center gap-3 text-sm">
                <div
                  className="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                  style={{ background: "rgba(137,29,26,0.1)" }}
                >
                  <Clock className="w-4 h-4" style={{ color: "#891D1A" }} />
                </div>
                <div>
                  <p className="text-xs" style={{ color: "#5E657B" }}>Time</p>
                  <p className="font-medium text-foreground">{state.time}</p>
                </div>
              </div>
            )}
            <div className="pt-1">
              <span
                className="text-xs px-2.5 py-1 rounded-full text-white font-medium"
                style={{ background: isPending ? "#B8860B" : "#3B6E4A" }}
              >
                {isPending ? "Pending Approval" : "Confirmed"}
              </span>
            </div>
          </div>

          <div className="flex flex-col gap-2">
            <button
              onClick={() => navigate(`${base}/bookings`)}
              className="w-full py-2.5 rounded-full text-sm font-medium text-[#F1E6D2] transition-colors"
              style={{ background: "#891D1A" }}
              onMouseEnter={(e) => (e.currentTarget.style.background = "#210706")}
              onMouseLeave={(e) => (e.currentTarget.style.background = "#891D1A")}
            >
              View My Bookings
            </button>
            <button
              onClick={() => navigate(`${base}/new-booking`)}
              className="w-full py-2.5 rounded-full text-sm font-medium border transition-colors"
              style={{ borderColor: "rgba(137,29,26,0.3)", color: "#5E657B" }}
            >
              Book Another Room
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
