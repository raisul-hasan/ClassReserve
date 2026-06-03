import { useState } from "react";
import { Mail, Building2, ChevronDown, ChevronUp, Save } from "lucide-react";
import { useAuth } from "../context/AuthContext";

const PLAYFAIR = { fontFamily: "'Playfair Display', serif" } as const;
const DM_SANS = { fontFamily: "'DM Sans', sans-serif" } as const;

function getInitials(name: string) {
  return name.split(" ").map((n) => n[0]).join("").toUpperCase().slice(0, 2);
}

function getRoleLabel(role?: string) {
  if (role === "admin") return "Administrator";
  if (role === "faculty") return "Faculty";
  return "Student";
}

export function Profile() {
  const { user } = useAuth();
  const [pwOpen, setPwOpen] = useState(false);
  const [firstName, setFirstName] = useState(user?.name?.split(" ")[0] || "");
  const [lastName, setLastName] = useState(user?.name?.split(" ").slice(1).join(" ") || "");
  const [email, setEmail] = useState(user?.email || "");
  const [department, setDepartment] = useState("Computer Science");
  const [studentId, setStudentId] = useState("CS-2024-0042");

  const inputCls =
    "w-full px-3 py-2.5 rounded-lg border bg-white dark:bg-[#3A1210] dark:text-[#F1E6D2] outline-none focus:ring-2 focus:ring-[#891D1A]/30 text-sm";

  return (
    <div className="space-y-0 max-w-2xl" style={DM_SANS}>
      {/* Header Banner */}
      <div
        className="rounded-t-2xl p-8 flex flex-col items-center gap-3"
        style={{
          background: "linear-gradient(135deg, #210706 0%, #3d0e0b 50%, #210706 100%)",
        }}
      >
        <div
          className="w-20 h-20 rounded-full flex items-center justify-center text-2xl font-bold text-white"
          style={{ background: "#891D1A", boxShadow: "0 0 0 4px rgba(137,29,26,0.3)" }}
        >
          {getInitials(user?.name || "U")}
        </div>
        <div className="text-center">
          <h2 className="text-xl text-white" style={{ ...PLAYFAIR, fontWeight: 600 }}>
            {user?.name || "User"}
          </h2>
          <span
            className="inline-block text-xs px-3 py-1 rounded-full mt-1 font-medium"
            style={{ background: "#5E657B", color: "#F1E6D2" }}
          >
            {getRoleLabel(user?.role)}
          </span>
        </div>
      </div>

      {/* Main card */}
      <div className="bg-card rounded-b-2xl shadow-sm overflow-hidden">
        <div className="px-6 pt-6 pb-5 border-b border-border">
          <h3 style={{ ...PLAYFAIR, fontSize: 16, fontWeight: 600 }} className="text-foreground">
            Personal Information
          </h3>
        </div>

        <div className="px-6 py-5 space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-1.5">
              <label className="text-sm font-medium" style={{ color: "#5E657B" }}>First Name</label>
              <input
                type="text"
                value={firstName}
                onChange={(e) => setFirstName(e.target.value)}
                className={inputCls}
                style={{ borderColor: "rgba(137,29,26,0.2)" }}
              />
            </div>
            <div className="space-y-1.5">
              <label className="text-sm font-medium" style={{ color: "#5E657B" }}>Last Name</label>
              <input
                type="text"
                value={lastName}
                onChange={(e) => setLastName(e.target.value)}
                className={inputCls}
                style={{ borderColor: "rgba(137,29,26,0.2)" }}
              />
            </div>
          </div>

          <div className="space-y-1.5">
            <label className="text-sm font-medium" style={{ color: "#5E657B" }}>Email Address</label>
            <div className="relative">
              <Mail className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4" style={{ color: "#5E657B" }} />
              <input
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className={inputCls + " pl-9"}
                style={{ borderColor: "rgba(137,29,26,0.2)" }}
              />
            </div>
          </div>

          <div className="space-y-1.5">
            <label className="text-sm font-medium" style={{ color: "#5E657B" }}>Department</label>
            <div className="relative">
              <Building2 className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4" style={{ color: "#5E657B" }} />
              <input
                type="text"
                value={department}
                onChange={(e) => setDepartment(e.target.value)}
                className={inputCls + " pl-9"}
                style={{ borderColor: "rgba(137,29,26,0.2)" }}
              />
            </div>
          </div>

          <div className="space-y-1.5">
            <label className="text-sm font-medium" style={{ color: "#5E657B" }}>
              {user?.role === "faculty" ? "Faculty ID" : "Student ID"}
            </label>
            <input
              type="text"
              value={studentId}
              onChange={(e) => setStudentId(e.target.value)}
              className={inputCls}
              style={{ borderColor: "rgba(137,29,26,0.2)" }}
            />
          </div>

          <button
            className="w-full py-2.5 rounded-lg text-sm font-medium text-white flex items-center justify-center gap-2 transition-colors"
            style={{ background: "#891D1A" }}
            onMouseEnter={(e) => (e.currentTarget.style.background = "#210706")}
            onMouseLeave={(e) => (e.currentTarget.style.background = "#891D1A")}
            onClick={() => alert("Profile saved.")}
          >
            <Save className="w-4 h-4" />
            Save Changes
          </button>
        </div>

        {/* Password section (collapsible) */}
        <div className="border-t border-border">
          <button
            onClick={() => setPwOpen((v) => !v)}
            className="w-full flex items-center justify-between px-6 py-4 hover:bg-[#891D1A]/5 transition-colors"
          >
            <span className="text-sm font-semibold" style={{ color: "#891D1A" }}>
              Change Password
            </span>
            {pwOpen ? (
              <ChevronUp className="w-4 h-4" style={{ color: "#891D1A" }} />
            ) : (
              <ChevronDown className="w-4 h-4" style={{ color: "#891D1A" }} />
            )}
          </button>

          {pwOpen && (
            <div className="px-6 pb-5 space-y-4">
              {["Current Password", "New Password", "Confirm New Password"].map((label) => (
                <div key={label} className="space-y-1.5">
                  <label className="text-sm font-medium" style={{ color: "#5E657B" }}>{label}</label>
                  <input
                    type="password"
                    placeholder="••••••••"
                    className={inputCls}
                    style={{ borderColor: "rgba(137,29,26,0.2)" }}
                  />
                </div>
              ))}
              <button
                className="w-full py-2.5 rounded-lg text-sm font-medium border transition-colors"
                style={{ borderColor: "#891D1A", color: "#891D1A" }}
                onMouseEnter={(e) => {
                  e.currentTarget.style.background = "rgba(137,29,26,0.06)";
                }}
                onMouseLeave={(e) => {
                  e.currentTarget.style.background = "transparent";
                }}
                onClick={() => alert("Password updated.")}
              >
                Update Password
              </button>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
