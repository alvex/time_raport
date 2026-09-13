# LEGACY_MAPPING — scheduledev → time_raportDev

Referens: `C:\xampp\htdocs\scheduledev` (identisk kopia av
`C:\Xampp5_3\xampp\htdocs\scheduledev`; endast DB-namnet i config skiljer).
OBS: legacy-koden KRASCHAR på PHP 8.1 (PEAR-era) — den körbara instansen är
den gamla XAMPP 5.3-miljön. All paritet nedan är verifierad mot legacy-KOD
och rå databas, inte sida-vid-sida i browser.

## Funktionsmappning

| Legacy | Fil:rad | Ny motsvarighet |
|---|---|---|
| Login-formulär + `Auth::doLogin` | `index.php:53`, `lib/Auth.class.php:209-237` | `AuthController` + `Core\Auth::attempt` |
| MD5-verifiering `make_password()` | `lib/DBEngine.class.php:778` | `Auth::attempt` (`hash_equals(md5())`) |
| Sessionvariabler `sessionID`, `sessionName`, `hourOffset`, `keys` | `lib/Auth.class.php:209-217` | `$_SESSION['resource_id'/'name'/'hour_offset']` |
| `keys == 2` ⇒ anställd → ctrlpnl_resource | `lib/Auth.class.php:231-234` | `ResourceRepository::findActiveEmployeeByEmail` (`keys=2 AND status='a'`) |
| Veckokalender + bokningslista | `ctrlpnl_resource.php:97-128` | `ScheduleController::week` + `views/week.php` |
| Kanonisk veckofråga | `lib/DBEngine.class.php:444-484` | `BookingRepository::findForResourceBetween` |
| Veckostart måndag, 7 dagar | `lib/MyCalendar.class.php:141-163` | `ScheduleService::weekBounds` (ISO-år) |
| Tidsvisning + hourOffset | `lib/Time.class.php:23-51,145-217` | `Services\TimeFormatter` |
| Timberäkning per rad + veckototal | `templates/cpanel.template.php:572-621` | `ScheduleService::calculateBookingMinutes/-WeekMinutes` |
| Timjustering (changeTimes=1) skriver starttime/endtime/modified/is_pending/old_hours | `reserve.php` + `lib/db/ResDB.class.php:333-354`, `lib/Reservation.class.php:1215-1227` | `ScheduleController::jobUpdateTimes` + `BookingRepository::updateTimes` |
| Rapportpreview | `lib/Admin.class.php:319-331`, `templates/resources.template.php:261-331` | `ReportController::preview` + `views/report_preview.php` |
| Submit: `is_pending = 1` på valda resids | `lib/Admin.class.php:332-340`, `lib/db/AdminDB.class.php:981-988` | `ReportService::submitReport` + `BookingRepository::markPending` |
| Rapportmail till jobb@hemfresh.se, ämne "\<Namn\> Tid Redovisning!" | `lib/Admin.class.php:745-775`, `config/config.php:31` | `ReportService::buildEmailHtml` + `Core\Mailer` |
| Godkännande (`is_pending = 2`) | `admin.php?tool=approval` → `lib/db/ResDB.class.php:492-507` | **Oförändrat i scheduledev** — nya appen läser bara statusen |
| Statusfärger pending/godkänd | `templates/cpanel.template.php:557-567` | Badges Utkast/Inskickad/Godkänd |
| Historik (bakåtbläddring + admin-vyer) | `ctrlpnl_resource.php?date=`, `admin.php?tool=approved` | `ReportController::history` (härledd per ISO-vecka) |

## Beteenden som avsiktligt INTE kopierats

| Legacy-beteende | Varför |
|---|---|
| `?machid=` från URL utan ägarkontroll (`ctrlpnl_resource.php:60`) | IDOR — nya appen tar resource-id enbart från sessionen |
| Klientstyrda timvärden i rapportmailet (hidden inputs `diff_time[]`) | Räknas nu alltid om server-side |
| Cookie-autologin med rå `memberid` (`AuthDB::verifyID`) | Kontoövertagande — borttaget |
| `$_SESSION['resources'] = 1` öppnar admin-verktyg | Privilegie-eskalering — nya appen har inga admin-verktyg |
| Rå echo av `summary`/adresser (stored XSS) | Allt escapas via `View::e()` |
| `eval()` på POST-datum (`reserve.php:94`) | Validerad parsning |
| `'Y'` + `'W'` för vecko-id (årsskiftesbugg) | ISO-år `'o'` + `'W'` — veckor runt nyår får korrekt år (kan avvika från legacys visning 29 dec–3 jan) |
| BCC till anställd som aldrig fungerade (`$usr = isset(...)`-buggen) | Inte återskapad; mottagare är jobbEmail |
| Ingen charset på DB-anslutningen + ad-hoc `utf8_encode` | Explicit latin1-anslutning + `View::text()`-normalisering (se DATABASE.md) |

## Medvetet bevarade legacy-beteenden

- Omskick av en vecka vars rader är `is_pending = 1` är tillåtet (anställda
  skickar om efter justeringar); endast helt godkända veckor nekas.
- `markPending` rör inte `modified` (legacy-paritet, `AdminDB.class.php:984`).
- `old_hours` sätts vid första justeringen och bevaras vid efterföljande
  (`Reservation.class.php:1218`); rapporten visar "nya (bokade)".
- Rapporten commitas före mailet; mailfel rullar inte tillbaka statusen
  (`Admin.class.php:332-340`) — men loggas och flaggas i UI.
