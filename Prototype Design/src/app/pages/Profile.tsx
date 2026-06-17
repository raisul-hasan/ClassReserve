import { Card, CardContent, CardHeader, CardTitle } from "../components/ui/card";
import { Button } from "../components/ui/button";
import { Input } from "../components/ui/input";
import { Label } from "../components/ui/label";
import { User, Mail, Phone, Building2 } from "lucide-react";

export function Profile() {
  return (
    <div className="space-y-6 max-w-4xl">
      <div>
        <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">Profile</h1>
        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
          Manage your account information
        </p>
      </div>

      {/* Profile Header */}
      <Card className="rounded-2xl border-gray-200 dark:border-gray-800 dark:bg-gray-900">
        <CardContent className="p-6">
          <div className="flex items-center gap-6">
            <div className="w-20 h-20 bg-blue-600 rounded-full flex items-center justify-center">
              <User className="w-10 h-10 text-white" />
            </div>
            <div className="flex-1">
              <h2 className="text-xl font-semibold text-gray-900 dark:text-white">Admin User</h2>
              <p className="text-gray-500 dark:text-gray-400">System Administrator</p>
              <Button variant="outline" className="mt-3 rounded-xl dark:border-gray-700 dark:text-gray-300">
                Change Photo
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Personal Information */}
      <Card className="rounded-2xl border-gray-200 dark:border-gray-800 dark:bg-gray-900">
        <CardHeader>
          <CardTitle className="dark:text-white">Personal Information</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="firstName" className="dark:text-gray-300">First Name</Label>
              <div className="relative">
                <User className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                <Input
                  id="firstName"
                  placeholder="John"
                  className="pl-9 rounded-xl dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                  defaultValue="Admin"
                />
              </div>
            </div>
            <div className="space-y-2">
              <Label htmlFor="lastName" className="dark:text-gray-300">Last Name</Label>
              <div className="relative">
                <User className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                <Input
                  id="lastName"
                  placeholder="Doe"
                  className="pl-9 rounded-xl dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                  defaultValue="User"
                />
              </div>
            </div>
          </div>

          <div className="space-y-2">
            <Label htmlFor="email" className="dark:text-gray-300">Email Address</Label>
            <div className="relative">
              <Mail className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
              <Input
                id="email"
                type="email"
                placeholder="admin@university.edu"
                className="pl-9 rounded-xl dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                defaultValue="admin@university.edu"
              />
            </div>
          </div>

          <div className="space-y-2">
            <Label htmlFor="phone" className="dark:text-gray-300">Phone Number</Label>
            <div className="relative">
              <Phone className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
              <Input
                id="phone"
                type="tel"
                placeholder="+1 (555) 123-4567"
                className="pl-9 rounded-xl dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                defaultValue="+1 (555) 123-4567"
              />
            </div>
          </div>

          <div className="space-y-2">
            <Label htmlFor="department" className="dark:text-gray-300">Department</Label>
            <div className="relative">
              <Building2 className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
              <Input
                id="department"
                placeholder="Administration"
                className="pl-9 rounded-xl dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                defaultValue="Administration"
              />
            </div>
          </div>

          <div className="pt-4">
            <Button className="bg-blue-600 hover:bg-blue-700 rounded-xl">
              Save Changes
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Security Settings */}
      <Card className="rounded-2xl border-gray-200 dark:border-gray-800 dark:bg-gray-900">
        <CardHeader>
          <CardTitle className="dark:text-white">Security</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="current-password" className="dark:text-gray-300">Current Password</Label>
            <Input
              id="current-password"
              type="password"
              placeholder="••••••••"
              className="rounded-xl dark:bg-gray-800 dark:border-gray-700 dark:text-white"
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="new-password" className="dark:text-gray-300">New Password</Label>
            <Input
              id="new-password"
              type="password"
              placeholder="••••••••"
              className="rounded-xl dark:bg-gray-800 dark:border-gray-700 dark:text-white"
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="confirm-password" className="dark:text-gray-300">Confirm New Password</Label>
            <Input
              id="confirm-password"
              type="password"
              placeholder="••••••••"
              className="rounded-xl dark:bg-gray-800 dark:border-gray-700 dark:text-white"
            />
          </div>

          <div className="pt-4">
            <Button variant="outline" className="rounded-xl dark:border-gray-700 dark:text-gray-300">
              Update Password
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}