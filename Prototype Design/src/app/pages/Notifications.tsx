import { useEffect, useState } from "react";
import { useNavigate } from "react-router";
import { CheckCircle, XCircle, Clock, AlertTriangle, Bell, Info } from "lucide-react";
import { getNotifications, markAllNotificationsRead, markNotificationRead } from "../services/classReserveService";
import type { Notification as AppNotification } from "../types/classReserve";
import { useAuth } from "../context/AuthContext";

const PLAYFAIR = { fontFamily: "'Playfair Display', serif" } as const;
const DM_SANS = { fontFamily: "'DM Sans', sans-serif" } as const;

type NotifType = "success" | "error" | "warning" | "pending" | "info";

type Notif = {
  id: number;
  type: NotifType;
  title: string;
  message: string;
  time: string;
  unread: boolean;
  group: "today" | "week" | "earlier";
  targetType?: AppNotification["targetType"];
  targetId?: AppNotification["targetId"];
  targetRoute?: string;
};

const initialNotifs: Notif[] = [
  { id: 1, type: "success", title: "Booking Approved", message: "Your booking for Room A-301 on Apr 1, 2026 has been approved.", time: "5 min ago", unread: true, group: "today" },
  { id: 2, type: "warning", title: "Conflict Detected", message: "Room D-202 has a scheduling conflict for Apr 3, 2026 at 2:00 PM.", time: "1 hr ago", unread: true, group: "today" },
  { id: 3, type: "pending", title: "Awaiting Approval", message: "Study Group Session booking is pending approval.", time: "2 hr ago", unread: false, group: "today" },
  { id: 4, type: "error", title: "Booking Rejected", message: "Your booking request for Room E-101 has been rejected due to maintenance.", time: "Yesterday", unread: false, group: "week" },
  { id: 5, type: "info", title: "Room Maintenance Scheduled", message: "Room D-202 will be under maintenance from Apr 10–12, 2026.", time: "2 days ago", unread: false, group: "week" },
  { id: 6, type: "success", title: "New Room Available", message: "Lab C-107 is now available for booking.", time: "3 days ago", unread: false, group: "week" },
  { id: 7, type: "info", title: "System Update", message: "The booking system will undergo maintenance on Apr 15, 2026.", time: "Last week", unread: false, group: "earlier" },
  { id: 8, type: "success", title: "Account Activated", message: "Your ClassReserve account has been verified and activated.", time: "2 weeks ago", unread: false, group: "earlier" },
];

function iconFor(type: NotifType, color: string) {
  const cls = "w-5 h-5";
  switch (type) {
    case "success": return <CheckCircle className={cls} style={{ color }} />;
    case "error": return <XCircle className={cls} style={{ color }} />;
    case "warning": return <AlertTriangle className={cls} style={{ color }} />;
    case "pending": return <Clock className={cls} style={{ color }} />;
    case "info": return <Info className={cls} style={{ color }} />;
    default: return <Bell className={cls} style={{ color }} />;
  }
}

function typeColor(type: NotifType) {
  switch (type) {
    case "success": return "#3B6E4A";
    case "error": return "#891D1A";
    case "warning": return "#B8860B";
    case "pending": return "#5E657B";
    case "info": return "#5E657B";
    default: return "#5E657B";
  }
}

const GROUPS: { key: Notif["group"]; label: string }[] = [
  { key: "today", label: "Today" },
  { key: "week", label: "This Week" },
  { key: "earlier", label: "Earlier" },
];

function groupForDate(value: string): Notif["group"] {
  const date = new Date(value.replace(" ", "T"));
  if (Number.isNaN(date.getTime())) return "earlier";
  const now = new Date();
  const diffDays = (now.getTime() - date.getTime()) / (1000 * 60 * 60 * 24);
  if (diffDays < 1) return "today";
  if (diffDays < 7) return "week";
  return "earlier";
}

function timeLabel(value: string) {
  const date = new Date(value.replace(" ", "T"));
  if (Number.isNaN(date.getTime())) return value || "Recently";
  const diffMs = Date.now() - date.getTime();
  const diffMinutes = Math.floor(diffMs / (1000 * 60));
  if (diffMinutes < 1) return "Just now";
  if (diffMinutes < 60) return `${diffMinutes} min ago`;
  const diffHours = Math.floor(diffMinutes / 60);
  if (diffHours < 24) return `${diffHours} hr ago`;
  const diffDays = Math.floor(diffHours / 24);
  if (diffDays === 1) return "Yesterday";
  if (diffDays < 7) return `${diffDays} days ago`;
  return date.toLocaleDateString();
}

function fromServiceNotification(notification: AppNotification): Notif {
  return {
    id: notification.id,
    type: notification.type === "warning" || notification.type === "error" || notification.type === "success" || notification.type === "pending" || notification.type === "info" ? notification.type : "pending",
    title: notification.title,
    message: notification.message,
    time: timeLabel(notification.createdAt),
    unread: notification.unread,
    group: groupForDate(notification.createdAt),
    targetType: notification.targetType,
    targetId: notification.targetId,
    targetRoute: notification.targetRoute,
  };
}

export function Notifications() {
  const { user } = useAuth();
  const navigate = useNavigate();
  const [notifs, setNotifs] = useState<Notif[]>([]);
  const [filter, setFilter] = useState<"all" | "unread" | NotifType>("all");

  useEffect(() => {
    let mounted = true;
    getNotifications({ role: user?.role, userId: user?.id, email: user?.email }).then((items) => {
      if (mounted) setNotifs(items.map(fromServiceNotification));
    });
    return () => { mounted = false; };
  }, [user?.role, user?.id, user?.email]);

  const unreadCount = notifs.filter((n) => n.unread).length;
  const visibleNotifs = notifs.filter((n) => {
    if (filter === "all") return true;
    if (filter === "unread") return n.unread;
    return n.type === filter;
  });

  const markAllRead = async () => {
    await markAllNotificationsRead();
    setNotifs((prev) => prev.map((n) => ({ ...n, unread: false })));
    window.dispatchEvent(new Event("classreserve:notifications-updated"));
  };
  const markRead = async (id: number) => {
    await markNotificationRead(id);
    setNotifs((prev) => prev.map((n) => (n.id === id ? { ...n, unread: false } : n)));
    window.dispatchEvent(new Event("classreserve:notifications-updated"));
  };

  const roleBase = `/${user?.role || "student"}`;

  const inferTargetType = (notif: Notif): AppNotification["targetType"] => {
    const text = `${notif.title} ${notif.message}`.toLowerCase();
    if (notif.targetType) return notif.targetType;
    if (text.includes("approval") || text.includes("waiting for approval") || text.includes("new booking request")) return user?.role === "admin" || user?.role === "faculty" ? "approval" : "booking";
    if (text.includes("booking")) return "booking";
    if (text.includes("conflict") || text.includes("calendar")) return "calendar";
    if (text.includes("maintenance")) return "maintenance";
    if (text.includes("issue") || text.includes("report") || text.includes("comment")) return user?.role === "admin" ? "admin_request" : "issue";
    if (text.includes("account") || text.includes("profile")) return "profile";
    return "system";
  };

  const targetRouteFor = (notif: Notif) => {
    if (notif.targetRoute) return notif.targetRoute;
    const targetType = inferTargetType(notif);
    const idParam = notif.targetId ? String(notif.targetId) : "";
    const query = (key: string) => idParam ? `?${key}=${encodeURIComponent(idParam)}` : "";

    if (targetType === "booking") return `${roleBase}/bookings${query("bookingId")}`;
    if (targetType === "approval" || targetType === "admin_request") return user?.role === "admin" ? `/admin/approvals${query("bookingId")}` : `${roleBase}/approvals${query("bookingId")}`;
    if (targetType === "calendar" || targetType === "maintenance") return `${roleBase}/calendar`;
    if (targetType === "issue") return user?.role === "admin" ? `/admin/issue-reports${query("issueId")}` : `${roleBase}/forum${query("issueId")}`;
    if (targetType === "profile") return `${roleBase}/profile`;
    return `${roleBase}/notifications`;
  };

  const handleNotificationClick = async (notif: Notif) => {
    await markRead(notif.id);
    navigate(targetRouteFor(notif));
  };

  return (
    <div className="space-y-5" style={DM_SANS}>
      <div className="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h1 style={{ ...PLAYFAIR, fontSize: 28, fontWeight: 600 }} className="text-foreground">
            Notifications
          </h1>
          <p className="text-sm mt-1" style={{ color: "#5E657B" }}>
            Stay updated with booking alerts and system messages
          </p>
        </div>
        {unreadCount > 0 && (
          <button
            onClick={markAllRead}
            className="text-sm font-medium border px-4 py-2 rounded-full transition-colors hover:bg-[#891D1A]/10"
            style={{ color: "#891D1A", borderColor: "rgba(137,29,26,0.3)" }}
          >
            Mark all as read ({unreadCount})
          </button>
        )}
      </div>

      <div className="flex items-center gap-2 flex-wrap">
        {(["all", "unread", "success", "warning", "error", "pending", "info"] as const).map((item) => (
          <button
            key={item}
            onClick={() => setFilter(item)}
            className="px-3 py-1.5 rounded-full text-xs font-medium border capitalize"
            style={filter === item ? { background: "#891D1A", borderColor: "#891D1A", color: "#fff" } : { borderColor: "rgba(137,29,26,0.25)", color: "#5E657B" }}
          >
            {item}
          </button>
        ))}
      </div>

      {visibleNotifs.length === 0 && (
        <div className="py-14 text-center bg-card rounded-xl shadow-sm">
          <Bell className="w-10 h-10 mx-auto mb-2 opacity-20" style={{ color: "#891D1A" }} />
          <p className="text-sm" style={{ color: "#5E657B" }}>{notifs.length === 0 ? "No notifications yet." : "No notifications match this filter."}</p>
        </div>
      )}

      <div className="space-y-6">
        {GROUPS.map((group) => {
          const items = visibleNotifs.filter((n) => n.group === group.key);
          if (items.length === 0) return null;
          return (
            <div key={group.key}>
              <h3 className="text-xs font-semibold mb-3" style={{ color: "#5E657B" }}>
                {group.label.toUpperCase()}
              </h3>
              <div className="bg-card rounded-xl shadow-sm overflow-hidden divide-y divide-border">
                {items.map((notif) => {
                  const color = typeColor(notif.type);
                  return (
                    <div
                      key={notif.id}
                      className="flex gap-4 px-5 py-4 cursor-pointer transition-colors hover:bg-[#891D1A]/4 relative"
                      style={notif.unread ? { background: "rgba(241,230,210,0.4)" } : undefined}
                      onClick={() => handleNotificationClick(notif)}
                    >
                      {notif.unread && (
                        <div
                          className="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-8 rounded-r"
                          style={{ background: "#891D1A" }}
                        />
                      )}
                      <div
                        className="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0"
                        style={{ background: color + "14" }}
                      >
                        {iconFor(notif.type, color)}
                      </div>
                      <div className="flex-1 min-w-0">
                        <div className="flex items-start justify-between gap-2">
                          <p className="text-sm font-semibold text-foreground">{notif.title}</p>
                          <span className="text-xs flex-shrink-0" style={{ color: "#5E657B" }}>
                            {notif.time}
                          </span>
                        </div>
                        <p className="text-sm mt-1" style={{ color: "#5E657B" }}>
                          {notif.message}
                        </p>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}
