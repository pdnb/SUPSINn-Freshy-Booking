---
paths:
  - 'app/Services/Order/**'
  - 'resources/views/pages/admin/settings/**'
---

# Order numbers

New orders get `FB-{YY}-{NNNN}` from `OrderNumberGenerator` at `place()` (e.g. `FB-69-0001`). `FB` is fixed. `YY` is the last two digits of the Buddhist `academic_year` setting (admin tab ปีการศึกษา, `AcademicYearSettingService`). The 4-digit sequence increments per year prefix and restarts when the year changes. Do not randomize `orders.number`. Existing numbers stay as stored; sequence matching uses `LIKE 'FB-{YY}-____'` so legacy 8-digit numbers are ignored. If the sequence exceeds 9999, reject checkout until staff changes the academic year.
