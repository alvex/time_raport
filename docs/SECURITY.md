# SECURITY — HemFresh TimeRapport

## Skydd → kodplats

| Skydd | Implementation |
|---|---|
| SQL-injektion | Enbart PDO prepared statements (`app/Repositories/*`); IN-listor byggs som `?`-platshållare |
| IDOR / manipulerat resource-/booking-id | Resource-id ENBART från session (`Core\Auth::id()`); varje fråga bär `machid = ?` — en kollegas `resid` ger 404 |
| XSS | All utdata genom `View::e()` (htmlspecialchars ENT_QUOTES); legacy-datan entitetsavkodas först i `View::text()` och escapas sedan |
| CSRF | Sessionstoken, valideras centralt för ALLA POST i `index.php` |
| Dubbel-submit (rapport) | Engångstoken (`Csrf::issueOnce/consumeOnce`) + villkorad `UPDATE … is_pending < 2`; JS-knappspärr som bekvämlighet |
| Session fixation/hijacking | `session_regenerate_id(true)` vid login, `use_strict_mode`, HttpOnly, SameSite=Lax, eget cookienamn+path (`TIMERAPPORT_SID`, `/time_raportDev/`) |
| Brute force | Filbaserad limiter per (IP,e-post): 5 fel → 15 min lås (`storage/ratelimit/`); enhetligt felmeddelande |
| Källkodsläckage | `TIMERAPPORT`-guard i varje icke-entry-fil; `.htaccess`-deny som andra lager; loggar heter `*.log.php` med inledande `<?php exit;` |
| Felhantering | `display_errors=0` forceras; exceptions → `storage/logs/` + generisk svensk felsida; aldrig SQL/stacktrace till användaren |
| Godkänd data orörbar | `is_pending < 2` i båda UPDATE-satserna; inga DELETE; endast kolumnerna `is_pending/starttime/endtime/modified/old_hours` skrivs |
| Serverstyrda värden | Timmar/totaler räknas alltid om server-side; tidsinput valideras (HH:MM, 5-min-steg, start<slut); `modified` = serverns `time()` |

## Lösenord — känd begränsning (v1)

`cal_login.password` är `char(32)` med **osaltad MD5** (legacy
`DBEngine::make_password`). Denna app verifierar `md5($input)` med
`hash_equals` — nödvändigt för att återanvända befintliga konton utan att
röra tabellen, men MD5 är knäckbart offline om databasen läcker.

**Migreringsförslag (INTE implementerat — kräver godkännande):**

1. **Alt A (kräver ALTER):** utöka `password` till `varchar(255)`; verifiera
   MD5 → rehasha till `password_hash()` vid lyckad login. Påverkar
   scheduledev: dess login jämför exakt MD5-sträng ⇒ konton som rehashats
   kan inte längre logga in i legacy. Fungerar först när legacy pensionerats.
2. **Alt B (ingen schemaändring i delade tabeller):** ny isolerad tabell
   `time_raport_credentials(memberid, password_hash, updated_at)`; denna app
   verifierar mot den när rad finns, annars MD5-fallback + skuggmigrering.
   Legacy opåverkat. Rekommenderas om båda systemen ska leva parallellt länge.

Övriga kända legacy-hål (cookie-autologin med rå memberid, default-lösenordet
`sommar`, admin-eskalering via `$_SESSION['resources']`) finns kvar i
scheduledev och är utanför denna apps kontroll — notera att de underminerar
totalskyddet så länge legacy är nåbart publikt.

## Rapportmail

`mail_mode = 'log'` (default): mail skrivs till `storage/mail/*.html` och
skickas inte. Sätt `'mail'` i `config/config.php` FÖRST när SMTP/sendmail är
verifierat i XAMPP och ett testutskick till testadress granskats. Innehållet
byggs server-side och escapas (legacy skickade oescapad användartext).

## Kända kvarvarande begränsningar

- MyISAM ⇒ ingen äkta transaktionalitet; skyddet är villkorade UPDATE-satser
  (atomära per sats) + engångstoken.
- Rate-limit är fil-baserad per servernod (OK för en XAMPP-värd).
- HTTPS förutsätts hanteras av värdmiljön; cookien blir `Secure` automatiskt
  när HTTPS används.
