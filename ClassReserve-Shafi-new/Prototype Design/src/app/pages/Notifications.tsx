import { Card, CardContent } from "../components/ui/card";
import { Badge } from "../components/ui/badge";
import { 
  CheckCircle, 
  XCircle, 
  Clock, 
  AlertTriangle,
  Bell,
  Info
} from "lucide-react";

const notifications = [
  {
    id: 1,
    type: 'success',
    title: 'Booking Approved',
    message: 'Your booking for Room A-301 on Apr 1, 2026 has been approved.',
    time: '5 minutes ago',
    unread: true,
  },
  {
    id: 2,
    type: 'warning',
    title: 'Conflict Detected',
    message: 'Room D-202 has a scheduling conflict for Apr 3, 2026 at 2:00 PM.',
    time: '1 hour ago',
    unread: true,
  },
  {
    id: 3,
    type: 'pending',
    title: 'Awaiting Approval',
    message: 'Study Group Session booking is pending approval.',
    time: '2 hours ago',
    unread: false,
  },
  {
    id: 4,
    type: 'error',
    title: 'Booking Rejected',
    message: 'Your booking request for Room E-101 has been rejected due to maintenance.',
    time: '3 hours ago',
    unread: false,
  },
  {
    id: 5,
    type: 'info',
    title: 'Room Maintenance Scheduled',
    message: 'Room D-202 will be under maintenance from Apr 10-12, 2026.',
    time: '1 day ago',
    unread: false,
  },
  {
    id: 6,
    type: 'success',
    title: 'New Room Available',
    message: 'Lab C-107 is now available for booking.',
    time: '2 days ago',
    unread: false,
  },
  {
    id: 7,
    type: 'info',
    title: 'System Update',
    message: 'The booking system will undergo maintenance on Apr 15, 2026.',
    time: '3 days ago',
    unread: false,
  },
];

export function Notifications() {
  const getIcon = (type: string) => {
    switch (type) {
      case 'success':
        return <CheckCircle className="w-5 h-5 text-green-600" />;
      case 'error':
        return <XCircle className="w-5 h-5 text-red-600" />;
      case 'warning':
        return <AlertTriangle className="w-5 h-5 text-yellow-600" />;
      case 'pending':
        return <Clock className="w-5 h-5 text-blue-600" />;
      case 'info':
        return <Info className="w-5 h-5 text-gray-600" />;
      default:
        return <Bell className="w-5 h-5 text-gray-600" />;
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">Notifications</h1>
        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
          Stay updated with booking alerts and system messages
        </p>
      </div>

      <Card className="rounded-2xl border-gray-200 dark:border-gray-800 dark:bg-gray-900">
        <CardContent className="p-0">
          <div className="divide-y divide-gray-200 dark:divide-gray-800">
            {notifications.map((notification) => (
              <div
                key={notification.id}
                className={`p-4 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors ${
                  notification.unread ? 'bg-blue-50/30 dark:bg-blue-950/20' : ''
                }`}
              >
                <div className="flex gap-4">
                  <div className="flex-shrink-0 mt-1">
                    {getIcon(notification.type)}
                  </div>
                  <div className="flex-1 min-w-0">
                    <div className="flex items-start justify-between gap-2">
                      <div className="flex items-center gap-2">
                        <p className="font-medium text-gray-900 dark:text-white">
                          {notification.title}
                        </p>
                        {notification.unread && (
                          <Badge className="bg-blue-600 text-white hover:bg-blue-600 h-5 px-2">
                            New
                          </Badge>
                        )}
                      </div>
                      <span className="text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                        {notification.time}
                      </span>
                    </div>
                    <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                      {notification.message}
                    </p>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}