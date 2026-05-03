import { useState } from "react";
import { useNavigate } from "react-router";
import { Card, CardContent } from "../components/ui/card";
import { Button } from "../components/ui/button";
import { Badge } from "../components/ui/badge";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "../components/ui/table";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "../components/ui/select";
import { Input } from "../components/ui/input";
import { Users, Calendar } from "lucide-react";
import { useAuth } from "../context/AuthContext";

const rooms = [
  {
    id: 1,
    name: "Room A-301",
    capacity: 30,
    status: "available",
    equipment: ["Projector", "Whiteboard", "Wi-Fi"],
    building: "Building A",
  },
  {
    id: 2,
    name: "Room A-302",
    capacity: 40,
    status: "booked",
    equipment: ["Projector", "Computer", "Wi-Fi"],
    building: "Building A",
  },
  {
    id: 3,
    name: "Lab C-105",
    capacity: 25,
    status: "available",
    equipment: ["Computers", "Wi-Fi", "Projector"],
    building: "Building C",
  },
  {
    id: 4,
    name: "Auditorium B",
    capacity: 200,
    status: "available",
    equipment: ["Audio System", "Projector", "Stage"],
    building: "Building B",
  },
  {
    id: 5,
    name: "Room D-202",
    capacity: 35,
    status: "maintenance",
    equipment: ["Whiteboard", "Wi-Fi"],
    building: "Building D",
  },
  {
    id: 6,
    name: "Room E-101",
    capacity: 20,
    status: "available",
    equipment: ["TV Display", "Wi-Fi"],
    building: "Building E",
  },
  {
    id: 7,
    name: "Lab C-106",
    capacity: 30,
    status: "booked",
    equipment: ["Computers", "Wi-Fi", "Printer"],
    building: "Building C",
  },
  {
    id: 8,
    name: "Room B-205",
    capacity: 45,
    status: "available",
    equipment: ["Projector", "Whiteboard", "Wi-Fi", "Computer"],
    building: "Building B",
  },
];

export function Rooms() {
  const [capacityFilter, setCapacityFilter] = useState("all");
  const [statusFilter, setStatusFilter] = useState("all");
  const navigate = useNavigate();
  const { user } = useAuth();

  const filteredRooms = rooms.filter((room) => {
    if (capacityFilter !== "all") {
      const cap = parseInt(capacityFilter);
      if (room.capacity < cap) return false;
    }
    if (statusFilter !== "all" && room.status !== statusFilter) {
      return false;
    }
    return true;
  });

  const handleRequest = (roomName: string) => {
    if (user?.role === "faculty") {
      navigate("/faculty/new-booking", { state: { roomName } });
      return;
    }

    if (user?.role === "admin") {
      navigate("/admin/new-booking", { state: { roomName } });
      return;
    }

    navigate("/student/new-booking", { state: { roomName } });
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case "available":
        return (
          <Badge className="bg-green-100 dark:bg-green-950 text-green-700 dark:text-green-300 hover:bg-green-100 dark:hover:bg-green-950">
            Available
          </Badge>
        );
      case "booked":
        return (
          <Badge className="bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-950">
            Booked
          </Badge>
        );
      case "maintenance":
        return (
          <Badge className="bg-red-100 dark:bg-red-950 text-red-700 dark:text-red-300 hover:bg-red-100 dark:hover:bg-red-950">
            Maintenance
          </Badge>
        );
      default:
        return null;
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">Rooms</h1>
        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
          Browse and manage classroom spaces
        </p>
      </div>

      <Card className="rounded-2xl border-gray-200 dark:border-gray-800 dark:bg-gray-900">
        <CardContent className="p-4">
          <div className="flex flex-wrap gap-4">
            <div className="flex items-center gap-2">
              <Calendar className="w-4 h-4 text-gray-500 dark:text-gray-400" />
              <Input
                type="date"
                className="rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                placeholder="Select date"
              />
            </div>

            <Select value={capacityFilter} onValueChange={setCapacityFilter}>
              <SelectTrigger className="w-[180px] rounded-xl dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                <SelectValue placeholder="Capacity" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Capacities</SelectItem>
                <SelectItem value="20">20+ people</SelectItem>
                <SelectItem value="30">30+ people</SelectItem>
                <SelectItem value="40">40+ people</SelectItem>
                <SelectItem value="100">100+ people</SelectItem>
              </SelectContent>
            </Select>

            <Select value={statusFilter} onValueChange={setStatusFilter}>
              <SelectTrigger className="w-[180px] rounded-xl dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                <SelectValue placeholder="Status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Status</SelectItem>
                <SelectItem value="available">Available</SelectItem>
                <SelectItem value="booked">Booked</SelectItem>
                <SelectItem value="maintenance">Maintenance</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </CardContent>
      </Card>

      <Card className="rounded-2xl border-gray-200 dark:border-gray-800 dark:bg-gray-900">
        <CardContent className="p-0">
          <Table>
            <TableHeader>
              <TableRow className="border-gray-200 dark:border-gray-800">
                <TableHead className="dark:text-gray-400">Room Name</TableHead>
                <TableHead className="dark:text-gray-400">Capacity</TableHead>
                <TableHead className="dark:text-gray-400">Status</TableHead>
                <TableHead className="dark:text-gray-400">Equipment</TableHead>
                <TableHead className="text-right dark:text-gray-400">Action</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {filteredRooms.map((room) => (
                <TableRow key={room.id} className="border-gray-200 dark:border-gray-800">
                  <TableCell className="font-medium">
                    <div>
                      <p className="text-gray-900 dark:text-white">{room.name}</p>
                      <p className="text-sm text-gray-500 dark:text-gray-400">{room.building}</p>
                    </div>
                  </TableCell>

                  <TableCell>
                    <div className="flex items-center gap-2">
                      <Users className="w-4 h-4 text-gray-500 dark:text-gray-400" />
                      <span className="text-gray-700 dark:text-gray-300">{room.capacity}</span>
                    </div>
                  </TableCell>

                  <TableCell>{getStatusBadge(room.status)}</TableCell>

                  <TableCell>
                    <div className="flex flex-wrap gap-1">
                      {room.equipment.slice(0, 3).map((eq, idx) => (
                        <Badge
                          key={idx}
                          variant="outline"
                          className="text-xs border-gray-300 dark:border-gray-700 dark:text-gray-300"
                        >
                          {eq}
                        </Badge>
                      ))}
                    </div>
                  </TableCell>

                  <TableCell className="text-right">
                    <Button
                      size="sm"
                      className="bg-blue-600 hover:bg-blue-700 rounded-xl"
                      disabled={room.status !== "available"}
                      onClick={() => handleRequest(room.name)}
                    >
                      Request
                    </Button>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  );
}