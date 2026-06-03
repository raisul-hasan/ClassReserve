import { FormEvent, useEffect, useState } from "react";
import { useNavigate } from "react-router";
import { Button } from "../components/ui/button";
import { Input } from "../components/ui/input";
import { Label } from "../components/ui/label";
import { DoorOpen, Mail, Lock, User, Moon, Sun } from "lucide-react";
import { useAuth } from "../context/AuthContext";
import { useTheme } from "../context/ThemeContext";

type UserRole = "student" | "club" | "faculty" | "admin";

const PLAYFAIR = { fontFamily: "'Playfair Display', serif" } as const;
const DM_SANS = { fontFamily: "'DM Sans', sans-serif" } as const;

export function Auth() {
  const navigate = useNavigate();
  const { login, signup, user } = useAuth();
  const { theme, toggleTheme } = useTheme();

  const [selectedRole, setSelectedRole] = useState<UserRole>("student");
  const [activeTab, setActiveTab] = useState<"login" | "signup">("login");

  const [loginEmail, setLoginEmail] = useState("");
  const [loginPassword, setLoginPassword] = useState("");

  const [signupName, setSignupName] = useState("");
  const [signupEmail, setSignupEmail] = useState("");
  const [signupPassword, setSignupPassword] = useState("");
  const [signupConfirmPassword, setSignupConfirmPassword] = useState("");

  useEffect(() => {
    if (!user) return;
    if (user.role === "student") navigate("/student");
    else if (user.role === "club") navigate("/club");
    else if (user.role === "faculty") navigate("/faculty");
    else navigate("/admin");
  }, [user, navigate]);

  const handleRoleChange = (role: UserRole) => {
    setSelectedRole(role);
    if (role === "admin" || role === "faculty") setActiveTab("login");
  };

  const handleLogin = (e: FormEvent) => {
    e.preventDefault();
    if (!loginEmail.trim() || !loginPassword.trim()) {
      alert("Please enter email and password.");
      return;
    }
    login(loginEmail, loginPassword, selectedRole);
    if (selectedRole === "student") navigate("/student");
    else if (selectedRole === "club") navigate("/club");
    else if (selectedRole === "faculty") navigate("/faculty");
    else navigate("/admin");
  };

  const handleSignup = (e: FormEvent) => {
    e.preventDefault();
    if (selectedRole === "admin" || selectedRole === "faculty") { alert("Faculty and admin accounts are login-only."); return; }
    if (!signupName.trim() || !signupEmail.trim() || !signupPassword.trim()) {
      alert("Please complete all fields.");
      return;
    }
    if (signupPassword !== signupConfirmPassword) { alert("Passwords do not match."); return; }
    signup(signupName, signupEmail, signupPassword, selectedRole);
    if (selectedRole === "student") navigate("/student");
    else navigate("/club");
  };

  const inputCls =
    "pl-9 bg-white border border-[#891D1A]/20 focus-visible:ring-[#891D1A] rounded-md text-[#210706]";
  const darkInputCls =
    "pl-9 dark:bg-[#3A1210] dark:border-[#891D1A]/30 dark:text-[#F1E6D2] focus-visible:ring-[#891D1A]";

  return (
    <div className="min-h-screen flex" style={DM_SANS}>
      {/* Left panel */}
      <div
        className="hidden lg:flex lg:w-5/12 xl:w-1/2 flex-col items-center justify-center p-12 relative overflow-hidden"
        style={{ background: "#210706" }}
      >
        {/* Subtle pattern overlay */}
        <div
          className="absolute inset-0 opacity-5"
          style={{
            backgroundImage:
              "repeating-linear-gradient(45deg, #891D1A 0, #891D1A 1px, transparent 0, transparent 50%)",
            backgroundSize: "20px 20px",
          }}
        />

        <div className="relative z-10 text-center max-w-sm">
          <div
            className="w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-8"
            style={{ background: "#891D1A" }}
          >
            <DoorOpen className="w-10 h-10 text-white" />
          </div>
          <h1
            className="text-4xl text-white mb-4"
            style={{ ...PLAYFAIR, fontWeight: 700, lineHeight: 1.2 }}
          >
            ClassReserve
          </h1>
          <p className="text-lg mb-2" style={{ color: "#F1E6D2", opacity: 0.8 }}>
            Reserve your space.
          </p>
          <p className="text-lg" style={{ color: "#F1E6D2", opacity: 0.8 }}>
            Own your time.
          </p>

          <div className="mt-12 flex flex-col gap-4 text-left">
            {[
              { label: "Instant availability checks", icon: "✓" },
              { label: "Priority-based booking system", icon: "✓" },
              { label: "Real-time conflict detection", icon: "✓" },
            ].map((item) => (
              <div key={item.label} className="flex items-center gap-3">
                <div
                  className="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                  style={{ background: "#891D1A" }}
                >
                  {item.icon}
                </div>
                <span style={{ color: "#F1E6D2", opacity: 0.75 }}>{item.label}</span>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Right panel */}
      <div className="flex-1 flex flex-col items-center justify-center p-6 bg-[#F1E6D2] dark:bg-[#210706] transition-colors relative">
        {/* Theme toggle */}
        <button
          onClick={toggleTheme}
          className="absolute top-4 right-4 w-9 h-9 rounded-lg flex items-center justify-center hover:bg-[#891D1A]/10 transition-colors"
          style={{ color: "#5E657B" }}
        >
          {theme === "light" ? <Moon className="w-5 h-5" /> : <Sun className="w-5 h-5" />}
        </button>

        {/* Mobile logo */}
        <div className="lg:hidden mb-8 text-center">
          <div
            className="w-14 h-14 rounded-xl flex items-center justify-center mx-auto mb-3"
            style={{ background: "#891D1A" }}
          >
            <DoorOpen className="w-7 h-7 text-white" />
          </div>
          <h1 className="text-2xl text-[#210706] dark:text-[#F1E6D2]" style={PLAYFAIR}>
            ClassReserve
          </h1>
        </div>

        {/* Card */}
        <div
          className="w-full max-w-md rounded-xl shadow-lg p-8"
          style={{ background: "#FFFFFF", boxShadow: "0 4px 24px rgba(33,7,6,0.10)" }}
        >
          <h2
            className="text-2xl text-[#210706] mb-1"
            style={{ ...PLAYFAIR, fontWeight: 600 }}
          >
            Welcome back
          </h2>
          <p className="text-sm text-[#5E657B] mb-6">Sign in to manage your reservations</p>

          {/* Role selector */}
          <div className="mb-6">
            <Label className="text-sm text-[#5E657B] mb-2 block">Sign in as</Label>
            <div className="flex gap-2">
              {(["student", "club", "faculty", "admin"] as UserRole[]).map((role) => (
                <button
                  key={role}
                  onClick={() => handleRoleChange(role)}
                  className="flex-1 py-2 px-3 rounded-full text-sm font-medium border transition-all capitalize"
                  style={
                    selectedRole === role
                      ? { background: "#891D1A", color: "#F1E6D2", border: "1px solid #891D1A" }
                      : { background: "transparent", color: "#5E657B", border: "1px solid #5E657B" }
                  }
                >
                  {role}
                </button>
              ))}
            </div>
          </div>

          {(selectedRole === "admin" || selectedRole === "faculty") && (
            <div
              className="mb-4 p-3 rounded-lg text-sm"
              style={{ background: "rgba(137,29,26,0.06)", color: "#891D1A" }}
            >
              Faculty and admin accounts are managed by the system. Self-registration is disabled.
            </div>
          )}

          {/* Tabs */}
          <div className="flex border-b border-[#891D1A]/15 mb-6">
            <button
              onClick={() => setActiveTab("login")}
              className="flex-1 pb-2 text-sm font-medium transition-colors border-b-2"
              style={
                activeTab === "login"
                  ? { color: "#891D1A", borderColor: "#891D1A" }
                  : { color: "#5E657B", borderColor: "transparent" }
              }
            >
              Sign In
            </button>
            <button
              onClick={() => !(selectedRole === "admin" || selectedRole === "faculty") && setActiveTab("signup")}
              disabled={selectedRole === "admin" || selectedRole === "faculty"}
              className="flex-1 pb-2 text-sm font-medium transition-colors border-b-2"
              style={
                activeTab === "signup"
                  ? { color: "#891D1A", borderColor: "#891D1A" }
                  : selectedRole === "admin" || selectedRole === "faculty"
                    ? { color: "#5E657B", opacity: 0.4, borderColor: "transparent" }
                    : { color: "#5E657B", borderColor: "transparent" }
              }
            >
              Create Account
            </button>
          </div>

          {activeTab === "login" && (
            <form onSubmit={handleLogin} className="space-y-4">
              <div className="space-y-1.5">
                <Label htmlFor="login-email" className="text-sm text-[#5E657B]">Email</Label>
                <div className="relative">
                  <Mail className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#5E657B]" />
                  <Input
                    id="login-email"
                    type="email"
                    placeholder="you@university.edu"
                    className={`${inputCls} ${darkInputCls}`}
                    value={loginEmail}
                    onChange={(e) => setLoginEmail(e.target.value)}
                    required
                  />
                </div>
              </div>

              <div className="space-y-1.5">
                <Label htmlFor="login-password" className="text-sm text-[#5E657B]">Password</Label>
                <div className="relative">
                  <Lock className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#5E657B]" />
                  <Input
                    id="login-password"
                    type="password"
                    placeholder="••••••••"
                    className={`${inputCls} ${darkInputCls}`}
                    value={loginPassword}
                    onChange={(e) => setLoginPassword(e.target.value)}
                    required
                  />
                </div>
              </div>

              <div className="flex justify-end">
                <button
                  type="button"
                  className="text-sm hover:underline"
                  style={{ color: "#891D1A" }}
                  onClick={() => alert("Password reset flow can be connected later.")}
                >
                  Forgot password?
                </button>
              </div>

              <button
                type="submit"
                className="w-full py-2.5 rounded-full text-[#F1E6D2] font-medium transition-colors"
                style={{ ...PLAYFAIR, background: "#891D1A", fontSize: "15px" }}
                onMouseEnter={(e) => (e.currentTarget.style.background = "#210706")}
                onMouseLeave={(e) => (e.currentTarget.style.background = "#891D1A")}
              >
                Sign In
              </button>

              <p className="text-center text-sm text-[#5E657B]">
                Don't have an account?{" "}
                <button
                  type="button"
                  className="font-medium hover:underline"
                  style={{ color: "#891D1A" }}
                  onClick={() => selectedRole !== "admin" && selectedRole !== "faculty" && setActiveTab("signup")}
                >
                  Create one
                </button>
              </p>
            </form>
          )}

          {activeTab === "signup" && (
            <form onSubmit={handleSignup} className="space-y-4">
              <div className="space-y-1.5">
                <Label htmlFor="signup-name" className="text-sm text-[#5E657B]">Full Name</Label>
                <div className="relative">
                  <User className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#5E657B]" />
                  <Input
                    id="signup-name"
                    type="text"
                    placeholder="Jane Doe"
                    className={`${inputCls} ${darkInputCls}`}
                    value={signupName}
                    onChange={(e) => setSignupName(e.target.value)}
                    required
                  />
                </div>
              </div>

              <div className="space-y-1.5">
                <Label htmlFor="signup-email" className="text-sm text-[#5E657B]">Email</Label>
                <div className="relative">
                  <Mail className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#5E657B]" />
                  <Input
                    id="signup-email"
                    type="email"
                    placeholder="you@university.edu"
                    className={`${inputCls} ${darkInputCls}`}
                    value={signupEmail}
                    onChange={(e) => setSignupEmail(e.target.value)}
                    required
                  />
                </div>
              </div>

              <div className="space-y-1.5">
                <Label htmlFor="signup-password" className="text-sm text-[#5E657B]">Password</Label>
                <div className="relative">
                  <Lock className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#5E657B]" />
                  <Input
                    id="signup-password"
                    type="password"
                    placeholder="••••••••"
                    className={`${inputCls} ${darkInputCls}`}
                    value={signupPassword}
                    onChange={(e) => setSignupPassword(e.target.value)}
                    required
                  />
                </div>
              </div>

              <div className="space-y-1.5">
                <Label htmlFor="signup-confirm" className="text-sm text-[#5E657B]">Confirm Password</Label>
                <div className="relative">
                  <Lock className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#5E657B]" />
                  <Input
                    id="signup-confirm"
                    type="password"
                    placeholder="••••••••"
                    className={`${inputCls} ${darkInputCls}`}
                    value={signupConfirmPassword}
                    onChange={(e) => setSignupConfirmPassword(e.target.value)}
                    required
                  />
                </div>
              </div>

              <div
                className="p-3 rounded-lg text-sm"
                style={{ background: "rgba(94,101,123,0.08)", color: "#5E657B" }}
              >
                Registering as:{" "}
                <span className="font-semibold text-[#210706] capitalize">{selectedRole}</span>
              </div>

              <button
                type="submit"
                className="w-full py-2.5 rounded-full text-[#F1E6D2] font-medium transition-colors"
                style={{ ...PLAYFAIR, background: "#891D1A", fontSize: "15px" }}
                onMouseEnter={(e) => (e.currentTarget.style.background = "#210706")}
                onMouseLeave={(e) => (e.currentTarget.style.background = "#891D1A")}
              >
                Create Account
              </button>

              <p className="text-center text-sm text-[#5E657B]">
                Already have an account?{" "}
                <button
                  type="button"
                  className="font-medium hover:underline"
                  style={{ color: "#891D1A" }}
                  onClick={() => setActiveTab("login")}
                >
                  Sign in
                </button>
              </p>
            </form>
          )}
        </div>
      </div>
    </div>
  );
}
