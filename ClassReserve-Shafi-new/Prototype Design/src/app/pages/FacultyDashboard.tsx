import { useState } from "react";
import { Card, CardContent } from "../components/ui/card";
import { Button } from "../components/ui/button";
import { Badge } from "../components/ui/badge";
import { Calendar, Check, X, Clock, AlertCircle, Plus } from "lucide-react";

type RequestItem = {
  id: number;
  room: string;
  requester: string;
  role: "Student" | "Club";
  event: string;
  date: string;
  time: string;
  priority: "low" | "medium";
};

type Reservation = {
  id: number;
  room: string;
  course: string;
  date: string;
  time: string;
  recurring: string;
};

const initialPendingRequests: RequestItem[] = [
  {
    id: 1,
    room: "Lab C-105",
    requester: "Michael Chen",
    role: "Student",
    event: "Study Group Session",
    date: "Apr 2, 2026",
    time: "3:00 PM - 5:00 PM",
    priority: "low",
  },
  {
    id: 2,
    room: "Room E-101",
    requester: "Dance Club",
    role: "Club",
    event: "Dance Practice",
    date: "Apr 5, 2026",
    time: "4:00 PM - 6:00 PM",
    priority: "medium",
  },
  {
    id: 3,
    room: "Room B-205",
    requester: "Lisa Anderson",
    role: "Student",
    event: "Tutorial Session",
    date: "Apr 4, 2026",
    time: "11:00 AM - 12:00 PM",
    priority: "low",
  },
];

const initialReservations: Reservation[] = [
  {
    id: 1,
    room: "Room A-301",
    course: "Math 101",
    date: "Apr 1, 2026",
    time: "10:00 AM - 12:00 PM",
    recurring: "Weekly",
  },
  {
    id: 2,
    room: "Lab C-106",
    course: "Physics Lab",
    date: "Apr 2, 2026",
    time: "9:00 AM - 12:00 PM",
    recurring: "Weekly",
  },
  {
    id: 3,
    room: "Auditorium B",
    course: "Guest Lecture Series",
    date: "Apr 4, 2026",
    time: "1:00 PM - 3:00 PM",
    recurring: "One-time",
  },
];

const weekDays = ["Mon", "Tue", "Wed", "Thu", "Fri"];
const timeSlots = ["9", "10", "11", "12", "1"];

const calendarBookings = [
  { day: 0, slot: 0, course: "Math 101" },
  { day: 1, slot: 1, course: "Physics Lab" },
  { day: 2, slot: 2, course: "Math 101" },
  { day: 3, slot: 0, course: "Guest Lecture" },
];

export function FacultyDashboard() {
  const [pendingRequests, setPendingRequests] = useState<RequestItem[]>(initialPendingRequests);
  const [myReservations, setMyReservations] = useState<Reservation[]>(initialReservations);

  const getPriorityBadge = (priority: string, role: string) => {
    switch (priority) {
      case "medium":
        return (
          <Badge className="bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-950">
            Medium - {role}
          </Badge>
        );
      case "low":
      default:
        return (
          <Badge className="bg-green-100 dark:bg-green-950 text-green-700 dark:text-green-300 hover:bg-green-100 dark:hover:bg-green-950">
            Low - {role}
          </Badge>
        );
    }
  };

  const handleApprove = (requestId: number) => {
    const selected = pendingRequests.find((item) => item.id === requestId);
    if (!selected) return;

    setPendingRequests((prev) => prev.filter((item) => item.id !== requestId));
    setMyReservations((prev) => [
      {
        id: Date.now(),
        room: selected.room,
        course: selected.event,
        date: selected.date,
        time: selected.time,
        recurring: "Approved",
      },
      ...prev,
    ]);

    alert(`Approved: ${selected.event}`);
  };

  const handleReject = (requestId: number) => {
    const selected = pendingRequests.find((item) => item.id === requestId);
    if (!selected) return;

    setPendingRequests((prev) => prev.filter((item) => item.id !== requestId));
    alert(`Rejected: ${selected.event}`);
  };

  const handleQuickReserve = () => {
    const newReservation: Reservation = {
      id: Date.now(),
      room: "Room F-401",
      course: "Faculty Reserved Session",
      date: "Apr 7, 2026",
      time: "2:00 PM - 4:00 PM",
      recurring: "High Priority",
    };

    setMyReservations((prev) => [newReservation, ...prev]);
    alert("Quick reserve created successfully.");
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between gap-4 flex-wrap">
        <div>
          <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">
            Faculty Dashboard
          </h1>
          <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Manage your classes and review booking requests
          </p>
        </div>

        <Button onClick={handleQuickReserve} className="bg-purple-600 hover:bg-purple-700 rounded-xl gap-2">
          <Plus className="w-4 h-4" />
          Quick Reserve (High Priority)
        </Button>
      </div>

      <Card className="rounded-2xl border-purple-200 dark:border-purple-900 bg-purple-50 dark:bg-purple-950/30">
        <CardContent className="p-4">
          <div className="flex items-start gap-3">
            <AlertCircle className="w-5 h-5 text-purple-600 dark:text-purple-400 mt-0.5" />
            <div>
              <p className="text-sm font-medium text-purple-900 dark:text-purple-300">
                Faculty Priority Booking Active
              </p>
              <p className="text-sm text-purple-700 dark:text-purple-400 mt-1">
                Your booking requests have high priority and are typically approved first.
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <Card className="lg:col-span-2 rounded-2xl border-gray-200 dark:border-gray-800 dark:bg-gray-900">
          <CardContent className="p-0">
            <div className="p-4 border-b border-gray-200 dark:border-gray-800">
              <h3 className="font-semibold text-gray-900 dark:text-white">
                Pending Student Requests
              </h3>
              <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Review and approve booking requests
              </p>
            </div>

            <div className="p-4 space-y-4">
              {pendingRequests.map((request) => (
                <div
                  key={request.id}
                  className="p-4 border border-gray-200 dark:border-gray-800 rounded-xl bg-white dark:bg-gray-800/50"
                >
                  <div className="flex items-start justify-between mb-3 gap-3">
                    <div>
                      <p className="font-medium text-gray-900 dark:text-white">{request.event}</p>
                      <p className="text-sm text-gray-500 dark:text-gray-400">{request.requester}</p>
                    </div>
                    {getPriorityBadge(request.priority, request.role)}
                  </div>

                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm mb-3">
                    <div>
                      <span className="text-gray-500 dark:text-gray-400">Room:</span>
                      <span className="ml-2 text-gray-900 dark:text-white font-medium">
                        {request.room}
                      </span>
                    </div>
                    <div>
                      <span className="text-gray-500 dark:text-gray-400">Date:</span>
                      <span className="ml-2 text-gray-900 dark:text-white">{request.date}</span>
                    </div>
                  </div>

                  <div className="flex items-center gap-1 text-sm text-gray-600 dark:text-gray-400 mb-3">
                    <Clock className="w-3 h-3" />
                    {request.time}
                  </div>

                  <div className="flex gap-2">
                    <Button
                      size="sm"
                      variant="outline"
                      className="flex-1 rounded-xl border-gray-300 dark:border-gray-700 hover:bg-red-50 dark:hover:bg-red-950/30 hover:border-red-300 dark:hover:border-red-800 hover:text-red-700 dark:hover:text-red-400"
                      onClick={() => handleReject(request.id)}
                    >
                      <X className="w-4 h-4 mr-1" />
                      Reject
                    </Button>

                    <Button
                      size="sm"
                      className="flex-1 bg-green-600 hover:bg-green-700 rounded-xl"
                      onClick={() => handleApprove(request.id)}
                    >
                      <Check className="w-4 h-4 mr-1" />
                      Approve
                    </Button>
                  </div>
                </div>
              ))}

              {pendingRequests.length === 0 && (
                <div className="p-6 text-center text-sm text-gray-500 dark:text-gray-400">
                  No pending requests left.
                </div>
              )}
            </div>
          </CardContent>
        </Card>

        <Card className="rounded-2xl border-gray-200 dark:border-gray-800 dark:bg-gray-900">
          <CardContent className="p-0">
            <div className="p-4 border-b border-gray-200 dark:border-gray-800">
              <h3 className="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <Calendar className="w-4 h-4" />
                This Week
              </h3>
            </div>

            <div className="p-4">
              <div className="space-y-2">
                {weekDays.map((day, dayIndex) => (
                  <div key={day} className="flex items-center gap-2">
                    <div className="w-12 text-xs font-medium text-gray-600 dark:text-gray-400">
                      {day}
                    </div>
                    <div className="flex-1 flex gap-1">
                      {timeSlots.map((_, slotIndex) => {
                        const booking = calendarBookings.find(
                          (b) => b.day === dayIndex && b.slot === slotIndex
                        );

                        return (
                          <div
                            key={slotIndex}
                            className={`h-8 flex-1 rounded ${
                              booking
                                ? "bg-purple-100 dark:bg-purple-950 border border-purple-300 dark:border-purple-800"
                                : "bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700"
                            }`}
                            title={booking?.course}
                          />
                        );
                      })}
                    </div>
                  </div>
                ))}
              </div>

              <div className="mt-4 flex items-center gap-2 text-xs">
                <div className="flex items-center gap-1">
                  <div className="w-3 h-3 bg-purple-100 dark:bg-purple-950 border border-purple-300 dark:border-purple-800 rounded" />
                  <span className="text-gray-600 dark:text-gray-400">Booked</span>
                </div>
                <div className="flex items-center gap-1">
                  <div className="w-3 h-3 bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded" />
                  <span className="text-gray-600 dark:text-gray-400">Free</span>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <Card className="rounded-2xl border-gray-200 dark:border-gray-800 dark:bg-gray-900">
        <CardContent className="p-0">
          <div className="p-4 border-b border-gray-200 dark:border-gray-800">
            <h3 className="font-semibold text-gray-900 dark:text-white">
              My Classes & Reservations
            </h3>
          </div>

          <div className="p-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {myReservations.map((reservation) => (
              <div
                key={reservation.id}
                className="p-4 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700"
              >
                <div className="flex items-start justify-between mb-2 gap-3">
                  <div>
                    <p className="font-medium text-gray-900 dark:text-white">{reservation.course}</p>
                    <p className="text-sm text-gray-500 dark:text-gray-400">{reservation.room}</p>
                  </div>
                  <Badge
                    variant="outline"
                    className="text-xs border-purple-300 dark:border-purple-700 text-purple-700 dark:text-purple-300"
                  >
                    {reservation.recurring}
                  </Badge>
                </div>

                <div className="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                  <div className="flex items-center gap-1">
                    <Calendar className="w-3 h-3" />
                    {reservation.date}
                  </div>
                  <div className="flex items-center gap-1">
                    <Clock className="w-3 h-3" />
                    {reservation.time}
                  </div>
                </div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}