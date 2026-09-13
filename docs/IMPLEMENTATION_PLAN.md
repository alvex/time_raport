# IMPLEMENTATION_PLAN — status och verifiering

## FAS 1 — Analys ✅ (2026-09-13)

Reverse engineering av scheduledev (login/session/veckofråga/timberäkning/
rapportflöde/godkännande), full databaskarta, InvoicesDev-impact,
miljöinventering (PHP 8.1.25 / MariaDB 10.4 / levande DB = fresh_bokning i
nya XAMPP). Resultat: docs/DATABASE.md, docs/LEGACY_MAPPING.md.

## FAS 2 — Grund ✅

- [x] Skelett: front controller, `?page=`-routing, whitelist, felhantering
- [x] 404-vy, 403 på `app/`+`config/` direktåtkomst, forcerat fel → svensk felsida + loggrad
- [x] **Charset-grind** (`scripts/charset_check.php`): latin1 = RAW MATCH för
      alla provtyper; `View::text()` normaliserar korrekt. Beslut i DATABASE.md.
- [x] Database/Session/Csrf/Auth/rate-limit + login/logout
- [x] HTTP-verifierat: POST utan CSRF → 403; fel lösenord ×5 → enhetligt fel;
      försök 6 → lockout; oautentiserad `?page=dashboard` → 302 till login
- [x] Datakontroller: 15 aktiva anställda, inga dubblett-e-postadresser,
      timezone-värden är hela timmar (0/1)

## FAS 3 — Schema ✅

- [x] Repositories + Services + TimeFormatter
- [x] `scripts/parity_check.php`: unit-asserts (offsetmatte, midnatts-wrap,
      timformat, ISO-veckor inkl. 2026-W53→2027-W01 och 2026-W01→2025-W52) +
      live-veckolista (verifierad för Katya Gruz 2026-W37: 3 jobb, 11 h,
      korrekt avkodad svenska)
- [x] Dashboard, veckovy, jobbdetalj, timjusteringsformulär
- [x] CLI-smoke-render av samtliga vyer utan fel

## FAS 4 — Tidrapport ✅

- [x] Preview → bekräfta (tvåstegsknapp) → submit → historik (12 veckor)
- [x] Skrivtest mot offerbokning (Alvaro `sc14f38cc5bdc922`, 2012-W40,
      exakt före-tillstånd återställt efteråt) — samtliga PASS:
      fel machid = 0 rader; timjustering skriver tider+`is_pending=1` och
      bevarar `old_hours`; submit ok + mail till `storage/mail/`; **(ändrat
      2026-09-14: omskick av inskickad vecka nekas numera — se
      DATABASE.md:s låsningsregel och locking-testsviten)**; godkänd rad
      kan inte tidjusteras; återställning byte-exakt
- [x] Rapportmail verifierat: ämne "\<Namn\> Tid Redovisning!", tabell med
      datum/kund/adress/tid/timmar "3 (4)"/kommentar/total

## FAS 5 — Härdning + docs ✅

- [x] Säkerhetschecklista (SECURITY.md) genomgången mot koden
- [x] Dokumentation skriven
- [ ] **Användarsteg:** logga in med riktiga anställd-uppgifter och gå igenom
      flödet i browser (särskilt: visade tider = legacys visade tider för en
      anställd med `timezone=1`)
- [ ] **Användarsteg:** aktivera `mail_mode='mail'` först efter SMTP-test

## FAS 6 — Check-in/check-out ⏸ (byggs EJ utan godkännande)

Endast förslag i ARCHITECTURE.md (`resource_time_entries`, SQL märkt
"KÖR INTE UTAN GODKÄNNANDE"). Planerad tid röres aldrig; server-timestamps;
ingen GPS.

## Kvarstående verifiering att göra med riktig användare

1. Login med riktigt konto (ingen aktiv anställd har defaultlösenordet, och
   appen får inte skriva i `cal_login` — därför kan lyckad-login bara testas
   med riktiga uppgifter).
2. Visuell mobilkontroll 360–430 px.
3. En riktig veckas submit + godkännande i scheduledev-admin (gamla miljön)
   → historiken visar Godkänd.
4. InvoicesDev-spot-check efter submit (fakturatimmar oförändrade).

## Kommandon

```
C:\xampp\php\php.exe scripts\charset_check.php
C:\xampp\php\php.exe -d zend.assertions=1 scripts\parity_check.php <machid|namn> [2026-W37]
```
