import { useEffect, useState } from "react";
import { Mail, Building2, ChevronDown, ChevronUp, Save } from "lucide-react";
import { useAuth } from "../context/AuthContext";
import type { User } from "../context/AuthContext";
import { changePassword, updateProfile } from "../services/classReserveService";

const PLAYFAIR = { fontFamily: "'Playfair Display', serif" } as const;
const DM_SANS = { fontFamily: "'DM Sans', sans-serif" } as const;

type ProfileExtras = {
  department: string;
  profileId: string;
  phone: string;
  recentActivity: string[];
};

function getInitials(name: string) {
  return name.split(" ").map((n) => n[0]).join("").toUpperCase().slice(0, 2);
}

function getRoleLabel(role?: string) {
  if (role === "admin") return "Administrator";
  if (role === "faculty") return "Faculty";
  if (role === "club") return "Club";
  return "Student";
}

function splitName(name?: string) {
  const parts = (name || "").trim().split(/\s+/).filter(Boolean);
  return {
    firstName: parts[0] || "",
    lastName: parts.slice(1).join(" "),
  };
}

function getProfileStorageKey(user: User | null) {
  return `classreserve.profile.${user?.id || user?.email || "guest"}`;
}

function loadProfileExtras(user: User | null): ProfileExtras {
  const defaults: ProfileExtras = { department: "", profileId: "", phone: "", recentActivity: [] };
  if (!user) return defaults;

  try {
    const saved = localStorage.getItem(getProfileStorageKey(user));
    return saved ? { ...defaults, ...JSON.parse(saved) } : defaults;
  } catch {
    return defaults;
  }
}

function saveProfileExtras(user: User, extras: ProfileExtras) {
  localStorage.setItem(getProfileStorageKey(user), JSON.stringify(extras));
}

function getDepartmentLabel(role?: string) {
  if (role === "club") return "Club Category / Department";
  if (role === "admin") return "Office / Department";
  return "Department";
}

function getIdLabel(role?: string) {
  if (role === "faculty") return "Faculty ID";
  if (role === "club") return "Club ID";
  if (role === "admin") return "Staff ID";
  return "Student ID";
}

function isValidPhone(phone: string) {
  return !phone || /^[+()\-\s\d]{7,20}$/.test(phone);
}

export function Profile() {
  const { user, updateUser } = useAuth();
  const initialName = splitName(user?.name);
  const [pwOpen, setPwOpen] = useState(false);
  const [isEditing, setIsEditing] = useState(false);
  const [firstName, setFirstName] = useState(initialName.firstName);
  const [lastName, setLastName] = useState(initialName.lastName);
  const [email, setEmail] = useState(user?.email || "");
  const [department, setDepartment] = useState("");
  const [profileId, setProfileId] = useState("");
  const [phone, setPhone] = useState("");
  const [recentActivity, setRecentActivity] = useState<string[]>([]);
  const [currentPassword, setCurrentPassword] = useState("");
  const [newPassword, setNewPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const [message, setMessage] = useState("");
  const [isSaving, setIsSaving] = useState(false);

  const applyProfileForm = (profileUser: User | null) => {
    const name = splitName(profileUser?.name);
    const extras = loadProfileExtras(profileUser);
    setFirstName(name.firstName);
    setLastName(name.lastName);
    setEmail(profileUser?.email || "");
    setDepartment(extras.department);
    setProfileId(extras.profileId);
    setPhone(extras.phone);
    setRecentActivity(extras.recentActivity || []);
  };

  const resetProfileForm = () => {
    applyProfileForm(user);
    setMessage("");
  };

  useEffect(() => {
    if (user) {
      applyProfileForm(user);
    }
  }, [user?.id, user?.email]);

  const inputCls =
    "w-full px-3 py-2.5 rounded-lg border bg-white dark:bg-[#3A1210] dark:text-[#F1E6D2] outline-none focus:ring-2 focus:ring-[#891D1A]/30 text-sm";

  const handleSaveProfile = async () => {
    const cleanFirstName = firstName.trim();
    const cleanLastName = lastName.trim();
    const cleanPhone = phone.trim();

    if (!cleanFirstName) {
      setMessage("First name is required.");
      return;
    }

    if (!cleanLastName) {
      setMessage("Last name is required.");
      return;
    }

    if (!isValidPhone(cleanPhone)) {
      setMessage("Phone number can only include numbers, spaces, +, -, and parentheses.");
      return;
    }

    const fullName = `${cleanFirstName} ${cleanLastName}`.trim();
    const activity = [`Profile updated ${new Date().toLocaleDateString()}`, ...recentActivity].slice(0, 5);
    const extras: ProfileExtras = {
      department: department.trim(),
      profileId: profileId.trim(),
      phone: cleanPhone,
      recentActivity: activity,
    };

    setIsSaving(true);
    setMessage("");
    try {
      await updateProfile(fullName);
      if (!user) {
        setMessage("Could not save profile.");
        return;
      }

      const nextUser = { ...user, name: fullName };
      updateUser(nextUser);
      saveProfileExtras(nextUser, extras);
      setRecentActivity(activity);
      setMessage("Profile saved.");
      setIsEditing(false);
    } catch (error) {
      if (!user) {
        setMessage(error instanceof Error ? error.message : "Could not save profile.");
        return;
      }

      const nextUser = { ...user, name: fullName };
      updateUser(nextUser);
      saveProfileExtras(nextUser, extras);
      setRecentActivity(activity);
      setMessage("Profile saved locally.");
      setIsEditing(false);
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
    if (newPassword.length < 6) {
      setMessage("New password must be at least 6 characters.");
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
          <div className="flex items-center justify-between gap-3">
            <h3 style={{ ...PLAYFAIR, fontSize: 16, fontWeight: 600 }} className="text-foreground">
              Personal Information
            </h3>
            {!isEditing && (
              <button
                onClick={() => setIsEditing(true)}
                className="px-3 py-1.5 rounded-lg text-xs font-medium border"
                style={{ borderColor: "rgba(137,29,26,0.3)", color: "#891D1A" }}
              >
                Edit Profile
              </button>
            )}
          </div>
        </div>

        <div className="px-6 py-5 space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-1.5">
              <label className="text-sm font-medium" style={{ color: "#5E657B" }}>
                First Name
              </label>
              <input
                type="text"
                value={firstName}
                onChange={(e) => setFirstName(e.target.value)}
                readOnly={!isEditing}
                required
                className={inputCls}
                style={{ borderColor: "rgba(137,29,26,0.2)", opacity: isEditing ? 1 : 0.75 }}
              />
            </div>
            <div className="space-y-1.5">
              <label className="text-sm font-medium" style={{ color: "#5E657B" }}>
                Last Name
              </label>
              <input
                type="text"
                value={lastName}
                onChange={(e) => setLastName(e.target.value)}
                readOnly={!isEditing}
                required
                className={inputCls}
                style={{ borderColor: "rgba(137,29,26,0.2)", opacity: isEditing ? 1 : 0.75 }}
              />
            </div>
          </div>

          <div className="space-y-1.5">
            <label className="text-sm font-medium" style={{ color: "#5E657B" }}>
              Email Address
            </label>
            <div className="relative">
              <Mail className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4" style={{ color: "#5E657B" }} />
              <input
                type="email"
                value={email}
                readOnly
                className={`${inputCls} pl-9`}
                style={{ borderColor: "rgba(137,29,26,0.2)", opacity: 0.75 }}
              />
            </div>
            <p className="text-xs" style={{ color: "#5E657B" }}>
              Email and role are managed by the system.
            </p>
          </div>

          <div className="space-y-1.5">
            <label className="text-sm font-medium" style={{ color: "#5E657B" }}>
              {getDepartmentLabel(user?.role)}
            </label>
            <div className="relative">
              <Building2 className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4" style={{ color: "#5E657B" }} />
              <input
                type="text"
                value={department}
                onChange={(e) => setDepartment(e.target.value)}
                readOnly={!isEditing}
                className={`${inputCls} pl-9`}
                style={{ borderColor: "rgba(137,29,26,0.2)", opacity: isEditing ? 1 : 0.75 }}
              />
            </div>
          </div>

          <div className="space-y-1.5">
            <label className="text-sm font-medium" style={{ color: "#5E657B" }}>
              {getIdLabel(user?.role)}
            </label>
            <input
              type="text"
              value={profileId}
              onChange={(e) => setProfileId(e.target.value)}
              readOnly={!isEditing}
              className={inputCls}
              style={{ borderColor: "rgba(137,29,26,0.2)", opacity: isEditing ? 1 : 0.75 }}
            />
          </div>

          <div className="space-y-1.5">
            <label className="text-sm font-medium" style={{ color: "#5E657B" }}>
              Phone Number
            </label>
            <input
              type="tel"
              value={phone}
              onChange={(e) => setPhone(e.target.value)}
              readOnly={!isEditing}
              placeholder="+880 1XXX XXXXXX"
              className={inputCls}
              style={{ borderColor: "rgba(137,29,26,0.2)", opacity: isEditing ? 1 : 0.75 }}
            />
          </div>

          {isEditing && (
            <div className="flex gap-3">
              <button
                className="flex-1 py-2.5 rounded-lg text-sm font-medium border"
                style={{ borderColor: "rgba(137,29,26,0.3)", color: "#5E657B" }}
                onClick={() => {
                  resetProfileForm();
                  setIsEditing(false);
                }}
              >
                Cancel
              </button>
              <button
                className="flex-1 py-2.5 rounded-lg text-sm font-medium text-white flex items-center justify-center gap-2 transition-colors"
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
          )}
        </div>

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
                <label className="text-sm font-medium" style={{ color: "#5E657B" }}>
                  Current Password
                </label>
                <input
                  type="password"
                  value={currentPassword}
                  onChange={(e) => setCurrentPassword(e.target.value)}
                  placeholder="Current password"
                  className={inputCls}
                  style={{ borderColor: "rgba(137,29,26,0.2)" }}
                />
              </div>
              <div className="space-y-1.5">
                <label className="text-sm font-medium" style={{ color: "#5E657B" }}>
                  New Password
                </label>
                <input
                  type="password"
                  value={newPassword}
                  onChange={(e) => setNewPassword(e.target.value)}
                  placeholder="New password"
                  className={inputCls}
                  style={{ borderColor: "rgba(137,29,26,0.2)" }}
                />
              </div>
              <div className="space-y-1.5">
                <label className="text-sm font-medium" style={{ color: "#5E657B" }}>
                  Confirm New Password
                </label>
                <input
                  type="password"
                  value={confirmPassword}
                  onChange={(e) => setConfirmPassword(e.target.value)}
                  placeholder="Confirm new password"
                  className={inputCls}
                  style={{ borderColor: "rgba(137,29,26,0.2)" }}
                />
              </div>
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

        <div className="border-t border-border px-6 py-5">
          <h3 style={{ ...PLAYFAIR, fontSize: 16, fontWeight: 600 }} className="text-foreground mb-3">
            Recent Activity
          </h3>
          {recentActivity.length > 0 ? (
            <div className="space-y-2">
              {recentActivity.map((activity) => (
                <p key={activity} className="text-sm" style={{ color: "#5E657B" }}>
                  {activity}
                </p>
              ))}
            </div>
          ) : (
            <p className="text-sm" style={{ color: "#5E657B" }}>
              No recent activity yet.
            </p>
          )}
        </div>
      </div>
    </div>
  );
}
