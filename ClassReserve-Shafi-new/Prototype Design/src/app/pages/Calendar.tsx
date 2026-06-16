import { Card, CardContent, CardHeader, CardTitle } from "../components/ui/card";
import { Badge } from "../components/ui/badge";

const timeSlots = [
  '08:00', '09:00', '10:00', '11:00', '12:00', 
  '13:00', '14:00', '15:00', '16:00', '17:00', '18:00'
];

const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

const bookings = [
  { day: 0, start: 0, duration: 2, room: 'A-301', title: 'Math 101', status: 'approved' },
  { day: 0, start: 3, duration: 1, room: 'B-205', title: 'Club Meeting', status: 'approved' },
  { day: 1, start: 1, duration: 2, room: 'C-105', title: 'Physics Lab', status: 'approved' },
  { day: 1, start: 5, duration: 2, room: 'A-301', title: 'Study Group', status: 'pending' },
  { day: 2, start: 2, duration: 1, room: 'D-202', title: 'CS Lecture', status: 'approved' },
  { day: 2, start: 2, duration: 1, room: 'D-203', title: 'Workshop', status: 'conflict' },
  { day: 3, start: 4, duration: 3, room: 'Aud-B', title: 'Seminar', status: 'approved' },
  { day: 4, start: 0, duration: 1, room: 'E-101', title: 'Tutorial', status: 'pending' },
  { day: 4, start: 6, duration: 2, room: 'A-301', title: 'Event', status: 'approved' },
];

export function Calendar() {
  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">Calendar</h1>
          <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">Weekly room booking overview</p>
        </div>
        <div className="flex items-center gap-2">
          <Badge className="bg-green-100 dark:bg-green-950 text-green-700 dark:text-green-300 hover:bg-green-100 dark:hover:bg-green-950">
            Approved
          </Badge>
          <Badge className="bg-yellow-100 dark:bg-yellow-950 text-yellow-700 dark:text-yellow-300 hover:bg-yellow-100 dark:hover:bg-yellow-950">
            Pending
          </Badge>
          <Badge className="bg-red-100 dark:bg-red-950 text-red-700 dark:text-red-300 hover:bg-red-100 dark:hover:bg-red-950">
            Conflict
          </Badge>
        </div>
      </div>

      <Card className="rounded-2xl border-gray-200 dark:border-gray-800 dark:bg-gray-900">
        <CardHeader>
          <CardTitle className="dark:text-white">Week of March 31, 2026</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="overflow-x-auto">
            <div className="min-w-[900px]">
              {/* Header Row */}
              <div className="grid grid-cols-6 gap-2 mb-2">
                <div className="text-sm font-medium text-gray-600 dark:text-gray-400">Time</div>
                {days.map((day) => (
                  <div key={day} className="text-sm font-medium text-gray-900 dark:text-white text-center">
                    {day}
                  </div>
                ))}
              </div>

              {/* Time Grid */}
              <div className="space-y-1">
                {timeSlots.map((time, timeIndex) => (
                  <div key={time} className="grid grid-cols-6 gap-2">
                    <div className="text-sm text-gray-600 dark:text-gray-400 py-3">{time}</div>
                    {days.map((_, dayIndex) => {
                      const booking = bookings.find(
                        (b) => b.day === dayIndex && b.start === timeIndex
                      );
                      
                      if (booking) {
                        const bgColor = 
                          booking.status === 'approved' ? 'bg-green-100 dark:bg-green-950 border-green-300 dark:border-green-800' :
                          booking.status === 'pending' ? 'bg-yellow-100 dark:bg-yellow-950 border-yellow-300 dark:border-yellow-800' :
                          'bg-red-100 dark:bg-red-950 border-red-300 dark:border-red-800';
                        const textColor = 
                          booking.status === 'approved' ? 'text-green-900 dark:text-green-300' :
                          booking.status === 'pending' ? 'text-yellow-900 dark:text-yellow-300' :
                          'text-red-900 dark:text-red-300';
                        
                        return (
                          <div
                            key={`${dayIndex}-${timeIndex}`}
                            className={`${bgColor} border-2 rounded-xl p-2 ${
                              booking.duration > 1 ? `row-span-${booking.duration}` : ''
                            }`}
                            style={{ gridRow: `span ${booking.duration}` }}
                          >
                            <p className={`text-xs font-medium ${textColor}`}>
                              {booking.title}
                            </p>
                            <p className={`text-xs ${textColor} opacity-75 mt-1`}>
                              {booking.room}
                            </p>
                          </div>
                        );
                      }
                      
                      return (
                        <div
                          key={`${dayIndex}-${timeIndex}`}
                          className="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-2 min-h-[60px] hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors cursor-pointer"
                        />
                      );
                    })}
                  </div>
                ))}
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}