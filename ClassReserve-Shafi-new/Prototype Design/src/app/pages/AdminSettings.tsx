import { useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "../components/ui/card";
import { Button } from "../components/ui/button";
import { Badge } from "../components/ui/badge";
import { Switch } from "../components/ui/switch";
import { Lock, Wrench } from "lucide-react";

type RoomItem = {
  id: number;
  name: string;
  building: string;
  isBlocked: boolean;
  isUnderMaintenance: boolean;
};

const initialRooms: RoomItem[] = [
  { id: 1, name: "Room A-301", building: "Building A", isBlocked: false, isUnderMaintenance: false },
  { id: 2, name: "Room A-302", building: "Building A", isBlocked: false, isUnderMaintenance: false },
  { id: 3, name: "Lab C-105", building: "Building C", isBlocked: false, isUnderMaintenance: false },
  { id: 4, name: "Auditorium B", building: "Building B", isBlocked: false, isUnderMaintenance: false },
  { id: 5, name: "Room D-202", building: "Building D", isBlocked: true, isUnderMaintenance: true },
  { id: 6, name: "Room E-101", building: "Building E", isBlocked: false, isUnderMaintenance: false },
];

export function AdminSettings() {
  const [rooms, setRooms] = useState<RoomItem[]>(initialRooms);
  const [autoApproveFaculty, setAutoApproveFaculty] = useState(false);
  const [conflictDetection, setConflictDetection] = useState(true);

  const toggleBlock = (id: number) => {
    setRooms((prev) =>
      prev.map((room) =>
        room.id === id
          ? {
              ...room,
              isBlocked: !room.isBlocked,
            }
          : room
      )
    );
  };

  const toggleMaintenance = (id: number) => {
    setRooms((prev) =>
      prev.map((room) =>
        room.id === id
          ? {
              ...room,
              isUnderMaintenance: !room.isUnderMaintenance,
              isBlocked: !room.isUnderMaintenance ? true : room.isBlocked,
            }
          : room
      )
    );
  };

  const getStatusBadge = (room: RoomItem) => {
    if (room.isBlocked) {
      return (
        <Badge className="bg-red-100 dark:bg-red-950 text-red-700 dark:text-red-300 hover:bg-red-100 dark:hover:bg-red-950 gap-1">
          <Lock className="w-3 h-3" />
          Blocked
        </Badge>
      );
    }

    return (
      <Badge className="bg-green-100 dark:bg-green-950 text-green-700 dark:text-green-300 hover:bg-green-100 dark:hover:bg-green-950 gap-1">
        <Lock className="w-3 h-3" />
        Active
      </Badge>
    );
  };

  const getMaintenanceBadge = (room: RoomItem) => {
    if (room.isUnderMaintenance) {
      return (
        <Badge className="bg-yellow-100 dark:bg-yellow-950 text-yellow-700 dark:text-yellow-300 hover:bg-yellow-100 dark:hover:bg-yellow-950 gap-1">
          <Wrench className="w-3 h-3" />
          Under Maintenance
        </Badge>
      );
    }

    return <span className="text-gray-500 dark:text-gray-400">—</span>;
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">Admin Settings</h1>
        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
          Manage rooms and system configuration
        </p>
      </div>

      <Card className="rounded-2xl border-gray-200 dark:border-gray-800 dark:bg-gray-900">
        <CardHeader>
          <CardTitle className="text-gray-900 dark:text-white">Room Management</CardTitle>
        </CardHeader>

        <CardContent className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-gray-200 dark:border-gray-800 text-left">
                <th className="py-3 pr-4 text-gray-500 dark:text-gray-400 font-medium">Room</th>
                <th className="py-3 pr-4 text-gray-500 dark:text-gray-400 font-medium">Status</th>
                <th className="py-3 pr-4 text-gray-500 dark:text-gray-400 font-medium">Maintenance</th>
                <th className="py-3 text-right text-gray-500 dark:text-gray-400 font-medium">Actions</th>
              </tr>
            </thead>

            <tbody>
              {rooms.map((room) => (
                <tr key={room.id} className="border-b border-gray-200 dark:border-gray-800 last:border-0">
                  <td className="py-4 pr-4">
                    <div>
                      <p className="font-medium text-gray-900 dark:text-white">{room.name}</p>
                      <p className="text-gray-500 dark:text-gray-400">{room.building}</p>
                    </div>
                  </td>

                  <td className="py-4 pr-4">{getStatusBadge(room)}</td>

                  <td className="py-4 pr-4">{getMaintenanceBadge(room)}</td>

                  <td className="py-4">
                    <div className="flex justify-end gap-2 flex-wrap">
                      <Button
                        variant="outline"
                        size="sm"
                        className="rounded-xl border-gray-300 dark:border-gray-700 dark:text-gray-300"
                        onClick={() => toggleBlock(room.id)}
                      >
                        {room.isBlocked ? "Unblock" : "Block"}
                      </Button>

                      <Button
                        variant="outline"
                        size="sm"
                        className="rounded-xl border-gray-300 dark:border-gray-700 dark:text-gray-300"
                        onClick={() => toggleMaintenance(room.id)}
                      >
                        {room.isUnderMaintenance ? "End Maintenance" : "Start Maintenance"}
                      </Button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </CardContent>
      </Card>

      <Card className="rounded-2xl border-gray-200 dark:border-gray-800 dark:bg-gray-900">
        <CardHeader>
          <CardTitle className="text-gray-900 dark:text-white">System Settings</CardTitle>
        </CardHeader>

        <CardContent className="space-y-6">
          <div className="flex items-center justify-between gap-4 py-2 border-b border-gray-200 dark:border-gray-800">
            <div>
              <p className="font-medium text-gray-900 dark:text-white">Auto-approve Faculty Requests</p>
              <p className="text-sm text-gray-500 dark:text-gray-400">
                Automatically approve booking requests from faculty members
              </p>
            </div>
            <Switch checked={autoApproveFaculty} onCheckedChange={setAutoApproveFaculty} />
          </div>

          <div className="flex items-center justify-between gap-4 py-2">
            <div>
              <p className="font-medium text-gray-900 dark:text-white">Conflict Detection</p>
              <p className="text-sm text-gray-500 dark:text-gray-400">
                Automatically detect and flag scheduling conflicts
              </p>
            </div>
            <Switch checked={conflictDetection} onCheckedChange={setConflictDetection} />
          </div>
        </CardContent>
      </Card>
    </div>
  );
}