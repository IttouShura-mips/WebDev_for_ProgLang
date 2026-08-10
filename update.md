# ICF Student Portal — Update Notes

**Date:** August 10, 2026

---

## What Was Wrong

The frontend forms and PHP backends were completely disconnected. You could fill out the login or registration forms, but nothing would actually happen because:

- Forms had no `action` or `method` — data never went anywhere.
- Input fields had no `name` attributes — PHP couldn't read what you typed.
- Field names didn't match between HTML and PHP (e.g., `fullname` vs `first_name`).
- After login, it sent you back to the login page instead of a dashboard.
- `profile.php` was just one line of text with no session check or student info.

---

## What Was Fixed

### 1. Login Page (`login.html`)
- Added `action="login.php"` and `method="POST"` to the form.
- Added `name="username"` and `name="password"` to the inputs so PHP can receive the data.
- Added `name="submit"` to the login button so PHP knows the form was submitted.

### 2. Registration Page (`registerstudent.html`)
- Added `action="register.php"` and `method="POST"` to the form.
- Renamed fields to match the database:
  - `fullname` → `first_name`
  - `middlename` → `middle_name`
  - `lastname` → `last_name`
- Added `name` attributes to all inputs.

### 3. Login Handler (`login.php`)
- Fixed invalid login redirect to point to `login.html` (same folder) instead of a broken `../../login.php` path.
- After successful login, the success screen now links to `profile.php` (the dashboard) instead of sending you back to the login page.

### 4. Registration Handler (`register.php`)
- Fixed success screen button to go to `login.html` instead of a broken `../../index.html` path.
- Fixed "Back to Login" link to point to `login.html` in the same folder.

### 5. Profile Dashboard (`profile.php`)
This file was completely rebuilt from scratch.

**Before:** Just a single line: `echo "Account succesfully logged in";`

**After:**
- Checks if the user is logged in. If not, sends them to `login.html`.
- Connects to the database and fetches the full student record.
- Displays a clean dashboard showing:
  - Full name (First + Middle + Last)
  - User ID
  - First Name
  - Middle Name
  - Last Name
  - Username / Student ID
- Includes a navbar with a **Logout** button.
- Fully responsive on mobile.

### 6. Logout (`logout.php`) — New File
- Destroys the session securely.
- Clears the session cookie.
- Redirects back to `login.html`.

---

## Final File Layout

Put all these files in the same folder (e.g., `/student/`):

```
/student/
├── login.html           ← The login form
├── login.php            ← Checks username & password
├── registerstudent.html ← The registration form
├── register.php         ← Saves new student to database
├── profile.php          ← Student dashboard (shows your info)
└── logout.php           ← Logs you out
```

---

## Database Table

Make sure your MySQL database has this table:

```sql
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

- `password` must be stored as a bcrypt hash (PHP's `password_hash()` handles this automatically).
- Do not add or display the password anywhere in the UI.

---

## How It Works Now

1. **Register** at `registerstudent.html` → form submits to `register.php` → account saved to database → click "Go to Login".
2. **Log in** at `login.html` → form submits to `login.php` → password is verified → session starts → click "Go to Dashboard".
3. **Dashboard** at `profile.php` → shows your real name, ID, and info pulled live from the database → click **Logout** anytime.
4. **Log out** at `logout.php` → session destroyed → sent back to `login.html`.
