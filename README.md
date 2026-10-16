# MySchool — مدرسه من
Version 0.1.3, cumulative WordPress plugin.
Requirements: WordPress 6.4+, PHP 8.0+.

W00.01: bootstrap and module loading.
W00.02: installer and nine core database tables.
W00.03: five school roles with granular permissions.
W00.04: RTL responsive admin dashboard, REST API namespace myschool/v1, Gregorian/Jalali date conversion.
REST routes: /wp-json/myschool/v1/status (myschool_manage_school) and /wp-json/myschool/v1/date/convert?date=2026-10-16 (myschool_access).
Dates persist as Gregorian ISO dates; Jalali is for display.
Existing data is retained during plugin upgrades.
