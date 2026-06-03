import { useState, useMemo, useEffect } from "react";
import {
  Flag, X, Search, Wrench, AlertTriangle, CheckCircle, Clock, Eye,
  Monitor, Wind, Trash2, Armchair, Users, HelpCircle, CalendarDays,
  MessageSquare, ThumbsUp, Send, ChevronDown, Plus, Shield,
} from "lucide-react";
import { addIssueComment, createMaintenanceBlockFromIssue, getIssues, updateIssueStatus } from "../services/classReserveService";
import type { ClassroomIssue } from "../types/classReserve";

const PLAYFAIR = { fontFamily: "'Playfair Display', serif" } as const;
const DM_SANS = { fontFamily: "'DM Sans', sans-serif" } as const;

type IssueCategory =
  | "Maintenance Problem"
  | "Schedule Conflict"
  | "Projector/Equipment Issue"
  | "AC/Fan/Light Problem"
  | "Cleanliness Issue"
  | "Furniture Problem"
  | "Capacity Problem"
  | "Other";

type IssueStatus = "Open" | "Under Review" | "In Progress" | "Resolved" | "Rejected";
type IssuePriority = "Low" | "Medium" | "High" | "Urgent";

type Comment = { id: number; author: string; role: string; time: string; text: string };

type Issue = {
  id: number;
  title: string;
  room: string;
  category: IssueCategory;
  description: string;
  postedBy: string;
  userRole: string;
  date: string;
  status: IssueStatus;
  priority: IssuePriority;
  comments: Comment[];
  upvotes: number;
  hasDocument: boolean;
  isAffectingBooking: boolean;
  relatedBooking?: string;
  adminResponse?: string;
};

const ROOMS = ["Room A-301", "Room A-302", "Lab C-105", "Lab C-106", "Auditorium B", "Room D-202", "Room E-101", "Room B-205"];
const STATUSES: IssueStatus[] = ["Open", "Under Review", "In Progress", "Resolved", "Rejected"];
const CATEGORIES: IssueCategory[] = [
  "Maintenance Problem", "Schedule Conflict", "Projector/Equipment Issue",
  "AC/Fan/Light Problem", "Cleanliness Issue", "Furniture Problem", "Capacity Problem", "Other",
];

const initialIssues: Issue[] = [
  {
    id: 1,
    title: "Projector not working in Room A-301",
    room: "Room A-301",
    category: "Projector/Equipment Issue",
    description: "The projector has been malfunctioning since Monday. It turns on but shows a blank screen. This is affecting multiple classes scheduled in this room.",
    postedBy: "Michael Chen",
    userRole: "Student",
    date: "Apr 1, 2026",
    status: "Under Review",
    priority: "High",
    upvotes: 14,
    hasDocument: false,
    isAffectingBooking: true,
    relatedBooking: "Study Group Session — Apr 2, 2026",
    adminResponse: "Maintenance team has been notified. Technician scheduled for April 2nd.",
    comments: [
      { id: 1, author: "Lisa Anderson", role: "Student", time: "2 hr ago", text: "Same issue happened during my tutorial session." },
      { id: 2, author: "Dr. Sarah Johnson", role: "Faculty", time: "1 hr ago", text: "Confirmed — projector not working during my lecture." },
    ],
  },
  {
    id: 2,
    title: "Double booking detected — Room D-202 on Apr 3",
    room: "Room D-202",
    category: "Schedule Conflict",
    description: "The CS Lecture and Tech Club Workshop are both scheduled for Room D-202 on April 3 from 2:00 PM – 3:00 PM.",
    postedBy: "Engineering Club",
    userRole: "Club",
    date: "Apr 1, 2026",
    status: "In Progress",
    priority: "Urgent",
    upvotes: 22,
    hasDocument: false,
    isAffectingBooking: true,
    relatedBooking: "Workshop — Tech Club, Apr 3",
    adminResponse: undefined,
    comments: [
      { id: 3, author: "Prof. David Lee", role: "Faculty", time: "3 hr ago", text: "Room was double-booked. Admin needs to intervene immediately." },
    ],
  },
  {
    id: 3,
    title: "AC not working in Lab C-105",
    room: "Lab C-105",
    category: "AC/Fan/Light Problem",
    description: "The air conditioning in Lab C-105 has not been working for the past two days. The room is extremely hot and uncomfortable.",
    postedBy: "Dance Club",
    userRole: "Club",
    date: "Mar 31, 2026",
    status: "Open",
    priority: "Medium",
    upvotes: 9,
    hasDocument: false,
    isAffectingBooking: false,
    adminResponse: undefined,
    comments: [
      { id: 4, author: "James Brown", role: "Student", time: "1 day ago", text: "AC not working during our session. Room was unbearable." },
    ],
  },
  {
    id: 4,
    title: "Broken chairs in Room B-205",
    room: "Room B-205",
    category: "Furniture Problem",
    description: "Several chairs are broken and unsafe. At least 5 chairs have damaged legs or missing back support.",
    postedBy: "Emma Wilson",
    userRole: "Student",
    date: "Mar 30, 2026",
    status: "Resolved",
    priority: "Medium",
    upvotes: 7,
    hasDocument: true,
    isAffectingBooking: false,
    adminResponse: "Broken chairs have been replaced. Room is now fully operational.",
    comments: [],
  },
  {
    id: 5,
    title: "Auditorium B capacity exceeded during Club Fair",
    room: "Auditorium B",
    category: "Capacity Problem",
    description: "The Club Fair event had far more attendees than the registered count of 150. Dangerously overcrowded.",
    postedBy: "Dr. Robert Smith",
    userRole: "Faculty",
    date: "Mar 28, 2026",
    status: "Resolved",
    priority: "High",
    upvotes: 11,
    hasDocument: false,
    isAffectingBooking: true,
    relatedBooking: "Club Fair — Student Council, Mar 28",
    adminResponse: "Capacity enforcement protocols have been updated.",
    comments: [],
  },
  {
    id: 6,
    title: "Lights flickering in Room E-101",
    room: "Room E-101",
    category: "AC/Fan/Light Problem",
    description: "The ceiling lights in Room E-101 have been flickering intermittently for a week. Causing eye strain and distraction.",
    postedBy: "Lisa Anderson",
    userRole: "Student",
    date: "Mar 29, 2026",
    status: "Open",
    priority: "Low",
    upvotes: 4,
    hasDocument: false,
    isAffectingBooking: false,
    adminResponse: undefined,
    comments: [],
  },
];

function categoryIcon(cat: IssueCategory) {
  switch (cat) {
    case "Maintenance Problem": return <Wrench className="w-3.5 h-3.5" />;
    case "Schedule Conflict": return <CalendarDays className="w-3.5 h-3.5" />;
    case "Projector/Equipment Issue": return <Monitor className="w-3.5 h-3.5" />;
    case "AC/Fan/Light Problem": return <Wind className="w-3.5 h-3.5" />;
    case "Cleanliness Issue": return <Trash2 className="w-3.5 h-3.5" />;
    case "Furniture Problem": return <Armchair className="w-3.5 h-3.5" />;
    case "Capacity Problem": return <Users className="w-3.5 h-3.5" />;
    default: return <HelpCircle className="w-3.5 h-3.5" />;
  }
}

function statusStyle(s: IssueStatus) {
  switch (s) {
    case "Open": return { bg: "rgba(184,134,11,0.12)", color: "#B8860B" };
    case "Under Review": return { bg: "rgba(94,101,123,0.12)", color: "#5E657B" };
    case "In Progress": return { bg: "rgba(137,29,26,0.1)", color: "#891D1A" };
    case "Resolved": return { bg: "rgba(59,110,74,0.12)", color: "#3B6E4A" };
    case "Rejected": return { bg: "rgba(33,7,6,0.08)", color: "#5E657B" };
  }
}

function priorityStyle(p: IssuePriority) {
  switch (p) {
    case "Low": return { bg: "rgba(94,101,123,0.1)", color: "#5E657B" };
    case "Medium": return { bg: "rgba(184,134,11,0.12)", color: "#B8860B" };
    case "High": return { bg: "rgba(137,29,26,0.12)", color: "#891D1A" };
    case "Urgent": return { bg: "#891D1A", color: "#fff" };
  }
}

function roleColor(role: string) {
  if (role === "Faculty") return "#891D1A";
  if (role === "Club") return "#5E657B";
  return "#B8860B";
}

const inputCls = "w-full px-3 py-2 rounded-lg border bg-white dark:bg-[#3A1210] dark:text-[#F1E6D2] outline-none focus:ring-2 focus:ring-[#891D1A]/30 text-sm";
const borderStyle = { borderColor: "rgba(137,29,26,0.2)" } as const;

function formatIssueDate(value: string) {
  if (!value) return "Just now";
  const date = new Date(value.replace(" ", "T"));
  return Number.isNaN(date.getTime()) ? value : date.toLocaleString([], { dateStyle: "medium", timeStyle: "short" });
}

function roleLabel(role: string) {
  if (role === "faculty") return "Faculty";
  if (role === "club") return "Club";
  if (role === "admin") return "Admin";
  return "Student";
}

function fromServiceIssue(issue: ClassroomIssue): Issue {
  return {
    id: issue.id,
    title: issue.title,
    room: issue.roomName,
    category: issue.category,
    description: issue.description,
    postedBy: issue.postedBy,
    userRole: roleLabel(issue.userRole),
    date: formatIssueDate(issue.createdAt),
    status: issue.status,
    priority: issue.priority,
    comments: issue.comments.map((comment) => ({
      id: comment.id,
      author: comment.authorName,
      role: roleLabel(comment.authorRole),
      time: formatIssueDate(comment.createdAt),
      text: comment.message,
    })),
    upvotes: issue.upvotes,
    hasDocument: Boolean(issue.hasDocument),
    isAffectingBooking: Boolean(issue.isAffectingBooking),
    relatedBooking: issue.relatedBooking,
    adminResponse: issue.adminResponse,
  };
}

export function IssueReports() {
  const [issues, setIssues] = useState<Issue[]>(initialIssues);
  const [searchQuery, setSearchQuery] = useState("");
  const [filterCategory, setFilterCategory] = useState("all");
  const [filterRoom, setFilterRoom] = useState("all");
  const [filterStatus, setFilterStatus] = useState("all");
  const [filterPriority, setFilterPriority] = useState("all");
  const [selectedIssue, setSelectedIssue] = useState<Issue | null>(null);
  const [adminResponseDraft, setAdminResponseDraft] = useState("");
  const [rejectReason, setRejectReason] = useState("");
  const [rejectModalId, setRejectModalId] = useState<number | null>(null);
  const [maintenanceModalIssue, setMaintenanceModalIssue] = useState<Issue | null>(null);
  const [maintenanceForm, setMaintenanceForm] = useState({ startDate: "", startTime: "08:00", endDate: "", endTime: "17:00", reason: "" });
  const [newComment, setNewComment] = useState("");

  useEffect(() => {
    let mounted = true;
    getIssues().then((items) => {
      if (mounted) setIssues(items.map(fromServiceIssue));
    });
    return () => { mounted = false; };
  }, []);

  const filtered = useMemo(() => {
    let list = [...issues];
    if (filterCategory !== "all") list = list.filter((i) => i.category === filterCategory);
    if (filterRoom !== "all") list = list.filter((i) => i.room === filterRoom);
    if (filterStatus !== "all") list = list.filter((i) => i.status === filterStatus);
    if (filterPriority !== "all") list = list.filter((i) => i.priority === filterPriority);
    if (searchQuery.trim()) {
      const q = searchQuery.toLowerCase();
      list = list.filter((i) =>
        i.title.toLowerCase().includes(q) ||
        i.room.toLowerCase().includes(q) ||
        i.category.toLowerCase().includes(q) ||
        i.postedBy.toLowerCase().includes(q)
      );
    }
    return list;
  }, [issues, filterCategory, filterRoom, filterStatus, filterPriority, searchQuery]);

  const updateStatus = async (id: number, status: IssueStatus, note?: string) => {
    await updateIssueStatus(id, status, note);
    setIssues((prev) => prev.map((i) => i.id === id ? { ...i, status } : i));
    setSelectedIssue((prev) => prev?.id === id ? { ...prev, status } : prev);
  };

  const submitAdminResponse = async (id: number) => {
    if (!adminResponseDraft.trim()) return;
    await updateIssueStatus(id, selectedIssue?.status || "Under Review", adminResponseDraft.trim());
    setIssues((prev) => prev.map((i) => i.id === id ? { ...i, adminResponse: adminResponseDraft.trim() } : i));
    setSelectedIssue((prev) => prev?.id === id ? { ...prev, adminResponse: adminResponseDraft.trim() } : prev);
    setAdminResponseDraft("");
  };

  const confirmReject = async () => {
    if (rejectModalId == null) return;
    await updateIssueStatus(rejectModalId, "Rejected", rejectReason.trim());
    setIssues((prev) => prev.map((i) => i.id === rejectModalId ? { ...i, status: "Rejected" } : i));
    setSelectedIssue((prev) => prev?.id === rejectModalId ? { ...prev, status: "Rejected" as IssueStatus } : prev);
    setRejectModalId(null);
    setRejectReason("");
  };

  const handleAddComment = async (issueId: number) => {
    if (!newComment.trim()) return;
    await addIssueComment(issueId, newComment.trim());
    const comment: Comment = { id: Date.now(), author: "Admin", role: "Admin", time: "Just now", text: newComment.trim() };
    setIssues((prev) => prev.map((i) => i.id === issueId ? { ...i, comments: [...i.comments, comment] } : i));
    setSelectedIssue((prev) => prev?.id === issueId ? { ...prev, comments: [...(prev.comments || []), comment] } : prev);
    setNewComment("");
  };

  const openMaintenanceModal = (issue: Issue) => {
    setMaintenanceModalIssue(issue);
    setMaintenanceForm({ startDate: "", startTime: "08:00", endDate: "", endTime: "17:00", reason: issue.description.slice(0, 80) });
  };

  const summaryStats = {
    open: issues.filter((i) => i.status === "Open").length,
    inProgress: issues.filter((i) => i.status === "In Progress" || i.status === "Under Review").length,
    resolved: issues.filter((i) => i.status === "Resolved").length,
    urgent: issues.filter((i) => i.priority === "Urgent").length,
  };

  const detailIssue = selectedIssue ? issues.find((i) => i.id === selectedIssue.id) || selectedIssue : null;

  return (
    <div className="space-y-5" style={DM_SANS}>
      {/* Header */}
      <div className="flex items-start justify-between flex-wrap gap-4">
        <div>
          <h1 style={{ ...PLAYFAIR, fontSize: 28, fontWeight: 600 }} className="text-foreground">
            Issue Reports
          </h1>
          <p className="text-sm mt-1" style={{ color: "#5E657B" }}>
            Review, manage, and resolve all reported classroom issues
          </p>
        </div>
        <div className="flex items-center gap-2 flex-wrap">
          {[
            { label: "Open", value: summaryStats.open, color: "#B8860B" },
            { label: "In Progress", value: summaryStats.inProgress, color: "#891D1A" },
            { label: "Resolved", value: summaryStats.resolved, color: "#3B6E4A" },
            { label: "Urgent", value: summaryStats.urgent, color: "#891D1A" },
          ].map((s) => (
            <div
              key={s.label}
              className="px-3 py-1.5 rounded-lg text-xs font-medium"
              style={{ background: s.color + "12", color: s.color, border: `1px solid ${s.color}22` }}
            >
              {s.value} {s.label}
            </div>
          ))}
        </div>
      </div>

      {/* Search + Filters */}
      <div className="bg-card rounded-xl p-4 shadow-sm space-y-3">
        <div className="relative">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4" style={{ color: "#891D1A" }} />
          <input
            type="text"
            placeholder="Search by title, room, category, reporter…"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="w-full pl-9 pr-4 py-2.5 rounded-lg border bg-white dark:bg-[#3A1210] text-sm outline-none focus:ring-2 focus:ring-[#891D1A]/20"
            style={{ borderColor: "rgba(137,29,26,0.15)" }}
          />
        </div>

        <div className="flex flex-wrap gap-3 items-end">
          {[
            { label: "Category", value: filterCategory, onChange: setFilterCategory, options: [["all", "All Categories"], ...CATEGORIES.map((c) => [c, c])] },
            { label: "Room", value: filterRoom, onChange: setFilterRoom, options: [["all", "All Rooms"], ...ROOMS.map((r) => [r, r])] },
            { label: "Status", value: filterStatus, onChange: setFilterStatus, options: [["all", "All Status"], ...STATUSES.map((s) => [s, s])] },
            { label: "Priority", value: filterPriority, onChange: setFilterPriority, options: [["all", "All Priorities"], ...["Low", "Medium", "High", "Urgent"].map((p) => [p, p])] },
          ].map((f) => (
            <div key={f.label} className="flex items-center gap-1.5">
              <label className="text-xs font-medium" style={{ color: "#5E657B" }}>{f.label}</label>
              <select
                value={f.value}
                onChange={(e) => f.onChange(e.target.value)}
                className="px-3 py-2 rounded-lg text-sm border bg-white dark:bg-[#3A1210] dark:text-[#F1E6D2] outline-none cursor-pointer"
                style={borderStyle}
              >
                {f.options.map(([v, l]) => <option key={v} value={v}>{l}</option>)}
              </select>
            </div>
          ))}
          <button
            onClick={() => { setFilterCategory("all"); setFilterRoom("all"); setFilterStatus("all"); setFilterPriority("all"); setSearchQuery(""); }}
            className="px-3 py-2 rounded-lg text-sm font-medium border transition-colors"
            style={{ borderColor: "rgba(137,29,26,0.25)", color: "#5E657B" }}
          >
            Reset
          </button>
        </div>
      </div>

      {/* Issues List */}
      <div className="space-y-3">
        {filtered.length === 0 && (
          <div className="py-14 text-center bg-card rounded-xl shadow-sm">
            <Flag className="w-10 h-10 mx-auto mb-2 opacity-20" style={{ color: "#891D1A" }} />
            <p className="text-sm" style={{ color: "#5E657B" }}>No issues match the current filters.</p>
          </div>
        )}

        {filtered.map((issue) => {
          const ss = statusStyle(issue.status);
          const ps = priorityStyle(issue.priority);
          const isConflict = issue.category === "Schedule Conflict";
          const isMaintenance = issue.category === "Maintenance Problem" || issue.category === "AC/Fan/Light Problem" || issue.category === "Furniture Problem";

          return (
            <div
              key={issue.id}
              className="bg-card rounded-xl shadow-sm overflow-hidden"
              style={{
                borderLeft: `3px solid ${
                  issue.priority === "Urgent" ? "#891D1A" :
                  issue.priority === "High" ? "#891D1A" :
                  issue.priority === "Medium" ? "#B8860B" : "#5E657B"
                }`,
              }}
            >
              <div className="p-5">
                <div className="flex items-start gap-4">
                  <div className="flex-1 min-w-0">
                    {/* Title row */}
                    <div className="flex items-start gap-3 flex-wrap mb-2">
                      <div className="flex-1 min-w-0">
                        <div className="flex items-center gap-2 flex-wrap mb-1">
                          <h3 className="font-semibold text-foreground" style={PLAYFAIR}>{issue.title}</h3>
                          {issue.priority === "Urgent" && (
                            <span className="text-xs px-2 py-0.5 rounded-full font-medium text-white animate-pulse" style={{ background: "#891D1A" }}>
                              URGENT
                            </span>
                          )}
                        </div>
                        <div className="flex items-center gap-2 flex-wrap text-xs" style={{ color: "#5E657B" }}>
                          <span className="font-medium text-foreground">{issue.room}</span>
                          <span>·</span>
                          <span className="flex items-center gap-0.5">{categoryIcon(issue.category)} {issue.category}</span>
                          <span>·</span>
                          <span>{issue.date}</span>
                        </div>
                      </div>
                      <div className="flex items-center gap-2 flex-wrap flex-shrink-0">
                        <span className="text-xs px-2 py-0.5 rounded-full font-medium" style={{ background: ps.bg, color: ps.color }}>
                          {issue.priority}
                        </span>
                        <span className="text-xs px-2 py-0.5 rounded-full font-medium" style={{ background: ss.bg, color: ss.color }}>
                          {issue.status}
                        </span>
                      </div>
                    </div>

                    <p className="text-sm line-clamp-2 mb-3" style={{ color: "#5E657B" }}>{issue.description}</p>

                    {/* Reporter info */}
                    <div className="flex items-center gap-2 mb-3">
                      <div className="w-6 h-6 rounded-full flex items-center justify-center text-white text-xs font-semibold" style={{ background: roleColor(issue.userRole) }}>
                        {issue.postedBy.charAt(0)}
                      </div>
                      <span className="text-xs text-foreground">{issue.postedBy}</span>
                      <span className="text-xs px-1.5 py-0.5 rounded-full" style={{ background: roleColor(issue.userRole) + "15", color: roleColor(issue.userRole) }}>
                        {issue.userRole}
                      </span>
                      <span className="flex items-center gap-0.5 text-xs ml-2" style={{ color: "#5E657B" }}>
                        <ThumbsUp className="w-3 h-3" /> {issue.upvotes}
                      </span>
                      <span className="flex items-center gap-0.5 text-xs" style={{ color: "#5E657B" }}>
                        <MessageSquare className="w-3 h-3" /> {issue.comments.length}
                      </span>
                      {issue.isAffectingBooking && (
                        <span className="flex items-center gap-0.5 text-xs" style={{ color: "#B8860B" }}>
                          <CalendarDays className="w-3 h-3" /> Booking affected
                        </span>
                      )}
                      {issue.adminResponse && (
                        <span className="flex items-center gap-0.5 text-xs" style={{ color: "#3B6E4A" }}>
                          <CheckCircle className="w-3 h-3" /> Responded
                        </span>
                      )}
                    </div>

                    {/* Conflict / Maintenance alert */}
                    {isConflict && (
                      <div className="flex items-center gap-1.5 mb-3 px-2.5 py-1.5 rounded-lg w-fit" style={{ background: "rgba(137,29,26,0.07)", border: "1px solid rgba(137,29,26,0.2)" }}>
                        <AlertTriangle className="w-3.5 h-3.5" style={{ color: "#891D1A" }} />
                        <span className="text-xs font-medium" style={{ color: "#891D1A" }}>Schedule conflict — Admin action required</span>
                      </div>
                    )}

                    {/* Action Buttons */}
                    <div className="flex flex-wrap gap-2">
                      <button
                        onClick={() => setSelectedIssue(issue)}
                        className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium border transition-colors"
                        style={{ borderColor: "rgba(94,101,123,0.3)", color: "#5E657B" }}
                      >
                        <Eye className="w-3.5 h-3.5" /> View Details
                      </button>

                      {/* Status Update */}
                      {issue.status !== "Resolved" && issue.status !== "Rejected" && (
                        <div className="relative group">
                          <button
                            className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium border transition-colors"
                            style={{ borderColor: "rgba(137,29,26,0.3)", color: "#891D1A" }}
                          >
                            <Clock className="w-3.5 h-3.5" /> Update Status <ChevronDown className="w-3 h-3" />
                          </button>
                          <div className="absolute left-0 top-full mt-1 bg-card border border-border rounded-lg shadow-lg z-10 min-w-[160px] hidden group-hover:block">
                            {(["Under Review", "In Progress", "Resolved"] as IssueStatus[])
                              .filter((s) => s !== issue.status)
                              .map((s) => {
                                const st = statusStyle(s);
                                return (
                                  <button
                                    key={s}
                                    onClick={() => updateStatus(issue.id, s)}
                                    className="w-full text-left px-3 py-2 text-xs hover:bg-[#891D1A]/5 transition-colors"
                                    style={{ color: st.color }}
                                  >
                                    {s}
                                  </button>
                                );
                              })}
                          </div>
                        </div>
                      )}

                      {/* Reject */}
                      {issue.status !== "Rejected" && issue.status !== "Resolved" && (
                        <button
                          onClick={() => { setRejectModalId(issue.id); setRejectReason(""); }}
                          className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium border transition-colors"
                          style={{ borderColor: "rgba(137,29,26,0.3)", color: "#891D1A" }}
                        >
                          <X className="w-3.5 h-3.5" /> Reject
                        </button>
                      )}

                      {/* Create Maintenance Block */}
                      {isMaintenance && issue.status !== "Rejected" && (
                        <button
                          onClick={() => openMaintenanceModal(issue)}
                          className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-white transition-colors"
                          style={{ background: "#B8860B" }}
                          onMouseEnter={(e) => (e.currentTarget.style.background = "#8c6500")}
                          onMouseLeave={(e) => (e.currentTarget.style.background = "#B8860B")}
                        >
                          <Wrench className="w-3.5 h-3.5" /> Block Room for Maintenance
                        </button>
                      )}

                      {/* Respond */}
                      <button
                        onClick={() => { setSelectedIssue(issue); setAdminResponseDraft(issue.adminResponse || ""); }}
                        className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-white transition-colors"
                        style={{ background: "#891D1A" }}
                        onMouseEnter={(e) => (e.currentTarget.style.background = "#210706")}
                        onMouseLeave={(e) => (e.currentTarget.style.background = "#891D1A")}
                      >
                        <Send className="w-3.5 h-3.5" /> Respond
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          );
        })}
      </div>

      {/* Reject Modal */}
      {rejectModalId != null && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4" style={{ background: "rgba(33,7,6,0.55)" }} onClick={() => setRejectModalId(null)}>
          <div className="bg-card rounded-2xl shadow-2xl w-full max-w-md p-6" onClick={(e) => e.stopPropagation()}>
            <h3 style={{ ...PLAYFAIR, fontSize: 18, fontWeight: 600 }} className="text-foreground mb-1">Reject Issue Report</h3>
            <p className="text-sm mb-4" style={{ color: "#5E657B" }}>Provide a reason for rejection.</p>
            <textarea
              value={rejectReason}
              onChange={(e) => setRejectReason(e.target.value)}
              placeholder="e.g. Issue not reproducible, duplicate report…"
              rows={3}
              className="w-full px-3 py-2.5 rounded-lg border bg-white dark:bg-[#3A1210] dark:text-[#F1E6D2] outline-none focus:ring-2 focus:ring-[#891D1A]/30 resize-none text-sm mb-4"
              style={borderStyle}
            />
            <div className="flex gap-3">
              <button onClick={() => setRejectModalId(null)} className="flex-1 py-2.5 rounded-lg text-sm font-medium border" style={{ borderColor: "rgba(137,29,26,0.3)", color: "#5E657B" }}>Cancel</button>
              <button onClick={confirmReject} disabled={!rejectReason.trim()} className="flex-1 py-2.5 rounded-lg text-sm font-medium text-white disabled:opacity-40 flex items-center justify-center gap-1" style={{ background: "#891D1A" }}>
                <X className="w-4 h-4" /> Confirm Rejection
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Maintenance Block Modal */}
      {maintenanceModalIssue && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4" style={{ background: "rgba(33,7,6,0.55)" }} onClick={() => setMaintenanceModalIssue(null)}>
          <div className="bg-card rounded-2xl shadow-2xl w-full max-w-md p-6" onClick={(e) => e.stopPropagation()}>
            <div className="flex items-center gap-2 mb-1">
              <Wrench className="w-5 h-5" style={{ color: "#B8860B" }} />
              <h3 style={{ ...PLAYFAIR, fontSize: 18, fontWeight: 600 }} className="text-foreground">Block Room for Maintenance</h3>
            </div>
            <p className="text-sm mb-4" style={{ color: "#5E657B" }}>Create a maintenance block for {maintenanceModalIssue.room}.</p>
            <div className="space-y-3">
              <div className="px-3 py-2 rounded-lg text-sm" style={{ background: "rgba(184,134,11,0.07)", border: "1px solid rgba(184,134,11,0.2)" }}>
                <span style={{ color: "#5E657B" }}>Room: </span>
                <span className="font-semibold text-foreground">{maintenanceModalIssue.room}</span>
              </div>
              <div className="grid grid-cols-2 gap-3">
                <div className="space-y-1.5">
                  <label className="text-sm font-medium" style={{ color: "#5E657B" }}>Start Date</label>
                  <input type="date" value={maintenanceForm.startDate} onChange={(e) => setMaintenanceForm((p) => ({ ...p, startDate: e.target.value }))} className={inputCls} style={borderStyle} />
                </div>
                <div className="space-y-1.5">
                  <label className="text-sm font-medium" style={{ color: "#5E657B" }}>Start Time</label>
                  <input type="time" value={maintenanceForm.startTime} onChange={(e) => setMaintenanceForm((p) => ({ ...p, startTime: e.target.value }))} className={inputCls} style={borderStyle} />
                </div>
                <div className="space-y-1.5">
                  <label className="text-sm font-medium" style={{ color: "#5E657B" }}>End Date</label>
                  <input type="date" value={maintenanceForm.endDate} onChange={(e) => setMaintenanceForm((p) => ({ ...p, endDate: e.target.value }))} className={inputCls} style={borderStyle} />
                </div>
                <div className="space-y-1.5">
                  <label className="text-sm font-medium" style={{ color: "#5E657B" }}>End Time</label>
                  <input type="time" value={maintenanceForm.endTime} onChange={(e) => setMaintenanceForm((p) => ({ ...p, endTime: e.target.value }))} className={inputCls} style={borderStyle} />
                </div>
              </div>
              <div className="space-y-1.5">
                <label className="text-sm font-medium" style={{ color: "#5E657B" }}>Reason</label>
                <input type="text" value={maintenanceForm.reason} onChange={(e) => setMaintenanceForm((p) => ({ ...p, reason: e.target.value }))} className={inputCls} style={borderStyle} />
              </div>
            </div>
            <div className="flex gap-3 mt-5">
              <button onClick={() => setMaintenanceModalIssue(null)} className="flex-1 py-2.5 rounded-lg text-sm font-medium border" style={{ borderColor: "rgba(137,29,26,0.3)", color: "#5E657B" }}>Cancel</button>
              <button
                onClick={async () => {
                  if (!maintenanceForm.startDate || !maintenanceForm.startTime || !maintenanceForm.endDate || !maintenanceForm.endTime) {
                    alert("Please select start and end date/time.");
                    return;
                  }
                  await createMaintenanceBlockFromIssue(maintenanceModalIssue.id, {
                    roomName: maintenanceModalIssue.room,
                    startDateTime: `${maintenanceForm.startDate} ${maintenanceForm.startTime}:00`,
                    endDateTime: `${maintenanceForm.endDate} ${maintenanceForm.endTime}:00`,
                    reason: maintenanceForm.reason,
                  });
                  await updateStatus(maintenanceModalIssue.id, "In Progress");
                  setMaintenanceModalIssue(null);
                  alert(`Maintenance block created for ${maintenanceModalIssue.room}`);
                }}
                className="flex-1 py-2.5 rounded-lg text-sm font-medium text-white flex items-center justify-center gap-2"
                style={{ background: "#B8860B" }}
              >
                <Wrench className="w-4 h-4" /> Confirm Block Room
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Detail Side Panel */}
      {detailIssue && (
        <div className="fixed inset-0 z-40" style={{ background: "rgba(33,7,6,0.4)" }} onClick={() => setSelectedIssue(null)}>
          <div
            className="absolute right-0 top-0 h-full w-[440px] bg-card shadow-2xl flex flex-col"
            style={{ borderLeft: "1px solid rgba(137,29,26,0.15)" }}
            onClick={(e) => e.stopPropagation()}
          >
            <div className="flex items-center justify-between px-5 py-4 border-b border-border flex-shrink-0">
              <div className="flex items-center gap-2">
                {categoryIcon(detailIssue.category)}
                <span className="text-sm font-semibold text-foreground">Issue Details</span>
              </div>
              <button onClick={() => setSelectedIssue(null)} className="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-[#891D1A]/10" style={{ color: "#5E657B" }}>
                <X className="w-4 h-4" />
              </button>
            </div>

            <div className="flex-1 overflow-y-auto">
              <div className="p-5 space-y-4">
                <div>
                  <h3 className="font-semibold text-foreground mb-2" style={{ ...PLAYFAIR, fontSize: 15 }}>{detailIssue.title}</h3>
                  <div className="flex flex-wrap gap-2">
                    {(() => {
                      const ss = statusStyle(detailIssue.status);
                      const ps = priorityStyle(detailIssue.priority);
                      return (
                        <>
                          <span className="text-xs px-2 py-0.5 rounded-full font-medium" style={{ background: ss.bg, color: ss.color }}>{detailIssue.status}</span>
                          <span className="text-xs px-2 py-0.5 rounded-full font-medium" style={{ background: ps.bg, color: ps.color }}>{detailIssue.priority} Priority</span>
                        </>
                      );
                    })()}
                  </div>
                </div>

                <div className="grid grid-cols-2 gap-3 text-sm">
                  {[
                    { label: "ROOM", value: detailIssue.room },
                    { label: "CATEGORY", value: detailIssue.category },
                    { label: "REPORTED BY", value: `${detailIssue.postedBy} (${detailIssue.userRole})` },
                    { label: "DATE", value: detailIssue.date },
                    { label: "UPVOTES", value: String(detailIssue.upvotes) },
                    { label: "COMMENTS", value: String(detailIssue.comments.length) },
                  ].map((row) => (
                    <div key={row.label}>
                      <p className="text-xs font-semibold mb-0.5" style={{ color: "#5E657B" }}>{row.label}</p>
                      <p className="text-sm text-foreground">{row.value}</p>
                    </div>
                  ))}
                </div>

                <div>
                  <p className="text-xs font-semibold mb-1" style={{ color: "#5E657B" }}>DESCRIPTION</p>
                  <p className="text-sm text-foreground leading-relaxed">{detailIssue.description}</p>
                </div>

                {detailIssue.isAffectingBooking && detailIssue.relatedBooking && (
                  <div className="flex items-center gap-2 p-3 rounded-lg" style={{ background: "rgba(184,134,11,0.07)", border: "1px solid rgba(184,134,11,0.2)" }}>
                    <CalendarDays className="w-4 h-4 flex-shrink-0" style={{ color: "#B8860B" }} />
                    <div>
                      <p className="text-xs font-semibold" style={{ color: "#B8860B" }}>Related Booking</p>
                      <p className="text-sm text-foreground">{detailIssue.relatedBooking}</p>
                    </div>
                  </div>
                )}

                {detailIssue.category === "Schedule Conflict" && (
                  <div className="p-3 rounded-lg" style={{ background: "rgba(137,29,26,0.06)", border: "1px solid rgba(137,29,26,0.2)" }}>
                    <div className="flex items-center gap-2 mb-1">
                      <AlertTriangle className="w-4 h-4" style={{ color: "#891D1A" }} />
                      <p className="text-sm font-semibold" style={{ color: "#891D1A" }}>Conflicting Booking Warning</p>
                    </div>
                    <p className="text-xs" style={{ color: "#5E657B" }}>This report involves a schedule conflict. Review the related booking and take corrective action.</p>
                  </div>
                )}

                {/* Admin Status Update */}
                <div>
                  <p className="text-xs font-semibold mb-2" style={{ color: "#5E657B" }}>UPDATE STATUS</p>
                  <div className="flex flex-wrap gap-2">
                    {(["Under Review", "In Progress", "Resolved"] as IssueStatus[]).map((s) => {
                      const st = statusStyle(s);
                      return (
                        <button
                          key={s}
                          onClick={() => updateStatus(detailIssue.id, s)}
                          className="px-3 py-1.5 rounded-lg text-xs font-medium border transition-colors"
                          style={
                            detailIssue.status === s
                              ? { background: st.bg, color: st.color, borderColor: st.color }
                              : { borderColor: "rgba(94,101,123,0.25)", color: "#5E657B" }
                          }
                        >
                          {s}
                        </button>
                      );
                    })}
                  </div>
                </div>

                {/* Admin Response */}
                <div>
                  <p className="text-xs font-semibold mb-2" style={{ color: "#5E657B" }}>ADMIN RESPONSE</p>
                  {detailIssue.adminResponse && (
                    <div className="p-3 rounded-lg mb-2" style={{ background: "rgba(59,110,74,0.06)", border: "1px solid rgba(59,110,74,0.2)" }}>
                      <p className="text-sm text-foreground">{detailIssue.adminResponse}</p>
                    </div>
                  )}
                  <div className="flex gap-2">
                    <textarea
                      value={adminResponseDraft}
                      onChange={(e) => setAdminResponseDraft(e.target.value)}
                      placeholder="Write an admin response…"
                      rows={2}
                      className="flex-1 px-3 py-2 rounded-lg border bg-white dark:bg-[#3A1210] dark:text-[#F1E6D2] outline-none text-sm resize-none"
                      style={borderStyle}
                    />
                    <button
                      onClick={() => submitAdminResponse(detailIssue.id)}
                      className="px-3 rounded-lg text-white flex items-start pt-2"
                      style={{ background: "#891D1A" }}
                    >
                      <Send className="w-4 h-4" />
                    </button>
                  </div>
                </div>

                {/* Comments */}
                <div>
                  <p className="text-xs font-semibold mb-3" style={{ color: "#5E657B" }}>COMMENTS ({detailIssue.comments.length})</p>
                  <div className="space-y-3">
                    {detailIssue.comments.map((c) => (
                      <div key={c.id} className="flex gap-2.5">
                        <div className="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-semibold flex-shrink-0" style={{ background: c.role === "Admin" ? "#210706" : roleColor(c.role) }}>
                          {c.author.charAt(0)}
                        </div>
                        <div className="flex-1 min-w-0">
                          <div className="flex items-center gap-2 flex-wrap">
                            <span className="text-xs font-semibold text-foreground">{c.author}</span>
                            <span className="text-xs px-1.5 py-0.5 rounded-full" style={{ background: "rgba(94,101,123,0.1)", color: "#5E657B" }}>{c.role}</span>
                            <span className="text-xs" style={{ color: "#5E657B" }}>{c.time}</span>
                          </div>
                          <p className="text-sm mt-0.5 text-foreground">{c.text}</p>
                        </div>
                      </div>
                    ))}
                  </div>
                  <div className="flex gap-2 mt-3">
                    <input
                      type="text"
                      placeholder="Add admin comment…"
                      value={newComment}
                      onChange={(e) => setNewComment(e.target.value)}
                      onKeyDown={(e) => { if (e.key === "Enter") handleAddComment(detailIssue.id); }}
                      className="flex-1 px-3 py-2 rounded-lg border bg-white dark:bg-[#3A1210] dark:text-[#F1E6D2] outline-none text-sm"
                      style={borderStyle}
                    />
                    <button onClick={() => handleAddComment(detailIssue.id)} className="px-3 py-2 rounded-lg text-white text-sm" style={{ background: "#891D1A" }}>
                      <Send className="w-4 h-4" />
                    </button>
                  </div>
                </div>

                {/* Quick Admin Actions */}
                <div className="pt-2 border-t border-border space-y-2">
                  <p className="text-xs font-semibold" style={{ color: "#5E657B" }}>QUICK ACTIONS</p>
                  <div className="flex flex-wrap gap-2">
                    {(detailIssue.category !== "Schedule Conflict") && (
                      <button
                        onClick={() => { setSelectedIssue(null); openMaintenanceModal(detailIssue); }}
                        className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-white"
                        style={{ background: "#B8860B" }}
                      >
                        <Wrench className="w-3.5 h-3.5" /> Block Room for Maintenance
                      </button>
                    )}
                    {detailIssue.status !== "Rejected" && (
                      <button
                        onClick={() => { setSelectedIssue(null); setRejectModalId(detailIssue.id); }}
                        className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium border"
                        style={{ borderColor: "rgba(137,29,26,0.3)", color: "#891D1A" }}
                      >
                        <X className="w-3.5 h-3.5" /> Reject Issue
                      </button>
                    )}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
