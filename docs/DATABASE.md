# DATABASE — fresh_bokning (delad, konservativt hanterad)

Databasen delas av tre system. **Denna app gör INGA schemaändringar** —
inga CREATE/ALTER/DROP, inga nya tabeller, inga nya index.

| System | Roll |
|---|---|
| scheduledev (legacy) | Ägare: bokningar, resurser, kunder, admin/godkännande |
| InvoicesDev | Läser bokningsdata för fakturering; egna tabeller `cal_subscriptions`, `cal_subscription_pauses` |
| time_raportDev (denna app) | Läser schema/kund/login; skriver ENDAST rapportstatus + timjustering (samma kolumner som legacy) |

## Tabeller som används

### cal_login — kunder OCH anställdas inloggning
`memberid` char(16) PK, `email`, `password` char(32) (osaltad MD5), `fname`,
`lname`, `phone`, `mobil`, `city`, `address`, `logon_name`, `is_admin`,
`` `keys` `` smallint (**2 = anställd**; reserverat ord — backticka alltid),
`lang`, `timezone` float (styr visningsoffset), `observations`, `timestamp`.
Anställd-konvention: `memberid == cal_resources.machid` (skapad via legacy
`reg_resources.php`). Appen: **read-only** (login-verifiering + kundnamn/adress).

### cal_resources — anställda
`machid` char(16) PK, `name`, `status` char(1) ('a' aktiv / 'u' inaktiv),
`rphone`, `location`, `scheduleid`, `adress`, `birthday`, `fa_name`, `fa_phone`.
Appen: **read-only** (aktiv-filter vid login).

### cal_reservations — bokningar
`resid` char(16) PK, `machid` → resource, `scheduleid`,
`start_date`/`end_date` int (unix-ts vid **midnatt**),
`starttime`/`endtime` int (**minuter efter midnatt**), `created`, `modified`,
`parentid` (återkommande serie), `is_blackout`, **`is_pending`** smallint,
`summary` text (kommentar), `recess`, `attention`, **`old_hours`** float(3,1).
MyISAM ⇒ transaktioner/`FOR UPDATE` är no-ops; atomicitet kommer från
villkorade UPDATE-satser.

**Appens enda skrivningar (identiska med legacys eget resource-flöde):**

| Operation | Kolumner | Legacy-motsvarighet |
|---|---|---|
| Skicka rapport | `is_pending = 1` (villkor `machid=? AND is_pending=0`) | `AdminDB::pending_reservation()` |
| Justera timmar | `starttime, endtime, modified, old_hours` (villkor `machid=? AND is_pending=0`; raden förblir utkast) | `ResDB` UPDATE :333 |

**Låsningsregel (skärpt 2026-09-14, avviker från legacy):**

```
Utkast (0) kan redigeras och skickas.
Inskickat (1) är låst och kan inte skickas igen.
Godkänt (2) är låst och kan inte skickas igen.
```

Endast rader med `is_pending = 0` kan någonsin uppdateras av appen —
villkoret i UPDATE-satsen är den slutliga spärren (atomär per sats på
MyISAM), och submitten kräver att radräkningen exakt matchar antalet
förväntade utkastrader innan rapportmailet skapas (exakt-en-gång).
Veckor med blandade statusar blockeras helt från ny inskickning.
Inga DELETE.

### cal_reservation_users — bokning ↔ kund
`resid`, `memberid`, `owner` (**1 = kunden**), `invited`, `perm_*`.
Appen: **read-only** (JOIN i alla bokningsfrågor).

## is_pending — rapportens statusmaskin

| Värde | Betydelse | Sätts av |
|---|---|---|
| 0 | Utkast / ej rapporterad | default; admin-redigeringar |
| 1 | Inskickad, väntar godkännande | anställd via submit (denna app: endast hela utkastveckor; legacy satte 1 även vid timjustering) |
| 2 | Godkänd / låst | scheduledev admin (`tool=approval` → `approve_res`) |

Vecka lagras inte — den identifieras av bokningarnas `start_date` bucketad
per ISO-vecka (`date('o')-Wdate('W')`). Veckostatus härleds: någon 0 → Utkast;
annars någon 1 → Inskickad; annars Godkänd.

## Kanonisk veckofråga

```sql
SELECT res.…, lg.fname, lg.lname, lg.address, lg.city
FROM cal_reservations res
INNER JOIN cal_reservation_users ru ON ru.resid = res.resid AND ru.owner = 1
INNER JOIN cal_login lg ON lg.memberid = ru.memberid
WHERE res.machid = ?                 -- alltid från autentiserad session
  AND res.start_date >= ?            -- måndag 00:00
  AND res.end_date   <= ?            -- söndag 00:00
  AND res.is_blackout <> 1
ORDER BY res.start_date, res.starttime, lg.fname
```
(Spegel av legacy `DBEngine::get_resource_reservations`, lib/DBEngine.class.php:444.)

## Teckenkodning — verifierat beslut (2026-09-13)

Tabellerna är `latin1_general_ci` med **blandat** innehåll:

| Typ | Exempel (HEX-bevis) |
|---|---|
| Äkta latin1 | `RESETILLÄGG` = `…4C4C C4 4747` (C4 = Ä); `Saltsjöbaden` = `…6A F6 62…` (F6 = ö) |
| UTF-8-i-latin1 | `Kungsklippevägen` = `…76 C3A4 67…` (C3A4 = UTF-8 ä) |
| HTML-entiteter | `&amp; Hanna Löfqvist` lagrat bokstavligt |

**Beslut:** PDO-anslutning `charset=latin1` (rå byte-passthrough — verifierat
RAW MATCH mot `HEX()` för alla provtyper via `scripts/charset_check.php`),
normalisering per sträng i `View::text()`: giltig UTF-8 behålls, annars
Windows-1252→UTF-8, därefter entitetsavkodning. Sidorna serveras som UTF-8.
`utf8mb4`-anslutning är fel: den transkodar bytes och dubbelkodar
UTF-8-i-latin1-raderna ("KungsklippevÃ¤gen").

Kodningen är enbart en läsfråga — appen skriver aldrig text, bara numeriska
kolumner. Mojibake i källdata "lagas" inte i databasen.

## hourOffset — verifierad visningsförskjutning

Legacy visar `starttime/endtime + 60·(cal_login.timezone − 0)` minuter.
Anställda har mestadels `timezone = 1` ⇒ visad tid = lagrad + 1 h (rådata
toppar 08:00; jobben visas 09:00). Denna app replikerar exakt
(`TimeFormatter::displayMinutes`) och inverterar vid sparning av justerade
tider. Lagrade värden justeras aldrig.

## InvoicesDev-samexistens

InvoicesDev läser `cal_reservations/cal_resources/cal_login/cal_reservation_users`
(fakturatimmar = SUM av bokad duration; filtrerar `is_blackout`, inte på
`is_pending`-nivåerna 0/1/2 var för sig) och skriver bara sina egna
`cal_subscriptions`-tabeller. Denna apps skrivningar (`is_pending`, tider vid
justering) är samma mutationer som legacy redan gör idag ⇒ ingen ny påverkan.

## Miljönot

Levande dev-data: `C:\xampp\mysql\data\fresh_bokning` (MariaDB 10.4).
`C:\Xampp5_3\...\bokningdb` är en frusen kopia (jan 2025) — använd inte.
Produktion enligt legacy-config: `hemfresh_bokning`.
