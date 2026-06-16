import { useMemo, useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "../components/ui/card";
import { Button } from "../components/ui/button";
import { Badge } from "../components/ui/badge";
import { AlertTriangle, Clock, Check, X } from "lucide-react";

type ApprovalRequest = {
  id: number;
  eventName: string;
  user: string;
  role: string;
  priority: "low" | "medium" | "high";
  room: string;
  date: string;
  time: string;
  hasConflict: boolean;
  conflictWith?: string;
  requestedAt: string;
  status?: "pending" | "approved" | "rejected";
};

const initialApprovalRequests: ApprovalRequest[] = [
  {
    id: 1,
    eventName: "Study Group Session",
    user: "Michael Chen",
    role: "Student",
    priority: "low",
    room: "Lab C-105",
    date: "Apr 2, 2026",
    time: "3:00 PM - 5:00 PM",
    hasConflict: false,
    requestedAt: "2 hours ago",
    status: "pending",
  },
  {
    id: 2,
    eventName: "Dance Club Practice",
    user: "Dance Club",
    role: "Club",
    priority: "medium",
    room: "Room E-101",
    date: "Apr 5, 2026",
    time: "4:00 PM - 6:00 PM",
    hasConflict: false,
    requestedAt: "5 hours ago",
    status: "pending",
  },
  {
    id: 3,
    eventName: "Research Seminar",
    user: "Dr. Robert Smith",
    role: "Faculty",
    priority: "high",
    room: "Auditorium B",
    date: "Apr 3, 2026",
    time: "2:00 PM - 4:00 PM",
    hasConflict: false,
    requestedAt: "1 day ago",
    status: "pending",
  },
  {
    id: 4,
    eventName: "Workshop",
    user: "Tech Club",
    role: "Club",
    priority: "medium",
    room: "Room D-202",
    date: "Apr 3, 2026",
    time: "2:00 PM - 3:00 PM",
    hasConflict: true,
    conflictWith: "CS Lecture already scheduled",
    requestedAt: "3 hours ago",
    status: "pending",
  },
  {
    id: 5,
    eventName: "Tutorial Session",
    user: "Lisa Anderson",
    role: "Student",
    priority: "low",
    room: "Room B-205",
    date: "Apr 4, 2026",
    time: "11:00 AM - 12:00 PM",
    hasConflict: false,
    requestedAt: "4 hours ago",
    status: "pending",
  },
  {
    id: 6,
    eventName: "Department Meeting",
    user: "Prof. Jennifer White",
    role: "Faculty",
    priority: "high",
    room: "Room A-301",
    date: "Apr 6, 2026",
    time: "10:00 AM - 12:00 PM",
    hasConflict: false,
    requestedAt: "6 hours ago",
    status: "pending",
  },
];

export function Approvals() {
  const [approvalRequests, setApprovalRequests] = useState<ApprovalRequest[]>(
    initialApprovalRequests
  );
  const [showProcessed, setShowProcessed] = useState(false);

  const pendingCount = useMemo(
    () => approvalRequests.filter((request) => request.status === "pending").length,
    [approvalRequests]
  );

  const displayedRequests = useMemo(() => {
    if (showProcessed) return approvalRequests;
    return approvalRequests.filter((request) => request.status === "pending");
  }, [approvalRequests, showProcessed]);

  const getPriorityBadge = (priority: string, role: string) => {
    switch (priority) {
      case "high":
        return (
          <Badge className="bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300 hover:bg-purple-100 dark:hover:bg-purple-950">
            High - {role}
          </Badge>
        );
      case "medium":
        return (
          <Badge className="bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-950">
            Medium - {role}
          </Badge>
        );
      case "low":
        return (
          <Badge className="bg-green-100 dark:bg-green-950 text-green-700 dark:text-green-300 hover:bg-green-100 dark:hover:bg-green-950">
            Low - {role}
          </Badge>
        );
      default:
        return null;
    }
  };

  const getStatusBadge = (status?: "pending" | "approved" | "rejected") => {
    switch (status) {
      case "approved":
        return (
          <Badge className="bg-green-100 dark:bg-green-950 text-green-700 dark:text-green-300 hover:bg-green-100 dark:hover:bg-green-950">
            Approved
          </Badge>
        );
      case "rejected":
        return (
          <Badge className="bg-red-100 dark:bg-red-950 text-red-700 dark:text-red-300 hover:bg-red-100 dark:hover:bg-red-950">
            Rejected
          </Badge>
        );
      default:
        return (
          <Badge className="bg-yellow-100 dark:bg-yellow-950 text-yellow-700 dark:text-yellow-300 hover:bg-yellow-100 dark:hover:bg-yellow-950">
            Pending
          </Badge>
        );
    }
  };

  const handleApprove = (id: number) => {
    setApprovalRequests((prev) =>
      prev.map((request) =>
        request.id === id ? { ...request, status: "approved" } : request
      )
    );
  };

  const handleReject = (id: number) => {
    setApprovalRequests((prev) =>
      prev.map((request) =>
        request.id === id ? { ...request, status: "rejected" } : request
      )
    );
  };

  return (
    <div className="space-y-6">
      <div className="flex items-start justify-between gap-4 flex-wrap">
        <div>
          <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">Approvals</h1>
          <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Review and approve pending booking requests
          </p>
          <p className="text-sm text-gray-600 dark:text-gray-300 mt-3">
            Pending requests: <span className="font-semibold">{pendingCount}</span>
          </p>
        </div>

        <Button
          variant="outline"
          className="rounded-xl border-gray-300 dark:border-gray-700 dark:text-gray-300"
          onClick={() => setShowProcessed((prev) => !prev)}
        >
          {showProcessed ? "Show Pending Only" : "Show All Requests"}
        </Button>
      </div>

      <div className="grid gap-4">
        {displayedRequests.map((request) => (
          <Card
            key={request.id}
            className={`rounded-2xl ${
              request.hasConflict
                ? "border-red-300 dark:border-red-800 bg-red-50/30 dark:bg-red-950/20"
                : "border-gray-200 dark:border-gray-800 dark:bg-gray-900"
            } ${
              request.status === "approved"
                ? "ring-1 ring-green-300 dark:ring-green-800"
                : request.status === "rejected"
                  ? "ring-1 ring-red-300 dark:ring-red-800"
                  : ""
            }`}
          >
            <CardHeader>
              <div className="flex items-start justify-between gap-4 flex-wrap">
                <div className="space-y-2">
                  <CardTitle className="text-lg dark:text-white">{request.eventName}</CardTitle>

                  <div className="flex items-center gap-2 flex-wrap">
                    {getPriorityBadge(request.priority, request.role)}
                    {getStatusBadge(request.status)}
                    {request.hasConflict && (
                      <Badge className="bg-red-100 dark:bg-red-950 text-red-700 dark:text-red-300 hover:bg-red-100 dark:hover:bg-red-950 gap-1">
                        <AlertTriangle className="w-3 h-3" />
                        Conflict
                      </Badge>
                    )}
                  </div>
                </div>

                <div className="flex gap-2">
                  <Button
                    size="sm"
                    variant="outline"
                    className="rounded-xl border-gray-300 dark:border-gray-700 hover:bg-red-50 dark:hover:bg-red-950/30 hover:border-red-300 dark:hover:border-red-800 hover:text-red-700 dark:hover:text-red-400"
                    onClick={() => handleReject(request.id)}
                    disabled={request.status !== "pending"}
                  >
                    <X className="w-4 h-4 mr-1" />
                    Reject
                  </Button>

                  <Button
                    size="sm"
                    className="bg-green-600 hover:bg-green-700 rounded-xl"
                    disabled={request.hasConflict || request.status !== "pending"}
                    onClick={() => handleApprove(request.id)}
                  >
                    <Check className="w-4 h-4 mr-1" />
                    Approve
                  </Button>
                </div>
              </div>
            </CardHeader>

            <CardContent>
              <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                  <p className="text-sm text-gray-500 dark:text-gray-400">Requested by</p>
                  <p className="text-sm font-medium text-gray-900 dark:text-white mt-1">
                    {request.user}
                  </p>
                </div>

                <div>
                  <p className="text-sm text-gray-500 dark:text-gray-400">Room</p>
                  <p className="text-sm font-medium text-gray-900 dark:text-white mt-1">
                    {request.room}
                  </p>
                </div>

                <div>
                  <p className="text-sm text-gray-500 dark:text-gray-400">Date & Time</p>
                  <p className="text-sm font-medium text-gray-900 dark:text-white mt-1">
                    {request.date}
                  </p>
                  <div className="flex items-center gap-1 text-sm text-gray-600 dark:text-gray-400 mt-0.5">
                    <Clock className="w-3 h-3" />
                    {request.time}
                  </div>
                </div>

                <div>
                  <p className="text-sm text-gray-500 dark:text-gray-400">Requested</p>
                  <p className="text-sm font-medium text-gray-900 dark:text-white mt-1">
                    {request.requestedAt}
                  </p>
                </div>
              </div>

              {request.hasConflict && (
                <div className="mt-4 p-3 bg-red-100 dark:bg-red-950/30 border border-red-200 dark:border-red-800 rounded-xl flex items-start gap-2">
                  <AlertTriangle className="w-4 h-4 text-red-600 dark:text-red-400 mt-0.5 flex-shrink-0" />
                  <div>
                    <p className="text-sm font-medium text-red-900 dark:text-red-300">
                      Scheduling Conflict
                    </p>
                    <p className="text-sm text-red-700 dark:text-red-400 mt-1">
                      {request.conflictWith}
                    </p>
                  </div>
                </div>
              )}
            </CardContent>
          </Card>
        ))}

        {displayedRequests.length === 0 && (
          <Card className="rounded-2xl border-gray-200 dark:border-gray-800 dark:bg-gray-900">
            <CardContent className="p-8 text-center">
              <p className="text-sm text-gray-500 dark:text-gray-400">
                No approval requests to show.
              </p>
            </CardContent>
          </Card>
        )}
      </div>
    </div>
  );
}