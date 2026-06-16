import { useEffect, useState } from "react";
import { useNavigate } from "react-router";
import { Card, CardContent, CardHeader, CardTitle } from "../components/ui/card";
import { Button } from "../components/ui/button";
import { Input } from "../components/ui/input";
import { Label } from "../components/ui/label";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "../components/ui/tabs";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "../components/ui/select";
import { DoorOpen, Mail, Lock, User, Moon, Sun } from "lucide-react";
import { useAuth } from "../context/AuthContext";
import { useTheme } from "../context/ThemeContext";

type UserRole = "student" | "faculty" | "admin";

export function Auth() {
  const navigate = useNavigate();
  const { login, signup, user } = useAuth();
  const { theme, toggleTheme } = useTheme();

  const [selectedRole, setSelectedRole] = useState<UserRole>("student");
  const [activeTab, setActiveTab] = useState<string>("login");

  const [loginEmail, setLoginEmail] = useState("");
  const [loginPassword, setLoginPassword] = useState("");

  const [signupName, setSignupName] = useState("");
  const [signupEmail, setSignupEmail] = useState("");
  const [signupPassword, setSignupPassword] = useState("");
  const [signupConfirmPassword, setSignupConfirmPassword] = useState("");

  useEffect(() => {
    if (!user) return;

    if (user.role === "student") navigate("/student");
    else if (user.role === "faculty") navigate("/faculty");
    else navigate("/admin");
  }, [user, navigate]);

  const handleRoleChange = (role: UserRole) => {
    setSelectedRole(role);
    if (role === "admin") {
      setActiveTab("login");
    }
  };

  const handleLogin = (e: React.FormEvent) => {
    e.preventDefault();

    if (!loginEmail.trim() || !loginPassword.trim()) {
      alert("Please enter email and password.");
      return;
    }

    login(loginEmail, loginPassword, selectedRole);

    if (selectedRole === "student") navigate("/student");
    else if (selectedRole === "faculty") navigate("/faculty");
    else navigate("/admin");
  };

  const handleSignup = (e: React.FormEvent) => {
    e.preventDefault();

    if (selectedRole === "admin") {
      alert("Admin accounts are managed by the system.");
      return;
    }

    if (!signupName.trim() || !signupEmail.trim() || !signupPassword.trim()) {
      alert("Please complete all fields.");
      return;
    }

    if (signupPassword !== signupConfirmPassword) {
      alert("Passwords do not match.");
      return;
    }

    signup(signupName, signupEmail, signupPassword, selectedRole);

    if (selectedRole === "student") navigate("/student");
    else navigate("/faculty");
  };

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-gray-950 flex items-center justify-center p-4 transition-colors relative">
      <div className="absolute top-4 right-4">
        <Button
          type="button"
          variant="outline"
          size="icon"
          onClick={toggleTheme}
          className="rounded-xl border-gray-300 dark:border-gray-700 bg-white/80 dark:bg-gray-900/80 backdrop-blur dark:text-white"
        >
          {theme === "light" ? <Moon className="w-5 h-5" /> : <Sun className="w-5 h-5" />}
        </Button>
      </div>

      <Card className="w-full max-w-md rounded-2xl border-gray-200 dark:border-gray-800 dark:bg-gray-900 shadow-sm">
        <CardHeader className="text-center space-y-4 pb-4">
          <div className="flex justify-center">
            <div className="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center shadow-sm">
              <DoorOpen className="w-9 h-9 text-white" />
            </div>
          </div>

          <div>
            <CardTitle className="text-2xl text-gray-900 dark:text-white">
              Classroom Management System
            </CardTitle>
            <p className="text-sm text-gray-500 dark:text-gray-400 mt-2">
              University Booking Platform
            </p>
          </div>

          <div className="space-y-2 text-left">
            <Label className="text-sm text-gray-700 dark:text-gray-300">Select Your Role</Label>
            <Select value={selectedRole} onValueChange={(value) => handleRoleChange(value as UserRole)}>
              <SelectTrigger className="rounded-xl dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="student">Student</SelectItem>
                <SelectItem value="faculty">Faculty</SelectItem>
                <SelectItem value="admin">Admin</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </CardHeader>

        <CardContent>
          <Tabs value={activeTab} onValueChange={setActiveTab}>
            <TabsList className="grid w-full grid-cols-2 mb-6 dark:bg-gray-800 rounded-xl">
              <TabsTrigger value="login" className="rounded-xl dark:text-gray-300">
                Login
              </TabsTrigger>
              <TabsTrigger
                value="signup"
                disabled={selectedRole === "admin"}
                className="rounded-xl dark:text-gray-300"
              >
                Sign Up
              </TabsTrigger>
            </TabsList>

            {selectedRole === "admin" && (
              <div className="mb-4 p-3 bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-900 rounded-xl">
                <p className="text-sm text-blue-800 dark:text-blue-300">
                  Admin accounts are managed by the system.
                </p>
              </div>
            )}

            <TabsContent value="login" className="space-y-4">
              <form onSubmit={handleLogin} className="space-y-4">
                <div className="space-y-2">
                  <Label htmlFor="login-email" className="dark:text-gray-300">Email</Label>
                  <div className="relative">
                    <Mail className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <Input
                      id="login-email"
                      type="email"
                      placeholder="your.email@university.edu"
                      className="pl-9 rounded-xl dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                      value={loginEmail}
                      onChange={(e) => setLoginEmail(e.target.value)}
                      required
                    />
                  </div>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="login-password" className="dark:text-gray-300">Password</Label>
                  <div className="relative">
                    <Lock className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <Input
                      id="login-password"
                      type="password"
                      placeholder="••••••••"
                      className="pl-9 rounded-xl dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                      value={loginPassword}
                      onChange={(e) => setLoginPassword(e.target.value)}
                      required
                    />
                  </div>
                </div>

                <div className="flex justify-between items-center">
                  <span className="text-xs text-gray-500 dark:text-gray-400 capitalize">
                    Logging in as {selectedRole}
                  </span>
                  <button
                    type="button"
                    className="text-sm text-blue-600 dark:text-blue-400 hover:underline"
                    onClick={() => alert("Password reset flow can be connected later.")}
                  >
                    Forgot password?
                  </button>
                </div>

                <Button
                  type="submit"
                  className="w-full bg-blue-600 hover:bg-blue-700 rounded-xl"
                >
                  Login
                </Button>
              </form>
            </TabsContent>

            <TabsContent value="signup" className="space-y-4">
              <form onSubmit={handleSignup} className="space-y-4">
                <div className="space-y-2">
                  <Label htmlFor="signup-name" className="dark:text-gray-300">Full Name</Label>
                  <div className="relative">
                    <User className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <Input
                      id="signup-name"
                      type="text"
                      placeholder="John Doe"
                      className="pl-9 rounded-xl dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                      value={signupName}
                      onChange={(e) => setSignupName(e.target.value)}
                      required
                    />
                  </div>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="signup-email" className="dark:text-gray-300">Email</Label>
                  <div className="relative">
                    <Mail className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <Input
                      id="signup-email"
                      type="email"
                      placeholder="your.email@university.edu"
                      className="pl-9 rounded-xl dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                      value={signupEmail}
                      onChange={(e) => setSignupEmail(e.target.value)}
                      required
                    />
                  </div>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="signup-password" className="dark:text-gray-300">Password</Label>
                  <div className="relative">
                    <Lock className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <Input
                      id="signup-password"
                      type="password"
                      placeholder="••••••••"
                      className="pl-9 rounded-xl dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                      value={signupPassword}
                      onChange={(e) => setSignupPassword(e.target.value)}
                      required
                    />
                  </div>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="signup-confirm" className="dark:text-gray-300">Confirm Password</Label>
                  <div className="relative">
                    <Lock className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <Input
                      id="signup-confirm"
                      type="password"
                      placeholder="••••••••"
                      className="pl-9 rounded-xl dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                      value={signupConfirmPassword}
                      onChange={(e) => setSignupConfirmPassword(e.target.value)}
                      required
                    />
                  </div>
                </div>

                <div className="rounded-xl bg-gray-50 dark:bg-gray-800 p-3 text-sm text-gray-600 dark:text-gray-300">
                  Account type: <span className="font-medium capitalize">{selectedRole}</span>
                </div>

                <Button
                  type="submit"
                  disabled={selectedRole === "admin"}
                  className="w-full bg-blue-600 hover:bg-blue-700 rounded-xl"
                >
                  Create Account
                </Button>
              </form>
            </TabsContent>
          </Tabs>
        </CardContent>
      </Card>
    </div>
  );
}