import {
  Booking,
  BookingStatus,
  CalendarEvent,
  CalendarConflictStatus,
  CalendarEventType,
  ClassroomIssue,
  IssueStatus,
  MaintenanceBlock,
  Notification,
  Room,
  RoomAvailabilityFilters,
  UserRole,
} from "../types/classReserve";

export type ApiUser = {
  id?: number | string;
  name: string;
  email: string;
  role: UserRole;
};

export type UserScopedOptions = {
  role?: UserRole;
  userId?: number | string;
  email?: string;
};

export type CalendarEventOptions = UserScopedOptions;

const API_BASE = import.meta.env.VITE_API_BASE_URL || "http://localhost/classreserve/api";

async function apiRequest<T>(path: string, options: RequestInit = {}): Promise<T> {
  const response = await fetch(`${API_BASE}/${path}`, {
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
      ...(options.headers || {}),
    },
    ...options,
  });

  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    throw new Error(data.error || "API request failed.");
  }

  return data as T;
}

async function apiFormRequest<T>(path: string, body: FormData): Promise<T> {
  const response = await fetch(`${API_BASE}/${path}`, {
    method: "POST",
    credentials: "include",
    body,
  });

  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    throw new Error(data.error || "API request failed.");
  }

  return data as T;
}

function toRoom(row: any): Room {
  return {
    id: Number(row.id),
    name: row.name,
    building: row.building || "Campus",
    floor: row.floor || undefined,
    capacity: Number(row.capacity || 0),
    type: row.type || "Lecture",
    equipment: row.equipment ? String(row.equipment).split(",").map((item) => item.trim()).filter(Boolean) : ["Projector", "Whiteboard"],
    status: row.status || "available",
  };
}

function toBooking(row: any): Booking {
  const start = row.start_datetime ? new Date(row.start_datetime.replace(" ", "T")) : null;
  const end = row.end_datetime ? new Date(row.end_datetime.replace(" ", "T")) : null;
  const requesterRole = (row.user_role || row.role || "student") as UserRole;

  return {
    id: Number(row.id),
    requesterId: row.user_id ? Number(row.user_id) : row.requesterId,
    requesterEmail: row.user_email || row.requesterEmail,
    title: row.title || "Room Booking",
    requesterName: row.user_name || "Requester",
    requesterRole,
    roomName: row.room_name || "Room",
    building: row.building || "",
    date: start ? start.toISOString().slice(0, 10) : "",
    startTime: start ? start.toTimeString().slice(0, 5) : "",
    endTime: end ? end.toTimeString().slice(0, 5) : "",
    attendees: Number(row.attendees || 0),
    priority: row.priority >= 3 ? "high" : row.priority >= 2 ? "medium" : "standard",
    status: row.status || "pending",
    hasDocument: Boolean(row.uploaded_path),
    conflictStatus: row.conflict_status || "clear",
  };
}

function eventTypeForRole(role: UserRole): CalendarEventType {
  if (role === "faculty") return "faculty_reservation";
  if (role === "club") return "club_event";
  return "student_booking";
}

function eventConflictStatus(value?: string): CalendarConflictStatus {
  if (value === "conflict" || value === "conflict_detected") return "conflict_detected";
  if (value === "maintenance" || value === "maintenance_conflict") return "maintenance_conflict";
  return "no_conflict";
}

function toCalendarEventFromBooking(booking: Booking, index = 0): CalendarEvent {
  return {
    id: booking.id,
    title: booking.title,
    roomName: booking.roomName,
    requesterName: booking.requesterName,
    requesterRole: booking.requesterRole,
    requesterId: (booking as any).requesterId,
    requesterEmail: (booking as any).requesterEmail,
    eventType: eventTypeForRole(booking.requesterRole),
    date: booking.date,
    startTime: booking.startTime,
    endTime: booking.endTime,
    status: booking.status,
    priority: booking.priority,
    conflictStatus: eventConflictStatus(booking.conflictStatus),
    ownerRole: booking.requesterRole,
  };
}

function toCalendarEventFromMaintenance(block: MaintenanceBlock, index = 0): CalendarEvent {
  return {
    id: 100000 + Number(block.id || index),
    title: block.reason,
    roomName: block.roomName,
    requesterName: "Facilities Team",
    requesterRole: "admin",
    eventType: "maintenance",
    date: block.startDateTime.slice(0, 10),
    startTime: block.startDateTime.slice(11, 16),
    endTime: block.endDateTime.slice(11, 16),
    status: "maintenance",
    priority: "high",
    conflictStatus: "maintenance_conflict",
    ownerRole: "admin",
  };
}

function toIssue(row: any): ClassroomIssue {
  return {
    id: Number(row.id),
    postedById: row.user_id ? Number(row.user_id) : row.postedById,
    postedByEmail: row.user_email || row.postedByEmail,
    title: row.title,
    roomName: row.room_name || row.roomName,
    category: row.category,
    postedBy: row.posted_by || row.postedBy || "Reporter",
    userRole: (row.user_role || row.userRole || "student") as UserRole,
    createdAt: row.created_at || row.createdAt || "",
    description: row.description,
    status: row.status || "Open",
    priority: row.priority || "Medium",
    comments: (row.comments || []).map((comment: any) => ({
      id: Number(comment.id),
      authorName: comment.author_name || comment.authorName,
      authorRole: (comment.author_role || comment.authorRole || "student") as UserRole,
      message: comment.message,
      createdAt: comment.created_at || comment.createdAt || "",
    })),
    upvotes: Number(row.upvotes || 0),
    relatedBookingId: row.related_booking_id ? Number(row.related_booking_id) : undefined,
    hasDocument: Boolean(row.has_document ?? row.hasDocument ?? row.uploaded_path),
    uploadedPath: row.uploaded_path || row.uploadedPath || undefined,
    isAffectingBooking: Boolean(row.is_affecting_booking ?? row.isAffectingBooking),
    relatedBooking: row.related_booking || row.relatedBooking || undefined,
    adminResponse: row.admin_response || row.adminResponse || undefined,
  };
}

function toNotification(row: any): Notification {
  return {
    id: Number(row.id),
    userId: row.user_id ? Number(row.user_id) : row.userId,
    title: row.title,
    message: row.message,
    type: row.type || "info",
    createdAt: row.created_at || row.createdAt || "",
    unread: !Boolean(row.is_read ?? row.read),
  };
}

const mockRooms: Room[] = [
  { id: 1, name: "Room A-301", building: "Building A", floor: "3rd Floor", capacity: 30, type: "Lecture", equipment: ["Projector", "Whiteboard", "Wi-Fi"], status: "available" },
  { id: 2, name: "Room A-302", building: "Building A", floor: "3rd Floor", capacity: 40, type: "Lecture", equipment: ["Projector", "Computer", "Wi-Fi"], status: "booked", conflictWarning: "Booked from 2:00 PM to 4:00 PM" },
  { id: 3, name: "Lab C-107", building: "Building C", floor: "1st Floor", capacity: 60, type: "Lab", equipment: ["Computers", "Projector", "Wi-Fi"], status: "available" },
  { id: 4, name: "Room D-202", building: "Building D", floor: "2nd Floor", capacity: 35, type: "Lecture", equipment: ["Whiteboard", "Wi-Fi"], status: "maintenance", maintenanceWarning: "HVAC repairs" },
  { id: 5, name: "Seminar Hall", building: "Admin Building", floor: "Ground Floor", capacity: 120, type: "Auditorium", equipment: ["Sound System", "Projector", "Stage"], status: "available" },
];

const mockBookings: Booking[] = [
  { id: 1, requesterId: 2, requesterEmail: "sarah.johnson@classreserve.test", title: "Software Engineering Extra Class", requesterName: "Dr. Sarah Johnson", requesterRole: "faculty", roomName: "Room A-301", building: "Building A", date: "2026-06-08", startTime: "10:00", endTime: "11:30", attendees: 42, priority: "high", status: "approved", hasDocument: false, conflictStatus: "clear" },
  { id: 2, requesterId: "demo-club", requesterEmail: "programming.club@uni.edu", title: "Computing Club Workshop", requesterName: "Computing Club", requesterRole: "club", roomName: "Lab C-107", building: "Building C", date: "2026-06-09", startTime: "14:00", endTime: "16:00", attendees: 55, priority: "medium", status: "pending", hasDocument: true, conflictStatus: "clear" },
  { id: 3, requesterId: "demo-student", requesterEmail: "student@classreserve.test", title: "Project Presentation Practice", requesterName: "Michael Chen", requesterRole: "student", roomName: "Room A-302", building: "Building A", date: "2026-06-10", startTime: "13:00", endTime: "14:00", attendees: 8, priority: "standard", status: "rejected", hasDocument: false, conflictStatus: "conflict", rejectionReason: "Overlaps with approved faculty reservation." },
];

const mockMaintenance: MaintenanceBlock[] = [
  { id: 1, roomId: 4, roomName: "Room D-202", startDateTime: "2026-06-11 09:00", endDateTime: "2026-06-12 17:00", reason: "HVAC repairs" },
];

const mockCalendarEvents: CalendarEvent[] = [
  {
    id: 501,
    title: "Faculty Extra Class",
    roomName: "Room 205",
    requesterName: "Dr. Sarah Johnson",
    requesterRole: "faculty",
    requesterEmail: "sarah.johnson@classreserve.test",
    eventType: "faculty_reservation",
    status: "approved",
    priority: "high",
    date: "2026-06-05",
    startTime: "10:00",
    endTime: "12:00",
    conflictStatus: "no_conflict",
    ownerRole: "faculty",
  },
  {
    id: 502,
    title: "Programming Club Workshop",
    roomName: "Lab 301",
    requesterName: "Programming Club",
    requesterRole: "club",
    requesterEmail: "programming.club@uni.edu",
    eventType: "club_event",
    status: "approved",
    priority: "medium",
    date: "2026-06-08",
    startTime: "14:00",
    endTime: "16:00",
    conflictStatus: "no_conflict",
    ownerRole: "club",
  },
  {
    id: 503,
    title: "Student Study Session",
    roomName: "Room 102",
    requesterName: "Jane Doe",
    requesterRole: "student",
    requesterEmail: "jane@uni.edu",
    eventType: "student_booking",
    status: "pending",
    priority: "standard",
    date: "2026-06-12",
    startTime: "11:00",
    endTime: "13:00",
    conflictStatus: "no_conflict",
    ownerRole: "student",
  },
  {
    id: 504,
    title: "Projector Maintenance",
    roomName: "Room 401",
    requesterName: "Facilities Team",
    requesterRole: "admin",
    eventType: "maintenance",
    status: "maintenance",
    priority: "high",
    date: "2026-06-15",
    startTime: "09:00",
    endTime: "17:00",
    conflictStatus: "maintenance_conflict",
    ownerRole: "admin",
  },
  {
    id: 505,
    title: "Debate Club Event",
    roomName: "Auditorium",
    requesterName: "Debate Club",
    requesterRole: "club",
    requesterEmail: "debate.club@uni.edu",
    eventType: "club_event",
    status: "approved",
    priority: "medium",
    date: "2026-06-20",
    startTime: "15:00",
    endTime: "18:00",
    conflictStatus: "no_conflict",
    ownerRole: "club",
  },
  {
    id: 506,
    title: "Makeup Class CSE-221",
    roomName: "Room 204",
    requesterName: "Prof. David Lee",
    requesterRole: "faculty",
    requesterEmail: "david.lee@classreserve.test",
    eventType: "faculty_reservation",
    status: "approved",
    priority: "high",
    date: "2026-06-24",
    startTime: "08:00",
    endTime: "10:00",
    conflictStatus: "no_conflict",
    ownerRole: "faculty",
  },
  {
    id: 507,
    title: "Robotics Club Demo",
    roomName: "Lab 301",
    requesterName: "Robotics Club",
    requesterRole: "club",
    requesterEmail: "robotics.club@uni.edu",
    eventType: "club_event",
    status: "pending",
    priority: "medium",
    date: "2026-06-08",
    startTime: "15:00",
    endTime: "17:00",
    conflictStatus: "conflict_detected",
    ownerRole: "club",
  },
];

const mockIssues: ClassroomIssue[] = [
  {
    id: 1,
    postedById: "demo-student",
    postedByEmail: "student@classreserve.test",
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
    postedById: 2,
    postedByEmail: "sarah.johnson@classreserve.test",
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

const mockNotifications: Notification[] = [
  { id: 1, userId: "demo-student", type: "success", title: "Booking Approved", message: "Your booking for Room A-301 has been approved.", createdAt: "2026-06-03 09:15", unread: true },
  { id: 2, userId: 1, type: "warning", title: "New Issue Report", message: "Projector flickering was reported for Room A-301.", createdAt: "2026-06-03 10:00", unread: true },
  { id: 3, userId: 1, type: "info", title: "System Update", message: "ClassReserve is running in demo mode.", createdAt: "2026-06-02 08:00", unread: false },
];

function sameOwner(ownerId: number | string | undefined, ownerEmail: string | undefined, options: UserScopedOptions = {}) {
  if (ownerId !== undefined && options.userId !== undefined && String(ownerId) === String(options.userId)) return true;
  if (ownerEmail && options.email && ownerEmail.toLowerCase() === options.email.toLowerCase()) return true;
  return false;
}

function wait<T>(value: T): Promise<T> {
  return new Promise((resolve) => setTimeout(() => resolve(value), 120));
}

export async function getSession() {
  return apiRequest<{ ok: boolean; user: ApiUser | null }>("auth.php");
}

export async function loginWithApi(email: string, password: string) {
  const data = await apiRequest<{ ok: boolean; message?: string; user: ApiUser }>("auth.php", {
    method: "POST",
    body: JSON.stringify({ action: "login", email, password }),
  });

  return data.user;
}

export async function signupWithApi(name: string, email: string, password: string, role: "student" | "club") {
  return apiRequest<{ ok: boolean; message?: string }>("auth.php", {
    method: "POST",
    body: JSON.stringify({ action: "register", name, email, password, role }),
  });
}

export async function logoutWithApi() {
  return apiRequest<{ ok: boolean }>("auth.php", {
    method: "POST",
    body: JSON.stringify({ action: "logout" }),
  });
}

export async function getProfile() {
  const data = await apiRequest<{ ok: boolean; user: ApiUser }>("profile.php");
  return data.user;
}

export async function updateProfile(name: string) {
  const data = await apiRequest<{ ok: boolean; message?: string; user: ApiUser }>("profile.php", {
    method: "POST",
    body: JSON.stringify({ action: "update_profile", name }),
  });
  return data.user;
}

export async function changePassword(currentPassword: string, newPassword: string) {
  return apiRequest<{ ok: boolean; message?: string }>("profile.php", {
    method: "POST",
    body: JSON.stringify({
      action: "change_password",
      current_password: currentPassword,
      new_password: newPassword,
    }),
  });
}

export async function getAvailableRooms(filters: RoomAvailabilityFilters = {}) {
  try {
    const params = new URLSearchParams();
    if (filters.minimumCapacity) params.set("capacity_min", String(filters.minimumCapacity));
    if (filters.building && filters.building !== "all") params.set("building", filters.building);
    if (filters.roomType && filters.roomType !== "all") params.set("type", filters.roomType);
    const query = params.toString();
    const rows = await apiRequest<any[]>(`rooms.php${query ? `?${query}` : ""}`);
    return rows.map(toRoom);
  } catch {
    return wait(mockRooms.filter((room) => {
    if (filters.minimumCapacity && room.capacity < filters.minimumCapacity) return false;
    if (filters.building && room.building !== filters.building) return false;
    if (filters.roomType && room.type !== filters.roomType) return false;
    if (filters.equipment?.length && !filters.equipment.every((item) => room.equipment.includes(item))) return false;
    return room.status !== "disabled";
    }));
  }
}

export async function createRoom(payload: Partial<Room> & { notes?: string }) {
  const data = await apiRequest<{ ok: boolean; id: number }>("rooms.php", {
    method: "POST",
    body: JSON.stringify({
      name: payload.name,
      capacity: payload.capacity,
      type: payload.type,
      building: payload.building,
      floor: payload.floor,
      equipment: payload.equipment?.join(", "),
      status: payload.status || "available",
      notes: payload.notes || "",
    }),
  });

  return {
    id: data.id,
    name: payload.name || "New Room",
    building: payload.building || "Campus",
    floor: payload.floor,
    capacity: Number(payload.capacity || 0),
    type: payload.type || "Lecture",
    equipment: payload.equipment || [],
    status: payload.status || "available",
  } as Room;
}

export async function createBooking(payload: Partial<Booking>) {
  if (payload.roomName || payload.roomName === "") {
    let room = mockRooms.find((item) => item.name === payload.roomName);
    try {
      const rooms = await getAvailableRooms();
      room = rooms.find((item) => item.name === payload.roomName) || room;
    } catch {
      // Mock room lookup remains available below.
    }
    payload = { ...payload, roomName: payload.roomName, id: payload.id };
    try {
      const start = `${payload.date} ${payload.startTime}:00`;
      const end = `${payload.date} ${payload.endTime}:00`;
      const attachment = (payload as any).attachment as File | undefined;
      if (attachment) {
        const formData = new FormData();
        formData.append("room_id", String(room?.id || (payload as any).roomId || ""));
        formData.append("start_datetime", start);
        formData.append("end_datetime", end);
        formData.append("title", payload.title || "");
        formData.append("description", (payload as any).description || "");
        formData.append("attachment", attachment);
        return await apiFormRequest<{ ok: boolean; id: number; message?: string }>("bookings.php", formData);
      }
      return await apiRequest<{ ok: boolean; id: number; message?: string }>("bookings.php", {
        method: "POST",
        body: JSON.stringify({
          room_id: room?.id || (payload as any).roomId,
          start_datetime: start,
          end_datetime: end,
          title: payload.title,
          description: (payload as any).description || "",
          priority: payload.requesterRole === "faculty" ? 3 : payload.requesterRole === "club" ? 2 : 1,
        }),
      });
    } catch {
      // Fall through to mock response below.
    }
  }

  const priority = payload.requesterRole === "faculty" ? "high" : payload.requesterRole === "club" ? "medium" : "standard";
  return wait({ id: Date.now(), status: "pending" as BookingStatus, priority, ...payload });
}

export async function getMyBookings(options: UserScopedOptions = {}) {
  try {
    const rows = await apiRequest<any[]>("bookings.php");
    return rows.map(toBooking).filter((booking) => sameOwner(booking.requesterId, booking.requesterEmail, options));
  } catch {
    return wait(mockBookings.filter((booking) => sameOwner(booking.requesterId, booking.requesterEmail, options)));
  }
}

export async function getAllBookings() {
  try {
    const rows = await apiRequest<any[]>("bookings.php");
    return rows.map(toBooking).sort((a, b) => priorityRank(b.requesterRole) - priorityRank(a.requesterRole));
  } catch {
    return wait([...mockBookings].sort((a, b) => priorityRank(b.requesterRole) - priorityRank(a.requesterRole)));
  }
}

export async function getPendingApprovals(role: UserRole) {
  try {
    const bookings = await getAllBookings();
    const allowedRoles: UserRole[] = role === "faculty" ? ["student", "club"] : ["student", "club", "faculty"];
    return bookings.filter((booking) => booking.status === "pending" && allowedRoles.includes(booking.requesterRole));
  } catch {
    const allowedRoles: UserRole[] = role === "faculty" ? ["student", "club"] : ["student", "club", "faculty"];
    return wait(mockBookings.filter((booking) => booking.status === "pending" && allowedRoles.includes(booking.requesterRole)));
  }
}

export async function approveBooking(id: number, reason?: string) {
  try {
    return await apiRequest<{ ok: boolean }>("bookings.php", {
      method: "POST",
      body: JSON.stringify({ id, action: "approve", reason }),
    });
  } catch {
    return wait({ ok: true, id, status: "approved" as BookingStatus, reason });
  }
}

export async function rejectBooking(id: number, reason: string) {
  try {
    return await apiRequest<{ ok: boolean }>("bookings.php", {
      method: "POST",
      body: JSON.stringify({ id, action: "reject", reason }),
    });
  } catch {
    return wait({ ok: true, id, status: "rejected" as BookingStatus, reason });
  }
}

export async function getCalendarEvents(_options: CalendarEventOptions = {}) {
  try {
    const [bookings, maintenance] = await Promise.all([getAllBookings(), getMaintenanceBlocks()]);
    const bookingEvents = bookings.map(toCalendarEventFromBooking);
    const maintenanceEvents = maintenance.map(toCalendarEventFromMaintenance);
    const apiEvents = [...bookingEvents, ...maintenanceEvents].filter((event) => event.date);
    return apiEvents.length ? apiEvents : wait(mockCalendarEvents);
  } catch {
    return wait(mockCalendarEvents);
  }
}

export async function createMaintenanceBlock(payload: Partial<MaintenanceBlock>) {
  try {
    return await apiRequest<{ ok: boolean; id: number }>("maintenance.php", {
      method: "POST",
      body: JSON.stringify({
        room_id: payload.roomId,
        start_datetime: payload.startDateTime,
        end_datetime: payload.endDateTime,
        reason: payload.reason,
      }),
    });
  } catch {
    return wait({ id: Date.now(), ...payload });
  }
}

export async function getMaintenanceBlocks() {
  try {
    const rows = await apiRequest<any[]>("maintenance.php");
    return rows.map((row) => ({
      id: Number(row.id),
      roomId: Number(row.room_id),
      roomName: row.room_name,
      startDateTime: row.start_datetime,
      endDateTime: row.end_datetime,
      reason: row.reason || "Maintenance",
    }));
  } catch {
    return wait(mockMaintenance);
  }
}

export async function getIssues() {
  try {
    const rows = await apiRequest<any[]>("issues.php");
    return rows.map(toIssue);
  } catch {
    return wait(mockIssues);
  }
}

export async function getMyIssues(options: UserScopedOptions = {}) {
  try {
    const rows = await apiRequest<any[]>("issues.php?mine=1");
    return rows.map(toIssue).filter((issue) => sameOwner(issue.postedById, issue.postedByEmail, options));
  } catch {
    return wait(mockIssues.filter((issue) => sameOwner(issue.postedById, issue.postedByEmail, options)));
  }
}

export async function createIssue(payload: Partial<ClassroomIssue>) {
  try {
    const attachment = (payload as any).attachment as File | undefined;
    if (attachment) {
      const formData = new FormData();
      formData.append("title", payload.title || "");
      formData.append("room_name", payload.roomName || "");
      formData.append("category", payload.category || "");
      formData.append("description", payload.description || "");
      formData.append("priority", payload.priority || "Medium");
      formData.append("has_document", payload.hasDocument ? "1" : "0");
      formData.append("is_affecting_booking", payload.isAffectingBooking ? "1" : "0");
      if (payload.relatedBooking) formData.append("related_booking", payload.relatedBooking);
      formData.append("attachment", attachment);
      const issue = await apiFormRequest<any>("issues.php", formData);
      return toIssue(issue);
    }
    const issue = await apiRequest<any>("issues.php", {
      method: "POST",
      body: JSON.stringify({
        title: payload.title,
        room_name: payload.roomName,
        category: payload.category,
        description: payload.description,
        priority: payload.priority,
        has_document: payload.hasDocument,
        is_affecting_booking: payload.isAffectingBooking,
        related_booking: payload.relatedBooking,
      }),
    });
    return toIssue(issue);
  } catch {
    return wait({ id: Date.now(), status: "Open" as IssueStatus, comments: [], upvotes: 0, ...payload });
  }
}

export async function getIssueById(id: number) {
  try {
    const issue = await apiRequest<any>(`issues.php?id=${id}`);
    return issue ? toIssue(issue) : null;
  } catch {
    return wait(mockIssues.find((issue) => issue.id === id) || null);
  }
}

export async function addIssueComment(issueId: number, comment: string) {
  try {
    return await apiRequest<{ ok: boolean; id: number }>("issues.php", {
      method: "POST",
      body: JSON.stringify({ action: "comment", issue_id: issueId, message: comment }),
    });
  } catch {
    return wait({ ok: true, issueId, comment });
  }
}

export async function updateIssueStatus(issueId: number, status: IssueStatus, reason?: string) {
  try {
    return await apiRequest<{ ok: boolean }>("issues.php", {
      method: "POST",
      body: JSON.stringify({
        action: "status",
        issue_id: issueId,
        status,
        reason: status === "Rejected" ? reason : undefined,
        admin_response: status !== "Rejected" ? reason : undefined,
      }),
    });
  } catch {
    return wait({ ok: true, issueId, status, reason });
  }
}

export async function upvoteIssue(issueId: number) {
  try {
    return await apiRequest<{ ok: boolean }>("issues.php", {
      method: "POST",
      body: JSON.stringify({ action: "upvote", issue_id: issueId }),
    });
  } catch {
    return wait({ ok: true, issueId });
  }
}

export async function createMaintenanceBlockFromIssue(issueId: number, payload: Partial<MaintenanceBlock>) {
  try {
    return await apiRequest<{ ok: boolean; id: number }>("issues.php", {
      method: "POST",
      body: JSON.stringify({
        action: "maintenance_from_issue",
        issue_id: issueId,
        start_datetime: payload.startDateTime,
        end_datetime: payload.endDateTime,
        reason: payload.reason,
      }),
    });
  } catch {
    return wait({ ok: true, issueId, maintenanceBlock: { id: Date.now(), ...payload } });
  }
}

export async function getNotifications(options: UserScopedOptions = {}) {
  try {
    const rows = await apiRequest<any[]>("notifications.php");
    const notifications = rows.map(toNotification);
    return notifications.some((notification) => notification.userId !== undefined)
      ? notifications.filter((notification) => sameOwner(notification.userId, undefined, options))
      : notifications;
  } catch {
    return wait(mockNotifications.filter((notification) => sameOwner(notification.userId, undefined, options)));
  }
}

export async function markNotificationRead(id: number) {
  try {
    return await apiRequest<{ ok: boolean }>("notifications.php", {
      method: "POST",
      body: JSON.stringify({ id }),
    });
  } catch {
    return wait({ ok: true, id });
  }
}

export async function markAllNotificationsRead() {
  try {
    return await apiRequest<{ ok: boolean }>("notifications.php", {
      method: "POST",
      body: JSON.stringify({ action: "read_all" }),
    });
  } catch {
    return wait({ ok: true });
  }
}

function priorityRank(role: UserRole) {
  if (role === "faculty") return 3;
  if (role === "club") return 2;
  return 1;
}
