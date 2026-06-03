import {
  Booking,
  BookingStatus,
  CalendarEvent,
  ClassroomIssue,
  IssueStatus,
  MaintenanceBlock,
  Room,
  RoomAvailabilityFilters,
  UserRole,
} from "../types/classReserve";

const mockRooms: Room[] = [
  { id: 1, name: "Room A-301", building: "Building A", floor: "3rd Floor", capacity: 30, type: "Lecture", equipment: ["Projector", "Whiteboard", "Wi-Fi"], status: "available" },
  { id: 2, name: "Room A-302", building: "Building A", floor: "3rd Floor", capacity: 40, type: "Lecture", equipment: ["Projector", "Computer", "Wi-Fi"], status: "booked", conflictWarning: "Booked from 2:00 PM to 4:00 PM" },
  { id: 3, name: "Lab C-107", building: "Building C", floor: "1st Floor", capacity: 60, type: "Lab", equipment: ["Computers", "Projector", "Wi-Fi"], status: "available" },
  { id: 4, name: "Room D-202", building: "Building D", floor: "2nd Floor", capacity: 35, type: "Lecture", equipment: ["Whiteboard", "Wi-Fi"], status: "maintenance", maintenanceWarning: "HVAC repairs" },
  { id: 5, name: "Seminar Hall", building: "Admin Building", floor: "Ground Floor", capacity: 120, type: "Auditorium", equipment: ["Sound System", "Projector", "Stage"], status: "available" },
];

const mockBookings: Booking[] = [
  { id: 1, title: "Software Engineering Extra Class", requesterName: "Dr. Sarah Johnson", requesterRole: "faculty", roomName: "Room A-301", building: "Building A", date: "2026-06-08", startTime: "10:00", endTime: "11:30", attendees: 42, priority: "high", status: "approved", hasDocument: false, conflictStatus: "clear" },
  { id: 2, title: "Computing Club Workshop", requesterName: "Computing Club", requesterRole: "club", roomName: "Lab C-107", building: "Building C", date: "2026-06-09", startTime: "14:00", endTime: "16:00", attendees: 55, priority: "medium", status: "pending", hasDocument: true, conflictStatus: "clear" },
  { id: 3, title: "Project Presentation Practice", requesterName: "Michael Chen", requesterRole: "student", roomName: "Room A-302", building: "Building A", date: "2026-06-10", startTime: "13:00", endTime: "14:00", attendees: 8, priority: "standard", status: "rejected", hasDocument: false, conflictStatus: "conflict", rejectionReason: "Overlaps with approved faculty reservation." },
];

const mockMaintenance: MaintenanceBlock[] = [
  { id: 1, roomId: 4, roomName: "Room D-202", startDateTime: "2026-06-11 09:00", endDateTime: "2026-06-12 17:00", reason: "HVAC repairs" },
];

const mockIssues: ClassroomIssue[] = [
  {
    id: 1,
    title: "Projector flickering during presentations",
    roomName: "Room A-301",
    category: "Projector/Equipment Issue",
    postedBy: "Michael Chen",
    userRole: "student",
    createdAt: "2026-06-03 09:15",
    description: "The projector flickers every few minutes and makes slides hard to read.",
    status: "Open",
    priority: "Medium",
    comments: [{ id: 1, authorName: "Computing Club", authorRole: "club", message: "We saw this too during a workshop.", createdAt: "2026-06-03 10:00" }],
    upvotes: 7,
  },
  {
    id: 2,
    title: "Schedule conflict for Lab C-107",
    roomName: "Lab C-107",
    category: "Schedule Conflict",
    postedBy: "Dr. Sarah Johnson",
    userRole: "faculty",
    createdAt: "2026-06-03 11:30",
    description: "An extra class and club workshop appear to be assigned to the same time.",
    status: "Under Review",
    priority: "High",
    comments: [],
    upvotes: 3,
    relatedBookingId: 2,
  },
];

function wait<T>(value: T): Promise<T> {
  return new Promise((resolve) => setTimeout(() => resolve(value), 120));
}

export async function getAvailableRooms(filters: RoomAvailabilityFilters = {}) {
  return wait(mockRooms.filter((room) => {
    if (filters.minimumCapacity && room.capacity < filters.minimumCapacity) return false;
    if (filters.building && room.building !== filters.building) return false;
    if (filters.roomType && room.type !== filters.roomType) return false;
    if (filters.equipment?.length && !filters.equipment.every((item) => room.equipment.includes(item))) return false;
    return room.status !== "disabled";
  }));
}

export async function createBooking(payload: Partial<Booking>) {
  const priority = payload.requesterRole === "faculty" ? "high" : payload.requesterRole === "club" ? "medium" : "standard";
  return wait({ id: Date.now(), status: "pending" as BookingStatus, priority, ...payload });
}

export async function getMyBookings(role: UserRole = "student") {
  if (role === "admin" || role === "faculty") return wait(mockBookings);
  return wait(mockBookings.filter((booking) => booking.requesterRole === role || booking.requesterRole === "student"));
}

export async function getAllBookings() {
  return wait([...mockBookings].sort((a, b) => priorityRank(b.requesterRole) - priorityRank(a.requesterRole)));
}

export async function getPendingApprovals(role: UserRole) {
  const allowedRoles: UserRole[] = role === "faculty" ? ["student", "club"] : ["student", "club", "faculty"];
  return wait(mockBookings.filter((booking) => booking.status === "pending" && allowedRoles.includes(booking.requesterRole)));
}

export async function approveBooking(id: number, reason?: string) {
  return wait({ ok: true, id, status: "approved" as BookingStatus, reason });
}

export async function rejectBooking(id: number, reason: string) {
  return wait({ ok: true, id, status: "rejected" as BookingStatus, reason });
}

export async function getCalendarEvents() {
  const bookingEvents: CalendarEvent[] = mockBookings.map((booking) => ({
    id: booking.id,
    title: booking.title,
    roomName: booking.roomName,
    date: booking.date,
    startTime: booking.startTime,
    endTime: booking.endTime,
    status: booking.status,
    ownerRole: booking.requesterRole,
  }));
  const maintenanceEvents: CalendarEvent[] = mockMaintenance.map((block) => ({
    id: block.id,
    title: `Maintenance: ${block.reason}`,
    roomName: block.roomName,
    date: block.startDateTime.slice(0, 10),
    startTime: block.startDateTime.slice(11, 16),
    endTime: block.endDateTime.slice(11, 16),
    status: "maintenance",
  }));
  return wait([...bookingEvents, ...maintenanceEvents]);
}

export async function createMaintenanceBlock(payload: Partial<MaintenanceBlock>) {
  return wait({ id: Date.now(), ...payload });
}

export async function getMaintenanceBlocks() {
  return wait(mockMaintenance);
}

export async function getIssues() {
  return wait(mockIssues);
}

export async function createIssue(payload: Partial<ClassroomIssue>) {
  return wait({ id: Date.now(), status: "Open" as IssueStatus, comments: [], upvotes: 0, ...payload });
}

export async function getIssueById(id: number) {
  return wait(mockIssues.find((issue) => issue.id === id) || null);
}

export async function addIssueComment(issueId: number, comment: string) {
  return wait({ ok: true, issueId, comment });
}

export async function updateIssueStatus(issueId: number, status: IssueStatus, reason?: string) {
  return wait({ ok: true, issueId, status, reason });
}

export async function createMaintenanceBlockFromIssue(issueId: number, payload: Partial<MaintenanceBlock>) {
  return wait({ ok: true, issueId, maintenanceBlock: { id: Date.now(), ...payload } });
}

function priorityRank(role: UserRole) {
  if (role === "faculty") return 3;
  if (role === "club") return 2;
  return 1;
}
