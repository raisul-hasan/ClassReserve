import { Card, CardContent } from "../components/ui/card";
import { Badge } from "../components/ui/badge";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "../components/ui/table";
import { Clock } from "lucide-react";

const bookings = [
  {
    id: 1,
    eventName: 'Math 101 Lecture',
    userRole: 'Faculty',
    user: 'Dr. Sarah Johnson',
    room: 'Room A-301',
    date: 'Apr 1, 2026',
    time: '10:00 AM - 12:00 PM',
    status: 'approved',
  },
  {
    id: 2,
    eventName: 'Engineering Club Meeting',
    userRole: 'Club',
    user: 'Engineering Club',
    room: 'Room B-205',
    date: 'Apr 1, 2026',
    time: '2:00 PM - 4:00 PM',
    status: 'approved',
  },
  {
    id: 3,
    eventName: 'Study Group Session',
    userRole: 'Student',
    user: 'Michael Chen',
    room: 'Lab C-105',
    date: 'Apr 2, 2026',
    time: '3:00 PM - 5:00 PM',
    status: 'pending',
  },
  {
    id: 4,
    eventName: 'Physics Lab',
    userRole: 'Faculty',
    user: 'Prof. David Lee',
    room: 'Lab C-106',
    date: 'Apr 2, 2026',
    time: '9:00 AM - 12:00 PM',
    status: 'approved',
  },
  {
    id: 5,
    eventName: 'Project Presentation',
    userRole: 'Student',
    user: 'Emma Wilson',
    room: 'Room D-202',
    date: 'Apr 3, 2026',
    time: '1:00 PM - 2:00 PM',
    status: 'rejected',
  },
  {
    id: 6,
    eventName: 'Guest Lecture Series',
    userRole: 'Faculty',
    user: 'Dr. Maria Garcia',
    room: 'Auditorium B',
    date: 'Apr 4, 2026',
    time: '2:00 PM - 5:00 PM',
    status: 'approved',
  },
  {
    id: 7,
    eventName: 'Dance Club Practice',
    userRole: 'Club',
    user: 'Dance Club',
    room: 'Room E-101',
    date: 'Apr 5, 2026',
    time: '4:00 PM - 6:00 PM',
    status: 'pending',
  },
  {
    id: 8,
    eventName: 'Tutorial Session',
    userRole: 'Student',
    user: 'James Brown',
    room: 'Room A-302',
    date: 'Apr 5, 2026',
    time: '10:00 AM - 11:00 AM',
    status: 'approved',
  },
];

export function Bookings() {
  const getRoleBadge = (role: string) => {
    switch (role) {
      case 'Faculty':
        return (
          <Badge className="bg-purple-100 text-purple-700 hover:bg-purple-100">
            Faculty
          </Badge>
        );
      case 'Club':
        return (
          <Badge className="bg-blue-100 text-blue-700 hover:bg-blue-100">
            Club
          </Badge>
        );
      case 'Student':
        return (
          <Badge className="bg-green-100 text-green-700 hover:bg-green-100">
            Student
          </Badge>
        );
      default:
        return null;
    }
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'approved':
        return (
          <Badge className="bg-green-100 dark:bg-green-950 text-green-700 dark:text-green-300 hover:bg-green-100 dark:hover:bg-green-950">
            Approved
          </Badge>
        );
      case 'pending':
        return (
          <Badge className="bg-yellow-100 dark:bg-yellow-950 text-yellow-700 dark:text-yellow-300 hover:bg-yellow-100 dark:hover:bg-yellow-950">
            Pending
          </Badge>
        );
      case 'rejected':
        return (
          <Badge className="bg-red-100 dark:bg-red-950 text-red-700 dark:text-red-300 hover:bg-red-100 dark:hover:bg-red-950">
            Rejected
          </Badge>
        );
      default:
        return null;
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">Bookings</h1>
        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
          View all room booking requests and their status
        </p>
      </div>

      <Card className="rounded-2xl border-gray-200 dark:border-gray-800 dark:bg-gray-900">
        <CardContent className="p-0">
          <Table>
            <TableHeader>
              <TableRow className="border-gray-200 dark:border-gray-800">
                <TableHead className="dark:text-gray-400">Event Name</TableHead>
                <TableHead className="dark:text-gray-400">User / Role</TableHead>
                <TableHead className="dark:text-gray-400">Room</TableHead>
                <TableHead className="dark:text-gray-400">Time</TableHead>
                <TableHead className="dark:text-gray-400">Status</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {bookings.map((booking) => (
                <TableRow key={booking.id} className="border-gray-200 dark:border-gray-800">
                  <TableCell className="font-medium text-gray-900 dark:text-white">
                    {booking.eventName}
                  </TableCell>
                  <TableCell>
                    <div className="space-y-1">
                      <p className="text-gray-900 dark:text-white">{booking.user}</p>
                      {getRoleBadge(booking.userRole)}
                    </div>
                  </TableCell>
                  <TableCell className="text-gray-700 dark:text-gray-300">{booking.room}</TableCell>
                  <TableCell>
                    <div className="space-y-1">
                      <p className="text-gray-900 dark:text-white">{booking.date}</p>
                      <div className="flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400">
                        <Clock className="w-3 h-3" />
                        {booking.time}
                      </div>
                    </div>
                  </TableCell>
                  <TableCell>{getStatusBadge(booking.status)}</TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  );
}