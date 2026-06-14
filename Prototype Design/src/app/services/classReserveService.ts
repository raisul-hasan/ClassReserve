import {
  AuditLog,
  Booking,
  CalendarConflictStatus,
  CalendarEvent,
  CalendarEventType,
  ClassroomIssue,
  DashboardStats,
  IssueStatus,
  MaintenanceBlock,
  Notification,
  Room,
  RoomAvailabilityFilters,
  UserRole,
} from "../types/classReserve";

export type ApiUser = { id?: number | string; name: string; email: string; role: UserRole };
export type UserScopedOptions = { role?: UserRole; userId?: number | string; email?: string };
export type CalendarEventOptions = UserScopedOptions;

const API_BASE = import.meta.env.VITE_API_BASE_URL || "http://localhost/classreserve/api";

async function apiRequest<T>(path: string, options: RequestInit = {}): Promise<T> {
  let response: Response;
  try {
    response = await fetch(`${API_BASE}/${path}`, {
      credentials: "include",
      headers: { "Content-Type": "application/json", ...(options.headers || {}) },
      ...options,
    });
  } catch {
    throw new Error("Unable to reach the ClassReserve API. Confirm Apache and MySQL are running.");
  }
  const data = await response.json().catch(() => ({}));
  if (!response.ok) throw new Error(data.error || `API request failed (${response.status}).`);
  return data as T;
}

async function apiFormRequest<T>(path: string, body: FormData): Promise<T> {
  let response: Response;
  try {
    response = await fetch(`${API_BASE}/${path}`, { method: "POST", credentials: "include", body });
  } catch {
    throw new Error("Unable to reach the ClassReserve API. Confirm Apache and MySQL are running.");
  }
  const data = await response.json().catch(() => ({}));
  if (!response.ok) throw new Error(data.error || `API request failed (${response.status}).`);
  return data as T;
}

function parseDateTime(value?: string) {
  return value ? new Date(value.replace(" ", "T")) : null;
}

function toRoom(row: any): Room {
  return {
    id: Number(row.id), name: row.name, building: row.building || "Campus", floor: row.floor || undefined,
    capacity: Number(row.capacity || 0), type: row.type || "Lecture",
    equipment: row.equipment ? String(row.equipment).split(",").map((item) => item.trim()).filter(Boolean) : [],
    status: row.status || "available",
  };
}

function toBooking(row: any): Booking {
  const start = parseDateTime(row.start_datetime);
  const end = parseDateTime(row.end_datetime);
  return {
    id: Number(row.id), requesterId: row.user_id ? Number(row.user_id) : undefined,
    requesterEmail: row.user_email || undefined, roomId: row.room_id ? Number(row.room_id) : undefined,
    title: row.title || "Room Booking", requesterName: row.user_name || "Requester",
    requesterRole: (row.user_role || "student") as UserRole, roomName: row.room_name || "Room",
    building: row.building || "", date: start ? start.toISOString().slice(0, 10) : "",
    startTime: start ? start.toTimeString().slice(0, 5) : "", endTime: end ? end.toTimeString().slice(0, 5) : "",
    attendees: Number(row.attendees || 0), priority: row.priority >= 3 ? "high" : row.priority >= 2 ? "medium" : "standard",
    status: row.status || "pending", hasDocument: Boolean(row.uploaded_path),
    uploadedDocumentName: row.uploaded_path ? String(row.uploaded_path).split("/").pop() : undefined,
    description: row.description || "", conflictStatus: row.conflict_status || "clear",
    rejectionReason: row.rejection_reason || undefined, reviewedById: row.reviewed_by ? Number(row.reviewed_by) : undefined,
    reviewedBy: row.reviewer_name || undefined, reviewedAt: row.reviewed_at || undefined,
    checkinCode: row.checkin_code || undefined,
    checkedInAt: row.checked_in_at || undefined,
    noShow: Boolean(row.no_show),
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

function toCalendarEventFromBooking(booking: Booking): CalendarEvent {
  return {
    id: booking.id, title: booking.title, roomId: booking.roomId, roomName: booking.roomName,
    building: booking.building, requesterName: booking.requesterName, requesterRole: booking.requesterRole,
    requesterId: booking.requesterId, requesterEmail: booking.requesterEmail,
    eventType: eventTypeForRole(booking.requesterRole), date: booking.date, startTime: booking.startTime,
    endTime: booking.endTime, status: booking.status, priority: booking.priority,
    conflictStatus: eventConflictStatus(booking.conflictStatus), description: booking.description,
    uploadedDocumentName: booking.uploadedDocumentName, hasDocument: booking.hasDocument, ownerRole: booking.requesterRole,
  };
}

function toMaintenance(row: any): MaintenanceBlock {
  return {
    id: Number(row.id), roomId: Number(row.room_id), roomName: row.room_name, building: row.building,
    startDateTime: row.start_datetime, endDateTime: row.end_datetime, reason: row.reason || "Maintenance",
    createdBy: row.created_by || "Admin",
  };
}

function toCalendarEventFromMaintenance(block: MaintenanceBlock): CalendarEvent {
  return {
    id: 100000 + block.id, title: block.reason, roomId: block.roomId, roomName: block.roomName,
    building: block.building, requesterName: "Facilities Team", requesterRole: "admin", eventType: "maintenance",
    date: block.startDateTime.slice(0, 10), startTime: block.startDateTime.slice(11, 16),
    endTime: block.endDateTime.slice(11, 16), status: "maintenance", priority: "high",
    conflictStatus: "maintenance_conflict", description: block.reason, maintenanceWarning: block.reason, ownerRole: "admin",
  };
}

function toIssue(row: any): ClassroomIssue {
  return {
    id: Number(row.id), postedById: row.user_id ? Number(row.user_id) : undefined,
    postedByEmail: row.user_email || undefined, title: row.title, roomName: row.room_name || row.roomName,
    category: row.category, postedBy: row.posted_by || "Reporter", userRole: (row.user_role || "student") as UserRole,
    createdAt: row.created_at || "", description: row.description, status: row.status || "Open",
    priority: row.priority || "Medium", comments: (row.comments || []).map((comment: any) => ({
      id: Number(comment.id), authorName: comment.author_name || comment.authorName,
      authorRole: (comment.author_role || comment.authorRole || "student") as UserRole,
      message: comment.message, createdAt: comment.created_at || comment.createdAt || "",
    })),
    upvotes: Number(row.upvotes || 0), relatedBookingId: row.related_booking_id ? Number(row.related_booking_id) : undefined,
    hasDocument: Boolean(row.has_document ?? row.uploaded_path), uploadedPath: row.uploaded_path || undefined,
    isAffectingBooking: Boolean(row.is_affecting_booking), relatedBooking: row.related_booking || undefined,
    adminResponse: row.admin_response || undefined,
  };
}

function toNotification(row: any): Notification {
  return {
    id: Number(row.id), userId: row.user_id ? Number(row.user_id) : undefined, title: row.title,
    message: row.message, type: row.type || "info", targetType: row.target_type || undefined,
    targetId: row.target_id || undefined, targetRoute: row.target_route || undefined,
    createdAt: row.created_at || "", unread: !Boolean(row.is_read),
  };
}

function sameOwner(ownerId: number | string | undefined, ownerEmail: string | undefined, options: UserScopedOptions) {
  if (options.userId !== undefined && ownerId !== undefined) return String(ownerId) === String(options.userId);
  if (options.email && ownerEmail) return ownerEmail.toLowerCase() === options.email.toLowerCase();
  return true;
}

function priorityRank(role: UserRole) { return role === "faculty" ? 3 : role === "club" ? 2 : 1; }

export async function getSession() { return apiRequest<{ ok: boolean; user: ApiUser | null }>("auth.php"); }
export async function loginWithApi(email: string, password: string) {
  const data = await apiRequest<{ ok: boolean; user: ApiUser }>("auth.php", { method: "POST", body: JSON.stringify({ action: "login", email, password }) });
  return data.user;
}
export async function signupWithApi(name: string, email: string, password: string, role: Exclude<UserRole, "admin">) {
  return apiRequest<{ ok: boolean; message?: string }>("auth.php", { method: "POST", body: JSON.stringify({ action: "register", name, email, password, role }) });
}
export async function logoutWithApi() { return apiRequest<{ ok: boolean }>("auth.php", { method: "POST", body: JSON.stringify({ action: "logout" }) }); }
export async function getProfile() { return (await apiRequest<{ ok: boolean; user: ApiUser }>("profile.php")).user; }
export async function updateProfile(name: string) {
  return (await apiRequest<{ ok: boolean; user: ApiUser }>("profile.php", { method: "POST", body: JSON.stringify({ action: "update_profile", name }) })).user;
}
export async function changePassword(currentPassword: string, newPassword: string) {
  return apiRequest<{ ok: boolean; message?: string }>("profile.php", { method: "POST", body: JSON.stringify({ action: "change_password", current_password: currentPassword, new_password: newPassword }) });
}

export async function getAvailableRooms(filters: RoomAvailabilityFilters = {}) {
  const params = new URLSearchParams();
  if (filters.minimumCapacity) params.set("capacity_min", String(filters.minimumCapacity));
  if (filters.building && filters.building !== "all") params.set("building", filters.building);
  if (filters.roomType && filters.roomType !== "all") params.set("type", filters.roomType);
  if (filters.equipment?.length === 1) params.set("equipment", filters.equipment[0]);
  const rows = await apiRequest<any[]>(`rooms.php${params.size ? `?${params}` : ""}`);
  return rows.map(toRoom).filter((room) => room.status !== "disabled" && (!filters.equipment?.length || filters.equipment.every((item) => room.equipment.includes(item))));
}

export async function createRoom(payload: Partial<Room> & { notes?: string }) {
  const data = await apiRequest<{ ok: boolean; id: number }>("rooms.php", { method: "POST", body: JSON.stringify({
    name: payload.name, capacity: payload.capacity, type: payload.type, building: payload.building,
    floor: payload.floor, equipment: payload.equipment?.join(", "), status: payload.status || "available", notes: payload.notes || "",
  }) });
  return { ...payload, id: data.id } as Room;
}

export async function createBooking(payload: Partial<Booking>) {
  const rooms = await getAvailableRooms();
  const roomId = payload.roomId || rooms.find((room) => room.name === payload.roomName)?.id;
  if (!roomId) throw new Error("The selected room was not found in the database.");
  const start = `${payload.date} ${payload.startTime}:00`;
  const end = `${payload.date} ${payload.endTime}:00`;
  const attachment = (payload as Booking & { attachment?: File }).attachment;
  if (attachment) {
    const formData = new FormData();
    formData.append("room_id", String(roomId)); formData.append("start_datetime", start); formData.append("end_datetime", end);
    formData.append("title", payload.title || ""); formData.append("description", payload.description || ""); formData.append("attachment", attachment);
    return apiFormRequest<{ ok: boolean; id: number; message?: string }>("bookings.php", formData);
  }
  return apiRequest<{ ok: boolean; id: number; message?: string }>("bookings.php", { method: "POST", body: JSON.stringify({ room_id: roomId, start_datetime: start, end_datetime: end, title: payload.title, description: payload.description || "" }) });
}

export async function getMyBookings(options: UserScopedOptions = {}) {
  return (await apiRequest<any[]>("bookings.php")).map(toBooking).filter((booking) => sameOwner(booking.requesterId, booking.requesterEmail, options));
}
export async function getAllBookings() {
  return (await apiRequest<any[]>("bookings.php")).map(toBooking).sort((a, b) => priorityRank(b.requesterRole) - priorityRank(a.requesterRole));
}
export async function getPendingApprovals(role: UserRole) {
  const allowedRoles: UserRole[] = role === "faculty" ? ["student", "club"] : ["student", "club", "faculty"];
  return (await getAllBookings()).filter((booking) => allowedRoles.includes(booking.requesterRole));
}
export async function approveBooking(id: number, reason?: string) {
  return apiRequest<{ ok: boolean }>("bookings.php", { method: "POST", body: JSON.stringify({ id, action: "approve", reason }) });
}
export async function rejectBooking(id: number, reason: string) {
  return apiRequest<{ ok: boolean }>("bookings.php", { method: "POST", body: JSON.stringify({ id, action: "reject", reason }) });
}
export async function cancelBooking(id: number) {
  return apiRequest<{ ok: boolean }>("bookings.php", { method: "POST", body: JSON.stringify({ id, action: "cancel" }) });
}
export async function checkinBooking(code: string) {
  return apiRequest<{ ok: boolean; message?: string }>("bookings.php", { method: "POST", body: JSON.stringify({ action: "checkin", checkin_code: code }) });
}

export async function getMaintenanceBlocks() { return (await apiRequest<any[]>("maintenance.php")).map(toMaintenance); }
export async function getCalendarEvents(_options: CalendarEventOptions = {}) {
  const [bookings, maintenance] = await Promise.all([getAllBookings(), getMaintenanceBlocks()]);
  return [...bookings.map(toCalendarEventFromBooking), ...maintenance.map(toCalendarEventFromMaintenance)].filter((event) => event.date);
}
export async function createMaintenanceBlock(payload: Partial<MaintenanceBlock>) {
  return apiRequest<{ ok: boolean; id: number }>("maintenance.php", { method: "POST", body: JSON.stringify({ room_id: payload.roomId, start_datetime: payload.startDateTime, end_datetime: payload.endDateTime, reason: payload.reason }) });
}

export async function getIssues() { return (await apiRequest<any[]>("issues.php")).map(toIssue); }
export async function getMyIssues(options: UserScopedOptions = {}) {
  return (await apiRequest<any[]>("issues.php?mine=1")).map(toIssue).filter((issue) => sameOwner(issue.postedById, issue.postedByEmail, options));
}
export async function createIssue(payload: Partial<ClassroomIssue>) {
  const attachment = payload.attachment || undefined;
  if (attachment) {
    const formData = new FormData();
    formData.append("title", payload.title || ""); formData.append("room_name", payload.roomName || "");
    formData.append("category", payload.category || ""); formData.append("description", payload.description || "");
    formData.append("priority", payload.priority || "Medium"); formData.append("has_document", payload.hasDocument ? "1" : "0");
    formData.append("is_affecting_booking", payload.isAffectingBooking ? "1" : "0");
    if (payload.relatedBooking) formData.append("related_booking", payload.relatedBooking);
    formData.append("attachment", attachment);
    return toIssue(await apiFormRequest<any>("issues.php", formData));
  }
  return toIssue(await apiRequest<any>("issues.php", { method: "POST", body: JSON.stringify({ title: payload.title, room_name: payload.roomName, category: payload.category, description: payload.description, priority: payload.priority, has_document: payload.hasDocument, is_affecting_booking: payload.isAffectingBooking, related_booking: payload.relatedBooking }) }));
}
export async function getIssueById(id: number) { const issue = await apiRequest<any>(`issues.php?id=${id}`); return issue ? toIssue(issue) : null; }
export async function addIssueComment(issueId: number, comment: string) { return apiRequest<{ ok: boolean; id: number }>("issues.php", { method: "POST", body: JSON.stringify({ action: "comment", issue_id: issueId, message: comment }) }); }
export async function updateIssueStatus(issueId: number, status: IssueStatus, reason?: string) { return apiRequest<{ ok: boolean }>("issues.php", { method: "POST", body: JSON.stringify({ action: "status", issue_id: issueId, status, reason: status === "Rejected" ? reason : undefined, admin_response: status !== "Rejected" ? reason : undefined }) }); }
export async function upvoteIssue(issueId: number) { return apiRequest<{ ok: boolean }>("issues.php", { method: "POST", body: JSON.stringify({ action: "upvote", issue_id: issueId }) }); }
export async function createMaintenanceBlockFromIssue(issueId: number, payload: Partial<MaintenanceBlock>) { return apiRequest<{ ok: boolean; id: number }>("issues.php", { method: "POST", body: JSON.stringify({ action: "maintenance_from_issue", issue_id: issueId, start_datetime: payload.startDateTime, end_datetime: payload.endDateTime, reason: payload.reason }) }); }

export async function getNotifications(options: UserScopedOptions = {}) {
  return (await apiRequest<any[]>("notifications.php")).map(toNotification).filter((notification) => sameOwner(notification.userId, undefined, options));
}
export async function markNotificationRead(id: number) { return apiRequest<{ ok: boolean }>("notifications.php", { method: "POST", body: JSON.stringify({ id }) }); }
export async function markAllNotificationsRead() { return apiRequest<{ ok: boolean }>("notifications.php", { method: "POST", body: JSON.stringify({ action: "read_all" }) }); }
export async function getDashboardStats() { return apiRequest<DashboardStats>("dashboard.php"); }
export async function getAuditLogs(limit = 100) {
  const rows = await apiRequest<any[]>(`audit_logs.php?limit=${limit}`);
  return rows.map((row): AuditLog => ({ id: Number(row.id), userId: row.user_id ? Number(row.user_id) : undefined, userName: row.user_name || undefined, userEmail: row.user_email || undefined, userRole: row.user_role || undefined, action: row.action, targetType: row.target_type || undefined, targetId: row.target_id ? Number(row.target_id) : undefined, details: row.details || undefined, createdAt: row.created_at }));
}
