# ARCHITECTURE — HemFresh TimeRapport

Fristående webbapp för anställdas (resources) schema och veckotidrapportering.
Ersätter det anställd-vända flödet i legacy `scheduledev` (phpScheduleIt 1.2-fork)
men delar databas med `scheduledev` och `InvoicesDev` — **utan schemaändringar**.

## Miljö

| | |
|---|---|
| URL | `http://localhost/time_raportDev/` |
| PHP | 8.1.25 (`C:\xampp\php`), inga Composer-beroenden |
| DB | MariaDB 10.4.32, `fresh_bokning` (prod: `hemfresh_bokning`) |
| Tidszon | `Europe/Stockholm` sätts i `app/bootstrap.php` (php.ini säger Europe/Berlin) |

## Lager

```
index.php (front controller, ?page=-routing, central CSRF, try/catch)
   └─ app/Controllers/*      HTTP-logik, ingen SQL
        └─ app/Services/*    Affärslogik: veckomatematik, timberäkning, rapportflöde
             └─ app/Repositories/*   All SQL (PDO prepared statements)
                  └─ app/Core/Database (PDO-singleton, charset=latin1)
   └─ app/Views/* renderas via app/Core/View (allt escapas med View::e)
```

Routing är query-string (`index.php?page=week&week=2026-W37`) — medvetet, eftersom
appen ligger i en XAMPP-underkatalog där mod_rewrite/AllowOverride inte kan
förutsättas. Alla icke-entry-PHP-filer inleds med en `TIMERAPPORT`-guard och
`.htaccess` blockerar `app/`, `config/`, `storage/`, `scripts/`, `docs/` som
extra lager.

## Nyckelbegrepp

- **Resource-identitet:** `authenticated_resource_id` = `cal_login.memberid`
  = `cal_resources.machid` (legacy-konvention). Lagras ENBART i sessionen
  (`App\Core\Auth::id()`); läses aldrig från URL. Alla SQL-frågor bär
  `machid = ?`.
- **hourOffset:** legacy visar tider förskjutna med
  `cal_login.timezone − 0` timmar (visning, inte lagring). Replikeras i
  `App\Services\TimeFormatter`; inversen används när justerade tider sparas.
- **Vecka:** ISO-8601 med ISO-år (`date('o')-Wdate('W')`), måndag–söndag.
  Veckointervallet i SQL är måndag 00:00 → söndag 00:00 — exakt samma som
  legacy `MyCalendar`/`DBEngine::get_resource_reservations`.
- **Timmar:** `(endtime − starttime)/60` från råa minutkolumner; veckototal =
  summan. Alltid beräknat server-side.
- **Tidrapporten** är ingen egen tabell: submit = `is_pending = 1` på veckans
  bokningar + HTML-e-post (se LEGACY_MAPPING.md). Historik härleds per vecka
  ur bokningarnas `is_pending`.

## Planerad tid vs faktisk tid (framtida check-in/check-out)

Principen är förberedd men INTE implementerad:

- **Planerad tid** = bokningens `starttime`/`endtime` (röres aldrig av en
  framtida check-in/out-funktion).
- **Faktisk tid** skulle lagras separat. Befintlig databas saknar helt stöd
  (verifierat: inga kolumner för actual/check_in/worked/attendance), så en ny
  **isolerad** tabell krävs den dagen funktionen byggs:

```sql
-- FÖRSLAG, KÖR INTE UTAN GODKÄNNANDE
CREATE TABLE resource_time_entries (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  resource_id     CHAR(16)  NOT NULL,   -- cal_resources.machid
  booking_id      CHAR(16)  NOT NULL,   -- cal_reservations.resid
  check_in_at     DATETIME  NOT NULL,   -- server-timestamp, aldrig klientens
  check_out_at    DATETIME  NULL,
  worked_minutes  INT       NULL,
  status          TINYINT   NOT NULL DEFAULT 0,
  created_at      DATETIME  NOT NULL,
  updated_at      DATETIME  NOT NULL,
  UNIQUE KEY uq_booking_open (booking_id, resource_id, check_in_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Ny tabell = ingen påverkan på scheduledev/InvoicesDev (de känner inte till
den). Dubbel check-in förhindras i applikationslogik + constraint. GPS-kolumner
är medvetet utelämnade (ej aktuellt). Innan detta byggs krävs separat
impact-assessment och godkännande.

## Varför inget ramverk

~30 filer, en tydlig domän, delad legacy-databas och en XAMPP-miljö utan
Composer. Ett ramverk hade gett routing/ORM vi ändå inte kan använda fullt ut
(latin1-blandad data, legacy-konventioner) till priset av deploy-komplexitet.
Strukturen (Controllers/Services/Repositories) gör en senare migrering enkel.
