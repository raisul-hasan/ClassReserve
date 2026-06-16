import { useMemo, useState } from "react";
import { useLocation, useNavigate } from "react-router";
import { Card, CardContent, CardHeader, CardTitle } from "../components/ui/card";
import { Button } from "../components/ui/button";
import { Input } from "../components/ui/input";
import { Label } from "../components/ui/label";
import { Textarea } from "../components/ui/textarea";
import { Badge } from "../components/ui/badge";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "../components/ui/select";
import { CalendarDays, Clock3, DoorOpen, Users, ArrowLeft } from "lucide-react";
import { useAuth } from "../context/AuthContext";

const roomOptions = [
  { name: "Room A-301", capacity: 30, building: "Building A", status: "available" },
  { name: "Room A-302", capacity: 40, building: "Building A", status: "booked" },
  { name: "Lab C-105", capacity: 25, building: "Building C", status: "available" },
  { name: "Auditorium B", capacity: 200, building: "Building B", status: "available" },
  { name: "Room D-202", capacity: 35, building: "Building D", status: "maintenance" },
  { name: "Room E-101", capacity: 20, building: "Building E", status: "available" },
  { name: "Lab C-106", capacity: 30, building: "Building C", status: "booked" },
  { name: "Room B-205", capacity: 45, building: "Building B", status: "available" },
];

export function NewBooking() {
  const navigate = useNavigate();
  const location = useLocation();
  const { user } = useAuth();

  const prefilledRoom = (location.state as { roomName?: string } | null)?.roomName || "";

  const [selectedRoom, setSelectedRoom] = useState(prefilledRoom);
  const [eventName, setEventName] = useState("");
  const [bookingDate, setBookingDate] = useState("");
  const [startTime, setStartTime] = useState("");
  const [endTime, setEndTime] = useState("");
  const [attendees, setAttendees] = useState("");
  const [details, setDetails] = useState("");

  const availableRooms = useMemo(
    () => roomOptions.filter((room) => room.status === "available"),
    []
  );

  const selectedRoomInfo = availableRooms.find((room) => room.name === selectedRoom);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    if (!selectedRoom || !eventName || !bookingDate || !startTime || !endTime || !attendees) {
      alert("Please complete all required fields.");
      return;
    }

    alert(`Booking request submitted for ${selectedRoom}.`);

    if (user?.role === "faculty") {
      navigate("/faculty/bookings");
      return;
    }

    if (user?.role === "admin") {
      navigate("/admin/bookings");
      return;
    }

    navigate("/student/bookings");
  };

  const handleCancel = () => {
    if (user?.role === "faculty") {
      navigate("/faculty/rooms");
      return;
    }

    if (user?.role === "admin") {
      navigate("/admin/rooms");
      return;
    }

    navigate("/student/rooms");
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <Button
          variant="outline"
          className="rounded-xl border-gray-300 dark:border-gray-700 dark:text-gray-300"
          onClick={handleCancel}
        >
          <ArrowLeft className="w-4 h-4 mr-2" />
          Back
        </Button>

        <div>
          <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">New Booking</h1>
          <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Submit a classroom reservation request
          </p>
        </div>
      </div>

      <div className="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <Card className="xl:col-span-2 rounded-2xl border-gray-200 dark:border-gray-800 dark:bg-gray-900">
          <CardHeader>
            <CardTitle className="text-gray-900 dark:text-white">Booking Details</CardTitle>
          </CardHeader>

          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-5">
              <div className="space-y-2">
                <Label className="dark:text-gray-300">Select Room</Label>
                <Select value={selectedRoom} onValueChange={setSelectedRoom}>
                  <SelectTrigger className="rounded-xl dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                    <SelectValue placeholder="Choose an available room" />
                  </SelectTrigger>
                  <SelectContent>
                    {availableRooms.map((room) => (
                      <SelectItem key={room.name} value={room.name}>
                        {room.name} • {room.building} • {room.capacity} seats
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-2">
                <Label htmlFor="event-name" className="dark:text-gray-300">
                  Event Name
                </Label>
                <Input
                  id="event-name"
                  value={eventName}
                  onChange={(e) => setEventName(e.target.value)}
                  placeholder="Example: AI Club Workshop"
                  className="rounded-xl dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                />
              </div>

              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="booking-date" className="dark:text-gray-300">
                    Date
                  </Label>
                  <Input
                    id="booking-date"
                    type="date"
                    value={bookingDate}
                    onChange={(e) => setBookingDate(e.target.value)}
                    className="rounded-xl dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                  />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="start-time" className="dark:text-gray-300">
                    Start Time
                  </Label>
                  <Input
                    id="start-time"
                    type="time"
                    value={startTime}
                    onChange={(e) => setStartTime(e.target.value)}
                    className="rounded-xl dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                  />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="end-time" className="dark:text-gray-300">
                    End Time
                  </Label>
                  <Input
                    id="end-time"
                    type="time"
                    value={endTime}
                    onChange={(e) => setEndTime(e.target.value)}
                    className="rounded-xl dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                  />
                </div>
              </div>

              <div className="space-y-2">
                <Label htmlFor="attendees" className="dark:text-gray-300">
                  Expected Attendees
                </Label>
                <Input
                  id="attendees"
                  type="number"
                  min="1"
                  value={attendees}
                  onChange={(e) => setAttendees(e.target.value)}
                  placeholder="Enter expected number of attendees"
                  className="rounded-xl dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="details" className="dark:text-gray-300">
                  Event Details
                </Label>
                <Textarea
                  id="details"
                  value={details}
                  onChange={(e) => setDetails(e.target.value)}
                  placeholder="Add a short description of the event, class, or meeting"
                  className="min-h-[120px] rounded-xl dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                />
              </div>

              <div className="flex items-center justify-end gap-3">
                <Button
                  type="button"
                  variant="outline"
                  className="rounded-xl border-gray-300 dark:border-gray-700 dark:text-gray-300"
                  onClick={handleCancel}
                >
                  Cancel
                </Button>

                <Button type="submit" className="bg-blue-600 hover:bg-blue-700 rounded-xl">
                  Submit Booking Request
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>

        <Card className="rounded-2xl border-gray-200 dark:border-gray-800 dark:bg-gray-900">
          <CardHeader>
            <CardTitle className="text-gray-900 dark:text-white">Booking Summary</CardTitle>
          </CardHeader>

          <CardContent className="space-y-4">
            <div className="p-4 rounded-xl bg-gray-50 dark:bg-gray-800 space-y-3">
              <div className="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <DoorOpen className="w-4 h-4" />
                <span className="font-medium">Room:</span>
                <span>{selectedRoom || "Not selected"}</span>
              </div>

              {selectedRoomInfo && (
                <>
                  <div className="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <Users className="w-4 h-4" />
                    <span className="font-medium">Capacity:</span>
                    <span>{selectedRoomInfo.capacity} seats</span>
                  </div>

                  <div className="text-sm text-gray-700 dark:text-gray-300">
                    <span className="font-medium">Building:</span> {selectedRoomInfo.building}
                  </div>

                  <Badge className="bg-green-100 dark:bg-green-950 text-green-700 dark:text-green-300 hover:bg-green-100 dark:hover:bg-green-950">
                    Available
                  </Badge>
                </>
              )}

              <div className="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <CalendarDays className="w-4 h-4" />
                <span className="font-medium">Date:</span>
                <span>{bookingDate || "Not selected"}</span>
              </div>

              <div className="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <Clock3 className="w-4 h-4" />
                <span className="font-medium">Time:</span>
                <span>
                  {startTime && endTime ? `${startTime} - ${endTime}` : "Not selected"}
                </span>
              </div>
            </div>

            <div className="rounded-xl border border-blue-200 dark:border-blue-900 bg-blue-50 dark:bg-blue-950/30 p-4">
              <p className="text-sm font-medium text-blue-900 dark:text-blue-300">
                Booking Workflow
              </p>
              <p className="text-sm text-blue-700 dark:text-blue-400 mt-2">
                Student and faculty requests are submitted to the booking workflow. Faculty requests
                can receive higher priority approval.
              </p>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}