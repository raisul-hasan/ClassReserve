import { useEffect, useState } from "react";
import { AlertTriangle, History } from "lucide-react";
import { getAuditLogs } from "../services/classReserveService";
import type { AuditLog } from "../types/classReserve";

const PLAYFAIR = { fontFamily: "'Playfair Display', serif" } as const;
const DM_SANS = { fontFamily: "'DM Sans', sans-serif" } as const;

function formatAction(action: string) { return action.replaceAll("_", " ").replace(/\b\w/g, (letter) => letter.toUpperCase()); }

export function AuditLogs() {
  const [logs, setLogs] = useState<AuditLog[]>([]);
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    getAuditLogs().then((items) => { setLogs(items); setError(""); })
      .catch((failure) => setError(failure instanceof Error ? failure.message : "Could not load audit logs."))
      .finally(() => setLoading(false));
  }, []);

  return <div className="space-y-5" style={DM_SANS}>
    <div><h1 style={{ ...PLAYFAIR, fontSize: 28, fontWeight: 600 }} className="text-foreground">Audit Logs</h1><p className="text-sm mt-1" style={{ color: "#5E657B" }}>{loading ? "Loading system activity..." : "Administrative record of important system actions"}</p></div>
    {error && <div className="rounded-xl p-4 text-sm flex gap-3" style={{ background: "rgba(137,29,26,0.06)", border: "1px solid rgba(137,29,26,0.2)", color: "#891D1A" }}><AlertTriangle className="w-5 h-5" />{error}</div>}
    <div className="bg-card rounded-xl shadow-sm overflow-hidden"><div className="px-5 py-4 border-b border-border flex items-center gap-2"><History className="w-4 h-4" style={{ color: "#891D1A" }} /><h3 style={{ ...PLAYFAIR, fontSize: 16, fontWeight: 600 }}>System Activity</h3></div><div className="overflow-x-auto"><table className="w-full text-sm"><thead><tr className="text-left" style={{ color: "#5E657B" }}><th className="px-5 py-3 font-medium">Action</th><th className="px-5 py-3 font-medium">User</th><th className="px-5 py-3 font-medium">Target</th><th className="px-5 py-3 font-medium">Details</th><th className="px-5 py-3 font-medium">Created</th></tr></thead><tbody className="divide-y divide-border">{logs.map((log) => <tr key={log.id}><td className="px-5 py-3 font-medium text-foreground">{formatAction(log.action)}</td><td className="px-5 py-3"><p className="text-foreground">{log.userName || "System"}</p><p className="text-xs" style={{ color: "#5E657B" }}>{log.userEmail || log.userRole || ""}</p></td><td className="px-5 py-3" style={{ color: "#5E657B" }}>{log.targetType || "-"}{log.targetId ? ` #${log.targetId}` : ""}</td><td className="px-5 py-3 max-w-md truncate" style={{ color: "#5E657B" }}>{log.details || "-"}</td><td className="px-5 py-3 whitespace-nowrap" style={{ color: "#5E657B" }}>{log.createdAt}</td></tr>)}{!logs.length && !loading && <tr><td colSpan={5} className="px-5 py-10 text-center" style={{ color: "#5E657B" }}>No audit logs yet.</td></tr>}</tbody></table></div></div>
  </div>;
}
