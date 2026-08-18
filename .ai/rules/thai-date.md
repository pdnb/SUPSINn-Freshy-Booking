---
paths:
  - 'resources/views/**'
  - 'app/Support/ThaiDate.php'
  - 'app/Providers/AppServiceProvider.php'
---

# Thai display dates

User-facing datetimes go through `ThaiDate::datetime()` or Carbon `toThaiDatetime()` (`18 ส.ค. 2569 14:30 น.`: Buddhist year, short Thai month, no leading zero on the day, 24-hour time, app timezone). Do not call `format('Y-m-d H:i')` or `format('d/m/Y H:i')` in views.

ISO `Y-m-d` and `Y-m-d\TH:i` stay only on `type="date"` / `datetime-local` inputs and for parsing or filters.
