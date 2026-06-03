export type UserRole = "student" | "club" | "faculty" | "admin";

export type BookingStatus = "pending" | "approved" | "rejected" | "cancelled";
export type BookingPriority = "standard" | "medium" | "high";

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
  conflictStatus?: "clear" | "conflict" | "maintenance";
  rejectionReason?: string;
}

export interface MaintenanceBlock {
  id: number;
  roomId: number;
  roomName: string;
  startDateTime: string;
  endDateTime: string;
  reason: string;
}

export interface CalendarEvent {
  id: number;
  title: string;
  roomName: string;
  date: string;
  startTime: string;
  endTime: string;
  status: BookingStatus | "maintenance";
  ownerRole?: UserRole;
}

export interface Notification {
  id: number;
  title: string;
  message: string;
  type: "success" | "warning" | "error" | "info";
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
export type IssuePriority = "Low" | "Medium" | "High" | "Critical";

export interface IssueComment {
  id: number;
  authorName: string;
  authorRole: UserRole | "admin";
  message: string;
  createdAt: string;
}

export interface ClassroomIssue {
  id: number;
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
