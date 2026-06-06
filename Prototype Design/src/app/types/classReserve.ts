export type UserRole = "student" | "club" | "faculty" | "admin";

export type BookingStatus = "pending" | "approved" | "rejected" | "cancelled";
export type BookingPriority = "standard" | "medium" | "high";
export type CalendarEventType = "student_booking" | "club_event" | "faculty_reservation" | "maintenance";
export type CalendarConflictStatus = "no_conflict" | "conflict_detected" | "maintenance_conflict";

export type RoomStatus = "available" | "booked" | "maintenance" | "disabled";

export interface Room {
  id: number;
  name: string;
  building: string;
  floor?: string;
  capacity: number;
  type: "Lecture" | "Lab" | "Seminar" | "Auditorium";
  equipment: string[];
  status: RoomStatus;
  maintenanceWarning?: string;
  conflictWarning?: string;
}

export interface Booking {
  id: number;
  requesterId?: number | string;
  requesterEmail?: string;
  roomId?: number | string;
  title: string;
  requesterName: string;
  requesterRole: UserRole;
  roomName: string;
  building: string;
  date: string;
  startTime: string;
  endTime: string;
  attendees: number;
  priority: BookingPriority;
  status: BookingStatus;
  hasDocument: boolean;
  uploadedDocumentName?: string;
  description?: string;
  conflictStatus?: "clear" | "conflict" | "maintenance";
  rejectionReason?: string;
}

export interface MaintenanceBlock {
  id: number;
  roomId: number;
  roomName: string;
  building?: string;
  startDateTime: string;
  endDateTime: string;
  reason: string;
  createdBy?: string;
}

export interface CalendarEvent {
  id: number;
  title: string;
  roomId?: number | string;
  roomName: string;
  building?: string;
  requesterName: string;
  requesterRole: UserRole;
  requesterId?: number | string;
  requesterEmail?: string;
  eventType: CalendarEventType;
  date: string;
  startTime: string;
  endTime: string;
  status: BookingStatus | "maintenance";
  priority: BookingPriority;
  conflictStatus: CalendarConflictStatus;
  description?: string;
  maintenanceWarning?: string;
  uploadedDocumentName?: string;
  hasDocument?: boolean;
  ownerRole?: UserRole;
}

export interface Notification {
  id: number;
  userId?: number | string;
  title: string;
  message: string;
  type: "success" | "warning" | "error" | "pending" | "info";
  targetType?: "booking" | "approval" | "calendar" | "maintenance" | "issue" | "admin_request" | "profile" | "system";
  targetId?: number | string;
  targetRoute?: string;
  createdAt: string;
  unread: boolean;
}

export type IssueStatus = "Open" | "Under Review" | "In Progress" | "Resolved" | "Rejected";
export type IssueCategory =
  | "Maintenance Problem"
  | "Schedule Conflict"
  | "Projector/Equipment Issue"
  | "AC/Fan/Light Problem"
  | "Cleanliness Issue"
  | "Furniture Problem"
  | "Capacity Problem"
  | "Other";
export type IssuePriority = "Low" | "Medium" | "High" | "Urgent";

export interface IssueComment {
  id: number;
  authorName: string;
  authorRole: UserRole | "admin";
  message: string;
  createdAt: string;
}

export interface ClassroomIssue {
  id: number;
  postedById?: number | string;
  postedByEmail?: string;
  title: string;
  roomName: string;
  category: IssueCategory;
  postedBy: string;
  userRole: UserRole;
  createdAt: string;
  description: string;
  status: IssueStatus;
  priority: IssuePriority;
  comments: IssueComment[];
  upvotes: number;
  relatedBookingId?: number;
  hasDocument?: boolean;
  uploadedPath?: string;
  attachment?: File | null;
  isAffectingBooking?: boolean;
  relatedBooking?: string;
  adminResponse?: string;
}

export interface RoomAvailabilityFilters {
  date?: string;
  startTime?: string;
  endTime?: string;
  minimumCapacity?: number;
  building?: string;
  roomType?: string;
  equipment?: string[];
}
