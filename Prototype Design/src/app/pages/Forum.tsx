import { useState, useMemo, useEffect } from "react";
import { useSearchParams } from "react-router";
import {
  MessageSquare, ThumbsUp, Plus, X, Search, Upload, AlertTriangle,
  Wrench, Monitor, Wind, Trash2, Armchair, Users, HelpCircle,
  CheckCircle, Clock, Eye, Send, ChevronDown, FileText, CalendarDays,
} from "lucide-react";
import { useAuth } from "../context/AuthContext";
import { addIssueComment, createIssue, getIssues, upvoteIssue } from "../services/classReserveService";
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

type Comment = {
  id: number;
  author: string;
  role: string;
  time: string;
  text: string;
};

type Issue = {
  id: number;
  postedById?: number | string;
  postedByEmail?: string;
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

const CATEGORIES: IssueCategory[] = [
  "Maintenance Problem",
  "Schedule Conflict",
  "Projector/Equipment Issue",
  "AC/Fan/Light Problem",
  "Cleanliness Issue",
  "Furniture Problem",
  "Capacity Problem",
  "Other",
];

const ROOMS = ["Room A-301", "Room A-302", "Lab C-105", "Lab C-106", "Auditorium B", "Room D-202", "Room E-101", "Room B-205"];

const initialIssues: Issue[] = [
  {
    id: 1,
    title: "Projector not working in Room A-301",
    room: "Room A-301",
    category: "Projector/Equipment Issue",
    description: "The projector has been malfunctioning since Monday. It turns on but shows a blank screen. This is affecting multiple classes scheduled in this room.",
    postedBy: "Michael Chen",
    userRole: "Student",
    date: "Apr 1, 2026 · 10:30 AM",
    status: "Under Review",
    priority: "High",
    upvotes: 14,
    hasDocument: false,
    isAffectingBooking: true,
    relatedBooking: "Study Group Session — Apr 2, 2026",
    adminResponse: "Maintenance team has been notified. Technician scheduled for April 2nd.",
    comments: [
      { id: 1, author: "Lisa Anderson", role: "Student", time: "2 hr ago", text: "Same issue happened during my tutorial session yesterday." },
      { id: 2, author: "Dr. Sarah Johnson", role: "Faculty", time: "1 hr ago", text: "Confirmed — the projector was not working during my 10am lecture as well." },
    ],
  },
  {
    id: 2,
    title: "Double booking detected — Room D-202 on Apr 3",
    room: "Room D-202",
    category: "Schedule Conflict",
    description: "The CS Lecture and Tech Club Workshop are both scheduled for Room D-202 on April 3 from 2:00 PM – 3:00 PM. This needs immediate resolution.",
    postedBy: "Engineering Club",
    userRole: "Club",
    date: "Apr 1, 2026 · 2:00 PM",
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
    description: "The air conditioning in Lab C-105 has not been working for the past two days. The room is extremely hot and uncomfortable for students.",
    postedBy: "Dance Club",
    userRole: "Club",
    date: "Mar 31, 2026 · 4:00 PM",
    status: "Open",
    priority: "Medium",
    upvotes: 9,
    hasDocument: false,
    isAffectingBooking: false,
    adminResponse: undefined,
    comments: [
      { id: 4, author: "James Brown", role: "Student", time: "1 day ago", text: "AC was not working during our session too. Room was unbearable." },
    ],
  },
  {
    id: 4,
    title: "Broken chairs in Room B-205",
    room: "Room B-205",
    category: "Furniture Problem",
    description: "Several chairs in Room B-205 are broken and unsafe. At least 5 chairs have damaged legs or missing back support. Should be replaced before more people get hurt.",
    postedBy: "Emma Wilson",
    userRole: "Student",
    date: "Mar 30, 2026 · 9:00 AM",
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
    description: "The Club Fair event had far more attendees than the registered count of 150. The auditorium was dangerously overcrowded.",
    postedBy: "Dr. Robert Smith",
    userRole: "Faculty",
    date: "Mar 28, 2026 · 3:00 PM",
    status: "Resolved",
    priority: "High",
    upvotes: 11,
    hasDocument: false,
    isAffectingBooking: true,
    relatedBooking: "Club Fair — Student Council, Mar 28",
    adminResponse: "Issue noted. Capacity enforcement protocols have been updated.",
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
    case "Open": return { bg: "rgba(184,134,11,0.12)", color: "#B8860B", label: "Open" };
    case "Under Review": return { bg: "rgba(94,101,123,0.12)", color: "#5E657B", label: "Under Review" };
    case "In Progress": return { bg: "rgba(137,29,26,0.1)", color: "#891D1A", label: "In Progress" };
    case "Resolved": return { bg: "rgba(59,110,74,0.12)", color: "#3B6E4A", label: "Resolved" };
    case "Rejected": return { bg: "rgba(33,7,6,0.08)", color: "#5E657B", label: "Rejected" };
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

type FilterKey = "all" | "my-posts" | "maintenance" | "conflict" | "equipment" | "open" | "resolved";
const FILTERS: { key: FilterKey; label: string }[] = [
  { key: "all", label: "All Issues" },
  { key: "my-posts", label: "My Posts" },
  { key: "maintenance", label: "Maintenance" },
  { key: "conflict", label: "Schedule Conflict" },
  { key: "equipment", label: "Equipment" },
  { key: "open", label: "Open" },
  { key: "resolved", label: "Resolved" },
];

const inputCls = "w-full px-3 py-2.5 rounded-lg border bg-white dark:bg-[#3A1210] dark:text-[#F1E6D2] outline-none focus:ring-2 focus:ring-[#891D1A]/30 text-sm";
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
    postedById: issue.postedById,
    postedByEmail: issue.postedByEmail,
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

export function Forum() {
  const { user } = useAuth();
  const [searchParams] = useSearchParams();

  const [issues, setIssues] = useState<Issue[]>(initialIssues);
  const [activeFilter, setActiveFilter] = useState<FilterKey>("all");
  const [searchQuery, setSearchQuery] = useState("");
  const [createOpen, setCreateOpen] = useState(false);
  const [selectedIssue, setSelectedIssue] = useState<Issue | null>(null);
  const [newComment, setNewComment] = useState("");
  const [upvoted, setUpvoted] = useState<Set<number>>(new Set());

  useEffect(() => {
    let mounted = true;
    getIssues().then((items) => {
      if (mounted) setIssues(items.map(fromServiceIssue));
    });
    return () => { mounted = false; };
  }, []);

  useEffect(() => {
    const issueId = Number(searchParams.get("issueId"));
    if (!issueId || issues.length === 0) return;
    const match = issues.find((issue) => issue.id === issueId);
    if (match) {
      setSelectedIssue(match);
    }
  }, [searchParams, issues]);

  // Create form state
  const [form, setForm] = useState({
    title: "",
    room: "",
    category: "" as IssueCategory | "",
    description: "",
    date: "",
    time: "",
    hasDocument: false,
    fileName: "",
    attachment: null as File | null,
    isAffectingBooking: false,
    relatedBooking: "",
  });

  const filteredIssues = useMemo(() => {
    let list = [...issues];
    // filter
    switch (activeFilter) {
      case "my-posts":
        list = list.filter((i) =>
          Boolean(
            (i.postedById && user?.id && String(i.postedById) === String(user.id)) ||
            (i.postedByEmail && user?.email && i.postedByEmail.toLowerCase() === user.email.toLowerCase())
          )
        );
        break;
      case "maintenance": list = list.filter((i) => i.category === "Maintenance Problem"); break;
      case "conflict": list = list.filter((i) => i.category === "Schedule Conflict"); break;
      case "equipment": list = list.filter((i) => i.category === "Projector/Equipment Issue"); break;
      case "open": list = list.filter((i) => i.status === "Open" || i.status === "Under Review" || i.status === "In Progress"); break;
      case "resolved": list = list.filter((i) => i.status === "Resolved"); break;
    }
    // search
    if (searchQuery.trim()) {
      const q = searchQuery.toLowerCase();
      list = list.filter((i) =>
        i.title.toLowerCase().includes(q) ||
        i.room.toLowerCase().includes(q) ||
        i.category.toLowerCase().includes(q) ||
        i.status.toLowerCase().includes(q)
      );
    }
    return list;
  }, [issues, activeFilter, searchQuery, user]);

  const handleUpvote = (id: number) => {
    void upvoteIssue(id);
    setUpvoted((prev) => {
      const next = new Set(prev);
      if (next.has(id)) { next.delete(id); setIssues((is) => is.map((i) => i.id === id ? { ...i, upvotes: i.upvotes - 1 } : i)); }
      else { next.add(id); setIssues((is) => is.map((i) => i.id === id ? { ...i, upvotes: i.upvotes + 1 } : i)); }
      return next;
    });
  };

  const handleAddComment = async (issueId: number) => {
    if (!newComment.trim()) return;
    await addIssueComment(issueId, newComment.trim());
    const comment: Comment = {
      id: Date.now(),
      author: user?.name || "Anonymous",
      role: user?.role === "faculty" ? "Faculty" : "Student",
      time: "Just now",
      text: newComment.trim(),
    };
    setIssues((prev) => prev.map((i) => i.id === issueId ? { ...i, comments: [...i.comments, comment] } : i));
    setSelectedIssue((prev) => prev?.id === issueId ? { ...prev, comments: [...prev.comments, comment] } : prev);
    setNewComment("");
  };

  const handleSubmitIssue = async () => {
    if (!form.title.trim() || !form.room || !form.category || !form.description.trim()) {
      alert("Please fill all required fields.");
      return;
    }
    const newIssue: Issue = {
      id: Date.now(),
      postedById: user?.id,
      postedByEmail: user?.email,
      title: form.title,
      room: form.room,
      category: form.category as IssueCategory,
      description: form.description,
      postedBy: user?.name || "Anonymous",
      userRole: user?.role === "faculty" ? "Faculty" : "Student",
      date: `${form.date || "Today"} · ${form.time || "Now"}`,
      status: "Open",
      priority: "Medium",
      upvotes: 0,
      hasDocument: !!form.fileName,
      isAffectingBooking: form.isAffectingBooking,
      relatedBooking: form.relatedBooking || undefined,
      comments: [],
    };
    try {
      const savedIssue = await createIssue({
        title: form.title,
        roomName: form.room,
        category: form.category as IssueCategory,
        description: form.description,
      priority: "Medium",
      hasDocument: !!form.fileName,
      attachment: form.attachment,
      isAffectingBooking: form.isAffectingBooking,
      relatedBooking: form.relatedBooking || undefined,
    });
      setIssues((prev) => [fromServiceIssue(savedIssue as ClassroomIssue), ...prev]);
    } catch {
      setIssues((prev) => [newIssue, ...prev]);
    }
    setCreateOpen(false);
    setForm({ title: "", room: "", category: "", description: "", date: "", time: "", hasDocument: false, fileName: "", attachment: null, isAffectingBooking: false, relatedBooking: "" });
  };

  const detailIssue = selectedIssue ? issues.find((i) => i.id === selectedIssue.id) || selectedIssue : null;

  return (
    <div className="space-y-5" style={DM_SANS}>
      {/* Header */}
      <div className="flex items-start justify-between flex-wrap gap-4">
        <div>
          <h1 style={{ ...PLAYFAIR, fontSize: 28, fontWeight: 600 }} className="text-foreground">
            Classroom Forum
          </h1>
          <p className="text-sm mt-1" style={{ color: "#5E657B" }}>
            Report classroom issues, equipment problems, or schedule conflicts
          </p>
        </div>
        <button
          onClick={() => setCreateOpen(true)}
          className="flex items-center gap-2 px-4 py-2.5 rounded-full text-sm font-medium text-white transition-colors"
          style={{ background: "#891D1A" }}
          onMouseEnter={(e) => (e.currentTarget.style.background = "#210706")}
          onMouseLeave={(e) => (e.currentTarget.style.background = "#891D1A")}
        >
          <Plus className="w-4 h-4" />
          Report Classroom Issue
        </button>
      </div>

      {/* Search */}
      <div className="relative">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4" style={{ color: "#891D1A" }} />
        <input
          type="text"
          placeholder="Search by room, title, category or status…"
          value={searchQuery}
          onChange={(e) => setSearchQuery(e.target.value)}
          className="w-full pl-9 pr-4 py-2.5 rounded-xl border bg-card text-sm outline-none focus:ring-2 focus:ring-[#891D1A]/20"
          style={{ borderColor: "rgba(137,29,26,0.15)" }}
        />
      </div>

      {/* Filter Bar */}
      <div className="flex gap-2 overflow-x-auto pb-1">
        {FILTERS.map((f) => (
          <button
            key={f.key}
            onClick={() => setActiveFilter(f.key)}
            className="flex-shrink-0 px-3 py-1.5 rounded-full text-xs font-medium transition-colors"
            style={
              activeFilter === f.key
                ? { background: "#891D1A", color: "#F1E6D2" }
                : { background: "rgba(94,101,123,0.1)", color: "#5E657B" }
            }
          >
            {f.label}
          </button>
        ))}
      </div>

      {/* Issue Feed */}
      <div className="space-y-3">
        {filteredIssues.length === 0 && (
          <div className="py-14 text-center bg-card rounded-xl shadow-sm">
            <MessageSquare className="w-10 h-10 mx-auto mb-2 opacity-20" style={{ color: "#891D1A" }} />
            <p className="text-sm" style={{ color: "#5E657B" }}>
              {activeFilter === "my-posts" ? "No forum posts yet." : "No issues found."}
            </p>
          </div>
        )}

        {filteredIssues.map((issue) => {
          const ss = statusStyle(issue.status);
          const ps = priorityStyle(issue.priority);
          const isUpvoted = upvoted.has(issue.id);
          const isConflict = issue.category === "Schedule Conflict";
          const isMaintenance = issue.category === "Maintenance Problem";

          return (
            <div
              key={issue.id}
              className="bg-card rounded-xl shadow-sm overflow-hidden cursor-pointer transition-opacity hover:opacity-95"
              style={{
                borderLeft: `3px solid ${
                  issue.priority === "Urgent" ? "#891D1A" :
                  issue.priority === "High" ? "#891D1A" :
                  issue.priority === "Medium" ? "#B8860B" : "#5E657B"
                }`,
              }}
              onClick={() => setSelectedIssue(issue)}
            >
              <div className="p-5">
                <div className="flex items-start gap-3">
                  {/* Left: upvote */}
                  <div className="flex flex-col items-center gap-1 flex-shrink-0 mt-1">
                    <button
                      onClick={(e) => { e.stopPropagation(); handleUpvote(issue.id); }}
                      className="flex flex-col items-center gap-0.5 px-2 py-1.5 rounded-lg transition-colors"
                      style={
                        isUpvoted
                          ? { background: "rgba(137,29,26,0.1)", color: "#891D1A" }
                          : { background: "rgba(94,101,123,0.06)", color: "#5E657B" }
                      }
                    >
                      <ThumbsUp className="w-3.5 h-3.5" />
                      <span className="text-xs font-semibold">{issue.upvotes}</span>
                    </button>
                  </div>

                  {/* Content */}
                  <div className="flex-1 min-w-0">
                    <div className="flex items-start justify-between gap-3 flex-wrap mb-2">
                      <div className="flex-1 min-w-0">
                        <h3 className="font-semibold text-foreground" style={PLAYFAIR}>{issue.title}</h3>
                        <div className="flex items-center gap-2 mt-1 flex-wrap">
                          <span className="text-xs font-medium" style={{ color: "#5E657B" }}>{issue.room}</span>
                          <span className="text-xs" style={{ color: "#5E657B" }}>·</span>
                          <span className="flex items-center gap-0.5 text-xs" style={{ color: "#5E657B" }}>
                            {categoryIcon(issue.category)} {issue.category}
                          </span>
                        </div>
                      </div>
                      <div className="flex items-center gap-2 flex-shrink-0 flex-wrap">
                        <span
                          className="text-xs px-2 py-0.5 rounded-full font-medium"
                          style={{ background: ps.bg, color: ps.color }}
                        >
                          {issue.priority}
                        </span>
                        <span
                          className="text-xs px-2 py-0.5 rounded-full font-medium"
                          style={{ background: ss.bg, color: ss.color }}
                        >
                          {ss.label}
                        </span>
                      </div>
                    </div>

                    <p className="text-sm line-clamp-2 mb-3" style={{ color: "#5E657B" }}>
                      {issue.description}
                    </p>

                    {/* Conflict / Maintenance warning */}
                    {isConflict && (
                      <div className="flex items-center gap-1.5 mb-2 px-2.5 py-1.5 rounded-lg w-fit" style={{ background: "rgba(137,29,26,0.07)", border: "1px solid rgba(137,29,26,0.2)" }}>
                        <AlertTriangle className="w-3.5 h-3.5 flex-shrink-0" style={{ color: "#891D1A" }} />
                        <span className="text-xs font-medium" style={{ color: "#891D1A" }}>Schedule Conflict — Admin Action Required</span>
                      </div>
                    )}
                    {isMaintenance && (
                      <div className="flex items-center gap-1.5 mb-2 px-2.5 py-1.5 rounded-lg w-fit" style={{ background: "rgba(184,134,11,0.08)", border: "1px solid rgba(184,134,11,0.2)" }}>
                        <Wrench className="w-3.5 h-3.5 flex-shrink-0" style={{ color: "#B8860B" }} />
                        <span className="text-xs font-medium" style={{ color: "#B8860B" }}>Maintenance Issue</span>
                      </div>
                    )}

                    <div className="flex items-center justify-between flex-wrap gap-2">
                      <div className="flex items-center gap-3">
                        <div className="flex items-center gap-1.5">
                          <div
                            className="w-6 h-6 rounded-full flex items-center justify-center text-white text-xs font-semibold"
                            style={{ background: roleColor(issue.userRole) }}
                          >
                            {issue.postedBy.charAt(0)}
                          </div>
                          <span className="text-xs text-foreground">{issue.postedBy}</span>
                          <span
                            className="text-xs px-1.5 py-0.5 rounded-full"
                            style={{ background: roleColor(issue.userRole) + "15", color: roleColor(issue.userRole) }}
                          >
                            {issue.userRole}
                          </span>
                        </div>
                        <span className="text-xs" style={{ color: "#5E657B" }}>{issue.date}</span>
                      </div>

                      <div className="flex items-center gap-3 text-xs" style={{ color: "#5E657B" }}>
                        {issue.hasDocument && (
                          <span className="flex items-center gap-0.5">
                            <FileText className="w-3 h-3" /> Doc
                          </span>
                        )}
                        {issue.isAffectingBooking && (
                          <span className="flex items-center gap-0.5" style={{ color: "#B8860B" }}>
                            <CalendarDays className="w-3 h-3" /> Affects booking
                          </span>
                        )}
                        <span className="flex items-center gap-0.5">
                          <MessageSquare className="w-3 h-3" /> {issue.comments.length}
                        </span>
                        {issue.adminResponse && (
                          <span className="flex items-center gap-0.5" style={{ color: "#3B6E4A" }}>
                            <CheckCircle className="w-3 h-3" /> Admin responded
                          </span>
                        )}
                        <span className="flex items-center gap-0.5">
                          <Eye className="w-3 h-3" /> View
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          );
        })}
      </div>

      {/* Create Issue Modal */}
      {createOpen && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center p-4"
          style={{ background: "rgba(33,7,6,0.55)" }}
          onClick={() => setCreateOpen(false)}
        >
          <div
            className="bg-card rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto"
            onClick={(e) => e.stopPropagation()}
          >
            <div className="flex items-center justify-between px-6 py-4 border-b border-border">
              <h3 style={{ ...PLAYFAIR, fontSize: 18, fontWeight: 600 }} className="text-foreground">
                Report Classroom Issue
              </h3>
              <button
                onClick={() => setCreateOpen(false)}
                className="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-[#891D1A]/10"
                style={{ color: "#5E657B" }}
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            <div className="px-6 py-5 space-y-4">
              <div className="space-y-1.5">
                <label className="text-sm font-medium" style={{ color: "#5E657B" }}>
                  Issue Title <span style={{ color: "#891D1A" }}>*</span>
                </label>
                <input
                  type="text"
                  placeholder="Briefly describe the issue…"
                  value={form.title}
                  onChange={(e) => setForm((p) => ({ ...p, title: e.target.value }))}
                  className={inputCls}
                  style={borderStyle}
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div className="space-y-1.5">
                  <label className="text-sm font-medium" style={{ color: "#5E657B" }}>
                    Classroom <span style={{ color: "#891D1A" }}>*</span>
                  </label>
                  <select
                    value={form.room}
                    onChange={(e) => setForm((p) => ({ ...p, room: e.target.value }))}
                    className={inputCls + " cursor-pointer"}
                    style={borderStyle}
                  >
                    <option value="">Select room…</option>
                    {ROOMS.map((r) => <option key={r}>{r}</option>)}
                  </select>
                </div>

                <div className="space-y-1.5">
                  <label className="text-sm font-medium" style={{ color: "#5E657B" }}>
                    Category <span style={{ color: "#891D1A" }}>*</span>
                  </label>
                  <select
                    value={form.category}
                    onChange={(e) => setForm((p) => ({ ...p, category: e.target.value as IssueCategory }))}
                    className={inputCls + " cursor-pointer"}
                    style={borderStyle}
                  >
                    <option value="">Select category…</option>
                    {CATEGORIES.map((c) => <option key={c}>{c}</option>)}
                  </select>
                </div>
              </div>

              <div className="space-y-1.5">
                <label className="text-sm font-medium" style={{ color: "#5E657B" }}>
                  Description <span style={{ color: "#891D1A" }}>*</span>
                </label>
                <textarea
                  placeholder="Describe the issue in detail…"
                  value={form.description}
                  onChange={(e) => setForm((p) => ({ ...p, description: e.target.value }))}
                  rows={4}
                  className="w-full px-3 py-2.5 rounded-lg border bg-white dark:bg-[#3A1210] dark:text-[#F1E6D2] outline-none focus:ring-2 focus:ring-[#891D1A]/30 resize-none text-sm"
                  style={borderStyle}
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div className="space-y-1.5">
                  <label className="text-sm font-medium" style={{ color: "#5E657B" }}>Date Noticed</label>
                  <input type="date" value={form.date} onChange={(e) => setForm((p) => ({ ...p, date: e.target.value }))} className={inputCls} style={borderStyle} />
                </div>
                <div className="space-y-1.5">
                  <label className="text-sm font-medium" style={{ color: "#5E657B" }}>Time Noticed</label>
                  <input type="time" value={form.time} onChange={(e) => setForm((p) => ({ ...p, time: e.target.value }))} className={inputCls} style={borderStyle} />
                </div>
              </div>

              {/* Upload */}
              {form.fileName ? (
                <div className="flex items-center gap-3 p-3 rounded-lg" style={{ background: "rgba(59,110,74,0.06)", border: "1px solid rgba(59,110,74,0.2)" }}>
                  <FileText className="w-4 h-4" style={{ color: "#3B6E4A" }} />
                  <span className="text-sm flex-1 text-foreground">{form.fileName}</span>
                  <button onClick={() => setForm((p) => ({ ...p, fileName: "", attachment: null }))} style={{ color: "#891D1A" }}>
                    <X className="w-4 h-4" />
                  </button>
                </div>
              ) : (
                <label
                  className="block rounded-lg border-2 border-dashed p-4 text-center cursor-pointer"
                  style={{ borderColor: "rgba(137,29,26,0.3)" }}
                >
                  <Upload className="w-6 h-6 mx-auto mb-1" style={{ color: "#891D1A" }} />
                  <p className="text-sm" style={{ color: "#5E657B" }}>
                    Upload photo or document <span style={{ color: "#891D1A" }}>browse</span>
                  </p>
                  <input
                    type="file"
                    className="hidden"
                    onChange={(e) => {
                      const file = e.target.files?.[0];
                      if (file) setForm((p) => ({ ...p, fileName: file.name, attachment: file }));
                    }}
                  />
                </label>
              )}

              {/* Affecting booking */}
              <div className="space-y-2">
                <div className="flex items-center gap-3">
                  <button
                    type="button"
                    onClick={() => setForm((p) => ({ ...p, isAffectingBooking: !p.isAffectingBooking }))}
                    className="w-10 h-5 rounded-full transition-colors relative flex-shrink-0"
                    style={{ background: form.isAffectingBooking ? "#891D1A" : "#5E657B" }}
                  >
                    <div className="w-3.5 h-3.5 bg-white rounded-full absolute top-0.5 transition-all" style={{ left: form.isAffectingBooking ? "calc(100% - 18px)" : "3px" }} />
                  </button>
                  <label className="text-sm font-medium text-foreground cursor-pointer">
                    This is affecting a booking or class
                  </label>
                </div>
                {form.isAffectingBooking && (
                  <input
                    type="text"
                    placeholder="Related booking or event name…"
                    value={form.relatedBooking}
                    onChange={(e) => setForm((p) => ({ ...p, relatedBooking: e.target.value }))}
                    className={inputCls}
                    style={borderStyle}
                  />
                )}
              </div>

              <div className="flex gap-3 pt-1">
                <button
                  onClick={() => setCreateOpen(false)}
                  className="flex-1 py-2.5 rounded-lg text-sm font-medium border"
                  style={{ borderColor: "rgba(137,29,26,0.3)", color: "#5E657B" }}
                >
                  Cancel
                </button>
                <button
                  onClick={handleSubmitIssue}
                  className="flex-1 py-2.5 rounded-lg text-sm font-medium text-white flex items-center justify-center gap-2"
                  style={{ background: "#891D1A" }}
                  onMouseEnter={(e) => (e.currentTarget.style.background = "#210706")}
                  onMouseLeave={(e) => (e.currentTarget.style.background = "#891D1A")}
                >
                  <Send className="w-4 h-4" /> Submit Issue
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Issue Detail Panel */}
      {detailIssue && (
        <div
          className="fixed inset-0 z-40"
          style={{ background: "rgba(33,7,6,0.4)" }}
          onClick={() => setSelectedIssue(null)}
        >
          <div
            className="absolute right-0 top-0 h-full w-[420px] bg-card shadow-2xl flex flex-col"
            style={{ borderLeft: "1px solid rgba(137,29,26,0.15)" }}
            onClick={(e) => e.stopPropagation()}
          >
            {/* Panel Header */}
            <div className="flex items-center justify-between px-5 py-4 border-b border-border flex-shrink-0">
              <div className="flex items-center gap-2">
                {categoryIcon(detailIssue.category)}
                <span className="text-sm font-semibold text-foreground">{detailIssue.category}</span>
              </div>
              <button
                onClick={() => setSelectedIssue(null)}
                className="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-[#891D1A]/10"
                style={{ color: "#5E657B" }}
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            <div className="flex-1 overflow-y-auto">
              <div className="p-5 space-y-4">
                {/* Title + badges */}
                <div>
                  <h3 className="font-semibold text-foreground mb-2" style={{ ...PLAYFAIR, fontSize: 16 }}>
                    {detailIssue.title}
                  </h3>
                  <div className="flex flex-wrap gap-2">
                    {(() => {
                      const ss = statusStyle(detailIssue.status);
                      const ps = priorityStyle(detailIssue.priority);
                      return (
                        <>
                          <span className="text-xs px-2 py-0.5 rounded-full font-medium" style={{ background: ss.bg, color: ss.color }}>{ss.label}</span>
                          <span className="text-xs px-2 py-0.5 rounded-full font-medium" style={{ background: ps.bg, color: ps.color }}>{detailIssue.priority} Priority</span>
                        </>
                      );
                    })()}
                  </div>
                </div>

                {/* Meta grid */}
                <div className="grid grid-cols-2 gap-3 text-sm">
                  {[
                    { label: "ROOM", value: detailIssue.room },
                    { label: "POSTED BY", value: `${detailIssue.postedBy} (${detailIssue.userRole})` },
                    { label: "DATE", value: detailIssue.date },
                    { label: "UPVOTES", value: String(detailIssue.upvotes) },
                  ].map((row) => (
                    <div key={row.label}>
                      <p className="text-xs font-semibold mb-0.5" style={{ color: "#5E657B" }}>{row.label}</p>
                      <p className="text-sm text-foreground">{row.value}</p>
                    </div>
                  ))}
                </div>

                {/* Description */}
                <div>
                  <p className="text-xs font-semibold mb-1" style={{ color: "#5E657B" }}>DESCRIPTION</p>
                  <p className="text-sm text-foreground leading-relaxed">{detailIssue.description}</p>
                </div>

                {/* Document */}
                {detailIssue.hasDocument && (
                  <div className="flex items-center gap-2 p-3 rounded-lg" style={{ background: "rgba(94,101,123,0.06)", border: "1px solid rgba(94,101,123,0.15)" }}>
                    <FileText className="w-4 h-4" style={{ color: "#5E657B" }} />
                    <span className="text-sm" style={{ color: "#5E657B" }}>Supporting document attached</span>
                  </div>
                )}

                {/* Related booking */}
                {detailIssue.isAffectingBooking && detailIssue.relatedBooking && (
                  <div className="flex items-center gap-2 p-3 rounded-lg" style={{ background: "rgba(184,134,11,0.07)", border: "1px solid rgba(184,134,11,0.2)" }}>
                    <CalendarDays className="w-4 h-4 flex-shrink-0" style={{ color: "#B8860B" }} />
                    <div>
                      <p className="text-xs font-semibold" style={{ color: "#B8860B" }}>Related Booking</p>
                      <p className="text-sm text-foreground">{detailIssue.relatedBooking}</p>
                    </div>
                  </div>
                )}

                {/* Conflict warning */}
                {detailIssue.category === "Schedule Conflict" && (
                  <div className="p-3 rounded-lg" style={{ background: "rgba(137,29,26,0.06)", border: "1px solid rgba(137,29,26,0.2)" }}>
                    <div className="flex items-center gap-2 mb-1">
                      <AlertTriangle className="w-4 h-4" style={{ color: "#891D1A" }} />
                      <p className="text-sm font-semibold" style={{ color: "#891D1A" }}>Conflicting Booking Warning</p>
                    </div>
                    <p className="text-xs" style={{ color: "#5E657B" }}>This issue involves a scheduling conflict. Admin action required to resolve the booking overlap.</p>
                  </div>
                )}

                {/* Admin response */}
                {detailIssue.adminResponse && (
                  <div className="p-3 rounded-lg" style={{ background: "rgba(59,110,74,0.06)", border: "1px solid rgba(59,110,74,0.2)" }}>
                    <div className="flex items-center gap-2 mb-1">
                      <CheckCircle className="w-4 h-4" style={{ color: "#3B6E4A" }} />
                      <p className="text-xs font-semibold" style={{ color: "#3B6E4A" }}>Admin Response</p>
                    </div>
                    <p className="text-sm text-foreground">{detailIssue.adminResponse}</p>
                  </div>
                )}

                {/* Comments */}
                <div>
                  <p className="text-xs font-semibold mb-3" style={{ color: "#5E657B" }}>
                    COMMENTS ({detailIssue.comments.length})
                  </p>
                  <div className="space-y-3">
                    {detailIssue.comments.map((c) => (
                      <div key={c.id} className="flex gap-2.5">
                        <div
                          className="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-semibold flex-shrink-0"
                          style={{ background: roleColor(c.role) }}
                        >
                          {c.author.charAt(0)}
                        </div>
                        <div className="flex-1 min-w-0">
                          <div className="flex items-center gap-2 flex-wrap">
                            <span className="text-xs font-semibold text-foreground">{c.author}</span>
                            <span className="text-xs px-1.5 py-0.5 rounded-full" style={{ background: roleColor(c.role) + "15", color: roleColor(c.role) }}>
                              {c.role}
                            </span>
                            <span className="text-xs" style={{ color: "#5E657B" }}>{c.time}</span>
                          </div>
                          <p className="text-sm mt-0.5 text-foreground">{c.text}</p>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>

                {/* Add comment */}
                <div className="flex gap-2">
                  <input
                    type="text"
                    placeholder="Add a comment…"
                    value={newComment}
                    onChange={(e) => setNewComment(e.target.value)}
                    onKeyDown={(e) => { if (e.key === "Enter") handleAddComment(detailIssue.id); }}
                    className="flex-1 px-3 py-2 rounded-lg border bg-white dark:bg-[#3A1210] dark:text-[#F1E6D2] outline-none text-sm"
                    style={borderStyle}
                  />
                  <button
                    onClick={() => handleAddComment(detailIssue.id)}
                    className="px-3 py-2 rounded-lg text-white text-sm font-medium"
                    style={{ background: "#891D1A" }}
                  >
                    <Send className="w-4 h-4" />
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
