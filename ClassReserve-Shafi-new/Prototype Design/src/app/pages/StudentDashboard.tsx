import { useMemo, useState } from "react";
import { Card, CardContent } from "../components/ui/card";
import { Button } from "../components/ui/button";
import { Input } from "../components/ui/input";
import { Badge } from "../components/ui/badge";
import { Search, Users, CheckCircle, XCircle, Clock, Plus } from "lucide-react";

type Room = {
  id: number;
  name: string;
  capacity: number;
  status: "available";
  building: string;
};

type BookingStatus = "pending" | "approved" | "rejected";

type Booking = {
  id: number;
  room: string;
  event: string;
  date: string;
  time: string;
  status: BookingStatus;
};

type Notice = {
  id: number;
  type: "success" | "error" | "pending";
  message: string;
  time: string;
};

const initialRooms: Room[] = [
  { id: 1, name: "Room A-301", capacity: 30, status: "available", building: "Building A" },
  { id: 2, name: "Lab C-105", capacity: 25, status: "available", building: "Building C" },
  { id: 3, name: "Auditorium B", capacity: 200, status: "available", building: "Building B" },
  { id: 4, name: "Room E-101", capacity: 20, status: "available", building: "Building E" },
  { id: 5, name: "Room B-205", capacity: 45, status: "available", building: "Building B" },
];

const initialBookings: Booking[] = [
  {
    id: 1,
    room: "Lab C-105",
    event: "Study Group Session",
    date: "Apr 2, 2026",
    time: "3:00 PM - 5:00 PM",
    status: "pending",
  },
  {
    id: 2,
    room: "Room B-205",
    event: "Project Meeting",
    date: "Apr 5, 2026",
    time: "2:00 PM - 4:00 PM",
    status: "approved",
  },
  {
    id: 3,
    room: "Room D-202",
    event: "Team Presentation",
    date: "Mar 28, 2026",
    time: "1:00 PM - 2:00 PM",
    status: "rejected",
  },
];

const initialNotifications: Notice[] = [
  {
    id: 1,
    type: "success",
    message: "Your booking for Room B-205 has been approved",
    time: "2 hours ago",
  },
  {
    id: 2,
    type: "error",
    message: "Booking request for Room D-202 was rejected",
    time: "1 day ago",
  },
  {
    id: 3,
    type: "pending",
    message: "Awaiting approval for Lab C-105 booking",
    time: "2 days ago",
  },
];

export function StudentDashboard() {
  const [search, setSearch] = useState("");
  const [bookings, setBookings] = useState<Booking[]>(initialBookings);
  const [notifications, setNotifications] = useState<Notice[]>(initialNotifications);

  const filteredRooms = useMemo(() => {
    const query = search.trim().toLowerCase();
    if (!query) return initialRooms;

    return initialRooms.filter((room) => {
      return (
        room.name.toLowerCase().includes(query) ||
        room.building.toLowerCase().includes(query) ||
        String(room.capacity).includes(query)
      );
    });
  }, [search]);

  const getStatusBadge = (status: BookingStatus) => {
    switch (status) {
      case "approved":
        return (
          <Badge className="bg-green-100 dark:bg-green-950 text-green-700 dark:text-green-300 hover:bg-green-100 dark:hover:bg-green-950">
            Approved
          </Badge>
        );
      case "pending":
        return (
          <Badge className="bg-yellow-100 dark:bg-yellow-950 text-yellow-700 dark:text-yellow-300 hover:bg-yellow-100 dark:hover:bg-yellow-950">
            Pending
          </Badge>
        );
      case "rejected":
        return (
          <Badge className="bg-red-100 dark:bg-red-950 text-red-700 dark:text-red-300 hover:bg-red-100 dark:hover:bg-red-950">
            Rejected
          </Badge>
        );
    }
  };

  const getNotificationIcon = (type: Notice["type"]) => {
    switch (type) {
      case "success":
        return <CheckCircle className="w-5 h-5 text-green-600 dark:text-green-400" />;
      case "error":
        return <XCircle className="w-5 h-5 text-red-600 dark:text-red-400" />;
      case "pending":
        return <Clock className="w-5 h-5 text-yellow-600 dark:text-yellow-400" />;
    }
  };

  const addBookingRequest = (roomName: string) => {
    const alreadyPending = bookings.some(
      (booking) => booking.room === roomName && booking.status === "pending"
    );

    if (alreadyPending) {
      alert(`A pending request already exists for ${roomName}.`);
      return;
    }

    const newBooking: Booking = {
      id: Date.now(),
      room: roomName,
      event: "New Booking Request",
      date: "Apr 6, 2026",
      time: "10:00 AM - 12:00 PM",
      status: "pending",
    };

    const newNotice: Notice = {
      id: Date.now() + 1,
      type: "pending",
      message: `Booking request submitted for ${roomName}`,
      time: "Just now",
    };

    setBookings((prev) => [newBooking, ...prev]);
    setNotifications((prev) => [newNotice, ...prev]);
    alert(`Request sent for ${roomName}.`);
  };

  const handleMainRequest = () => {
    if (filteredRooms.length === 0) {
      alert("No room matches your search.");
      return;
    }

    addBookingRequest(filteredRooms[0].name);
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between gap-4 flex-wrap">
        <div>
          <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">
            Student Dashboard
          </h1>
          <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Find and request available classrooms
          </p>
        </div>

        <Button onClick={handleMainRequest} className="bg-blue-600 hover:bg-blue-700 rounded-xl gap-2">
          <Plus className="w-4 h-4" />
          Request Booking
        </Button>
      </div>

      <Card className="rounded-2xl border-gray-200 dark:border-gray-800 dark:bg-gray-900">
        <CardContent className="p-4">
          <div className="relative">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
            <Input
              placeholder="Search rooms by room name, building, or capacity..."
              className="pl-9 rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
            />
          </div>
        </CardContent>
      </Card>

      <Card className="rounded-2xl border-gray-200 dark:border-gray-800 dark:bg-gray-900">
        <CardContent className="p-0">
          <div className="p-4 border-b border-gray-200 dark:border-gray-800">
            <h3 className="font-semibold text-gray-900 dark:text-white">Available Rooms</h3>
          </div>

          <div className="p-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            {filteredRooms.map((room) => (
              <div
                key={room.id}
                className="p-4 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-800/40"
              >
                <div className="flex items-start justify-between gap-3">
                  <div>
                    <p className="font-medium text-gray-900 dark:text-white">{room.name}</p>
                    <p className="text-sm text-gray-500 dark:text-gray-400">{room.building}</p>
                  </div>
                  <Badge className="bg-green-100 dark:bg-green-950 text-green-700 dark:text-green-300 hover:bg-green-100 dark:hover:bg-green-950">
                    Available
                  </Badge>
                </div>

                <div className="mt-3 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                  <Users className="w-4 h-4" />
                  Capacity: {room.capacity}
                </div>

                <Button
                  size="sm"
                  className="mt-4 w-full bg-blue-600 hover:bg-blue-700 rounded-xl"
                  onClick={() => addBookingRequest(room.name)}
                >
                  Request
                </Button>
              </div>
            ))}

            {filteredRooms.length === 0 && (
              <div className="col-span-full p-6 text-center text-sm text-gray-500 dark:text-gray-400">
                No rooms found for this search.
              </div>
            )}
          </div>
        </CardContent>
      </Card>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Card className="rounded-2xl border-gray-200 dark:border-gray-800 dark:bg-gray-900">
          <CardContent className="p-0">
            <div className="p-4 border-b border-gray-200 dark:border-gray-800">
              <h3 className="font-semibold text-gray-900 dark:text-white">My Bookings</h3>
            </div>

            <div className="p-4 space-y-4">
              {bookings.map((booking) => (
                <div key={booking.id} className="p-4 bg-gray-50 dark:bg-gray-800 rounded-xl space-y-2">
                  <div className="flex items-start justify-between gap-3">
                    <div>
                      <p className="font-medium text-gray-900 dark:text-white">{booking.room}</p>
                      <p className="text-sm text-gray-500 dark:text-gray-400">{booking.event}</p>
                    </div>
                    {getStatusBadge(booking.status)}
                  </div>
                  <div className="flex items-center gap-1 text-sm text-gray-600 dark:text-gray-400">
                    <Clock className="w-3 h-3" />
                    {booking.date} • {booking.time}
                  </div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>

        <Card className="rounded-2xl border-gray-200 dark:border-gray-800 dark:bg-gray-900">
          <CardContent className="p-0">
            <div className="p-4 border-b border-gray-200 dark:border-gray-800">
              <h3 className="font-semibold text-gray-900 dark:text-white">Notifications</h3>
            </div>

            <div className="p-4 space-y-4">
              {notifications.map((notification) => (
                <div key={notification.id} className="flex gap-3">
                  <div className="flex-shrink-0 mt-0.5">{getNotificationIcon(notification.type)}</div>
                  <div className="min-w-0">
                    <p className="text-sm text-gray-900 dark:text-white">{notification.message}</p>
                    <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">{notification.time}</p>
                  </div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}