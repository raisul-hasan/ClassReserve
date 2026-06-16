import { Card, CardContent, CardHeader, CardTitle } from "../components/ui/card";
import { DoorOpen, BookOpen, Clock, AlertTriangle } from "lucide-react";
import { Badge } from "../components/ui/badge";

const stats = [
  { 
    title: 'Total Rooms', 
    value: '42', 
    icon: DoorOpen, 
    color: 'text-blue-600 dark:text-blue-400',
    bgColor: 'bg-blue-50 dark:bg-blue-950'
  },
  { 
    title: 'Active Bookings', 
    value: '18', 
    icon: BookOpen, 
    color: 'text-green-600 dark:text-green-400',
    bgColor: 'bg-green-50 dark:bg-green-950'
  },
  { 
    title: 'Pending Requests', 
    value: '7', 
    icon: Clock, 
    color: 'text-yellow-600 dark:text-yellow-400',
    bgColor: 'bg-yellow-50 dark:bg-yellow-950'
  },
  { 
    title: 'Conflicts Today', 
    value: '2', 
    icon: AlertTriangle, 
    color: 'text-red-600 dark:text-red-400',
    bgColor: 'bg-red-50 dark:bg-red-950'
  },
];

const recentActivity = [
  {
    user: 'Dr. Sarah Johnson',
    action: 'booked',
    room: 'Room A-301',
    time: '2 minutes ago',
    type: 'faculty',
  },
  {
    user: 'Engineering Club',
    action: 'requested',
    room: 'Auditorium B',
    time: '15 minutes ago',
    type: 'club',
  },
  {
    user: 'Admin',
    action: 'approved booking for',
    room: 'Lab C-105',
    time: '1 hour ago',
    type: 'approved',
  },
  {
    user: 'Michael Chen',
    action: 'requested',
    room: 'Room D-202',
    time: '2 hours ago',
    type: 'student',
  },
  {
    user: 'Admin',
    action: 'marked',
    room: 'Room E-101 as maintenance',
    time: '3 hours ago',
    type: 'maintenance',
  },
];

export function Dashboard() {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">Admin Dashboard</h1>
        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
          System-wide overview of classroom booking system
        </p>
      </div>

      {/* Conflict Alerts */}
      <Card className="rounded-2xl border-red-200 dark:border-red-900 bg-red-50 dark:bg-red-950/30">
        <CardContent className="p-4">
          <div className="flex items-start gap-3">
            <AlertTriangle className="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5" />
            <div>
              <p className="text-sm font-medium text-red-900 dark:text-red-300">
                2 Scheduling Conflicts Detected
              </p>
              <p className="text-sm text-red-700 dark:text-red-400 mt-1">
                Room D-202 has overlapping bookings for Apr 3, 2026. Immediate action required.
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Statistics Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {stats.map((stat) => (
          <Card key={stat.title} className="rounded-2xl border-gray-200 dark:border-gray-800 dark:bg-gray-900">
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-gray-600 dark:text-gray-400">{stat.title}</p>
                  <p className="text-3xl font-semibold text-gray-900 dark:text-white mt-2">
                    {stat.value}
                  </p>
                </div>
                <div className={`w-12 h-12 ${stat.bgColor} rounded-xl flex items-center justify-center`}>
                  <stat.icon className={`w-6 h-6 ${stat.color}`} />
                </div>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      {/* Activity Feed */}
      <Card className="rounded-2xl border-gray-200 dark:border-gray-800 dark:bg-gray-900">
        <CardHeader>
          <CardTitle className="dark:text-white">Recent Activity</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {recentActivity.map((activity, index) => (
              <div
                key={index}
                className="flex items-start gap-4 pb-4 last:pb-0 border-b last:border-b-0 border-gray-100 dark:border-gray-800"
              >
                <div className="w-10 h-10 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center flex-shrink-0">
                  <span className="text-sm font-medium text-gray-700 dark:text-gray-300">
                    {activity.user.charAt(0)}
                  </span>
                </div>
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-2 flex-wrap">
                    <span className="font-medium text-gray-900 dark:text-white">
                      {activity.user}
                    </span>
                    <span className="text-gray-600 dark:text-gray-400">{activity.action}</span>
                    <span className="font-medium text-gray-900 dark:text-white">
                      {activity.room}
                    </span>
                    {activity.type === 'faculty' && (
                      <Badge className="bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300 hover:bg-purple-100 dark:hover:bg-purple-950">
                        Faculty
                      </Badge>
                    )}
                    {activity.type === 'club' && (
                      <Badge className="bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-950">
                        Club
                      </Badge>
                    )}
                    {activity.type === 'student' && (
                      <Badge className="bg-green-100 dark:bg-green-950 text-green-700 dark:text-green-300 hover:bg-green-100 dark:hover:bg-green-950">
                        Student
                      </Badge>
                    )}
                  </div>
                  <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">{activity.time}</p>
                </div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}