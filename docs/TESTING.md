# ClassReserve Testing Guide

This document describes the manual test scenarios and cases to verify all security, rooms, bookings, calendar, check-in, and email notification functions in ClassReserve.

---

## 1. Authentication & Security Validation Tests

### 1.1 Login Rate Limiting (Brute-Force Protection)
- **Goal**: Verify that users are blocked after 5 failed login attempts within 15 minutes.
- **Role**: Guest / Student
- **Steps**:
  1. Open the login page.
  2. Enter a valid user email (e.g., `alice.johnson@uni.edu`) but type an incorrect password. Click "Login".
  3. Repeat this 4 more times (5 total failed attempts).
  4. On the 6th attempt, you must receive the error: `"Too many failed login attempts. Please try again after 15 minutes."` with a `429 Too Many Requests` API status.
  5. Wait 15 minutes or manually clear the IP/email entries from the `failed_logins` database table, then log in with the correct password. It should successfully authenticate and clear any remaining fail counters.

### 1.2 Strong Password Validation
- **Goal**: Enforce high-entropy password requirements during registration and password change.
- **Steps**:
  1. Go to register a new user.
  2. Input a weak password (e.g. `12345` or `password`). Click "Sign Up".
  3. Confirm you receive the validation error: `"Password must be at least 8 characters long and include at least one uppercase letter, one lowercase letter, one number, and one special character."`
  4. Input a strong password (e.g. `ClassReserve123!`). Click "Sign Up". Confirm registration completes successfully.
  5. Repeat this check in the User Profile page when doing a password change.

### 1.3 CSRF Protection
- **Goal**: Confirm write endpoints (POST, PUT, DELETE) reject requests without a valid `X-CSRF-Token` header.
- **Steps**:
  1. Use a tool like Postman or run a curl command to hit `/api/bookings.php` with a POST request.
  2. Do not supply the `X-CSRF-Token` header.
  3. Confirm the API rejects the request with a `403 Forbidden` response: `{"error": "CSRF verification failed."}`.

---

## 2. Room Equipment relational management

### 2.1 Admin Room Creation / Update
- **Goal**: Verify room equipment is successfully parsed and written to the relational database schema.
- **Role**: Admin
- **Steps**:
  1. Log in as Admin (`admin@uni.edu`).
  2. Go to the "Rooms" page and click "Add Room".
  3. Enter details: Name = `Room F-203`, Building = `Building F`, Floor = `2nd Floor`, Capacity = `30`, Type = `Lecture`, Equipment = `Projector, Whiteboard, Wi-Fi, AC`. Click "Add Room".
  4. Confirm the room is created and list page displays the badges: `Projector`, `Whiteboard`, `Wi-Fi`, `AC`.
  5. Check MySQL database tables `equipment` and `room_equipment`. Verify that relations have been created linking the new room to correct equipment IDs.
  6. Edit the room to remove `AC` and add `Computer`. Save and verify that relational tables are updated correctly.

---

## 3. Booking Creation, Approval, and Notifications

### 3.1 Booking Creation Test
- **Goal**: Create booking requests as student or club roles.
- **Role**: Student / Club
- **Steps**:
  1. Log in as a student (e.g. `rahul.sen@uni.edu` / `ClassReserve123!`).
  2. Go to Calendar, click on an available date, and fill in booking details.
  3. Submit the request.
  4. Confirm a success notification is shown, booking status is "Pending Review", and an audit log entry for `booking_created` is registered.

### 3.2 Booking Review (Approval/Rejection)
- **Goal**: Approve/Reject bookings and verify email/notification triggers.
- **Role**: Admin / Faculty
- **Steps**:
  1. Log in as Admin or Faculty.
  2. Go to the "Approvals" page.
  3. Select a pending booking:
     - **Approve**: Click "Approve". Check that:
       - Status updates to "Approved".
       - Notification is dispatched to the student.
       - Email notification is sent (if SMTP is active) with the check-in code.
     - **Reject**: Click "Reject" and type a rejection reason. Check that:
       - Status updates to "Rejected".
       - Notification is dispatched to the student with the reason.
       - Rejection email is sent to the student.

---

## 4. Calendar Conflict Visualization

### 4.1 Room Time Conflict Warning
- **Goal**: Verify overlaps in the same room on the same day are caught and visually highlighted.
- **Steps**:
  1. Log in as Admin/Faculty.
  2. Create/approve two overlapping bookings (e.g., Booking A: 10:00 - 12:00, Booking B: 11:00 - 13:00) for `Room A-301`.
  3. Go to the Calendar page.
  4. Verify that the conflict warning badge (`⚠️ Conflict detected`) is clearly displayed on both overlapping bookings.
  5. Schedule a maintenance window for `Room A-301` overlapping with one of the approved classes.
  6. Confirm the conflict badge changes to show (`⚠️ Maintenance conflict`).

### 4.2 Room-wise Daily Schedule
- **Goal**: Verify day view groupings by room.
- **Steps**:
  1. Go to the Calendar page.
  2. Switch to **Day** view.
  3. Verify that all bookings scheduled for the day are grouped under their respective room headings (e.g., `🏢 Room A-301 (Building A)`) instead of appearing as a single long unsorted list.

---

## 5. QR & Check-in Workflow

### 5.1 Verification and Check-in
- **Goal**: Use check-in codes or QR images to successfully log attendance.
- **Role**: Student / Booking Owner
- **Steps**:
  1. Log in as the student who owns an approved booking.
  2. Go to "My Bookings" and select the approved booking.
  3. Verify that the check-in code and the dynamic QR code image generated by `api.qrserver.com` are displayed.
  4. Click the "Check In Now" button.
  5. Confirm success alert appears and the booking details page status updates to showing checked in time `✓ Checked in at [timestamp]`.

### 5.2 Auto No-Show Expiry
- **Goal**: Check that bookings are expired 15 minutes after start time if not checked in.
- **Steps**:
  1. Create a booking that starts 20 minutes in the past. Approve it.
  2. Reload the bookings list or dashboard page.
  3. Verify that the booking status is automatically updated to `✗ Marked as No-Show (expired)` (no_show = 1 in db) and check-in options are locked out.
