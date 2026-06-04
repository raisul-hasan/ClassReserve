import { useEffect, useState } from "react";
import { Mail, Building2, ChevronDown, ChevronUp, Save } from "lucide-react";
import { useAuth } from "../context/AuthContext";
import { changePassword, getProfile, updateProfile } from "../services/classReserveService";

const PLAYFAIR = { fontFamily: "'Playfair Display', serif" } as const;
const DM_SANS = { fontFamily: "'DM Sans', sans-serif" } as const;

function getInitials(name: string) {
  return name.split(" ").map((n) => n[0]).join("").toUpperCase().slice(0, 2);
}

function getRoleLabel(role?: string) {
  if (role === "admin") return "Administrator";
  if (role === "faculty") return "Faculty";
  if (role === "club") return "Club";
  return "Student";
}

export function Profile() {
  const { user, updateUser } = useAuth();
  const [pwOpen, setPwOpen] = useState(false);
  const [firstName, setFirstName] = useState(user?.name?.split(" ")[0] || "");
  const [lastName, setLastName] = useState(user?.name?.split(" ").slice(1).join(" ") || "");
  const [email, setEmail] = useState(user?.email || "");
  const [department, setDepartment] = useState("");
  const [studentId, setStudentId] = useState("");
  const [currentPassword, setCurrentPassword] = useState("");
  const [newPassword, setNewPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const [message, setMessage] = useState("");
  const [isSaving, setIsSaving] = useState(false);

  useEffect(() => {
    if (user) {
      setFirstName(user.name?.split(" ")[0] || "");
      setLastName(user.name?.split(" ").slice(1).join(" ") || "");
      setEmail(user.email || "");
    }

    getProfile()
      .then((apiUser) => {
        updateUser(apiUser);
        setFirstName(apiUser.name?.split(" ")[0] || "");
        setLastName(apiUser.name?.split(" ").slice(1).join(" ") || "");
        setEmail(apiUser.email || "");
      })
      .catch(() => undefined);
  }, [user?.email]);

  const inputCls =
    "w-full px-3 py-2.5 rounded-lg border bg-white dark:bg-[#3A1210] dark:text-[#F1E6D2] outline-none focus:ring-2 focus:ring-[#891D1A]/30 text-sm";

  const handleSaveProfile = async () => {
    const fullName = `${firstName} ${lastName}`.trim();
    if (!fullName) {
      setMessage("Name is required.");
      return;
    }

    setIsSaving(true);
    setMessage("");
    try {
      const updated = await updateProfile(fullName);
      updateUser(updated);
      setMessage("Profile saved.");
    } catch (error) {
      setMessage(error instanceof Error ? error.message : "Could not save profile.");
    } finally {
      setIsSaving(false);
    }
  };

  const handleChangePassword = async () => {
    if (!currentPassword || !newPassword || !confirmPassword) {
      setMessage("Please fill all password fields.");
      return;
    }
    if (newPassword !== confirmPassword) {
      setMessage("New password and confirmation do not match.");
      return;
    }

    setIsSaving(true);
    setMessage("");
    try {
      await changePassword(currentPassword, newPassword);
      setCurrentPassword("");
      setNewPassword("");
      setConfirmPassword("");
      setPwOpen(false);
      setMessage("Password updated.");
    } catch (error) {
      setMessage(error instanceof Error ? error.message : "Could not update password.");
    } finally {
      setIsSaving(false);
    }
  };

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
        {message && (
          <div
            className="mx-6 mt-5 rounded-lg px-4 py-3 text-sm font-medium"
            style={{ background: "rgba(137,29,26,0.08)", color: "#891D1A" }}
          >
            {message}
          </div>
        )}
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
                readOnly
                className={inputCls + " pl-9"}
                style={{ borderColor: "rgba(137,29,26,0.2)", opacity: 0.75 }}
              />
            </div>
            <p className="text-xs" style={{ color: "#5E657B" }}>
              Email and role are managed by the system.
            </p>
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
            onClick={handleSaveProfile}
            disabled={isSaving}
          >
            <Save className="w-4 h-4" />
            {isSaving ? "Saving..." : "Save Changes"}
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
              <div className="space-y-1.5">
                <label className="text-sm font-medium" style={{ color: "#5E657B" }}>Current Password</label>
                <input type="password" value={currentPassword} onChange={(e) => setCurrentPassword(e.target.value)} placeholder="Current password" className={inputCls} style={{ borderColor: "rgba(137,29,26,0.2)" }} />
              </div>
              <div className="space-y-1.5">
                <label className="text-sm font-medium" style={{ color: "#5E657B" }}>New Password</label>
                <input type="password" value={newPassword} onChange={(e) => setNewPassword(e.target.value)} placeholder="New password" className={inputCls} style={{ borderColor: "rgba(137,29,26,0.2)" }} />
              </div>
              <div className="space-y-1.5">
                <label className="text-sm font-medium" style={{ color: "#5E657B" }}>Confirm New Password</label>
                <input type="password" value={confirmPassword} onChange={(e) => setConfirmPassword(e.target.value)} placeholder="Confirm new password" className={inputCls} style={{ borderColor: "rgba(137,29,26,0.2)" }} />
              </div>
              {false && ["Current Password", "New Password", "Confirm New Password"].map((label) => (
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
                onClick={handleChangePassword}
                disabled={isSaving}
              >
                {isSaving ? "Updating..." : "Update Password"}
              </button>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
