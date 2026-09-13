# HemFresh TimeRapport

Fristående, mobilanpassad webbapp för anställdas veckoschema och
tidrapportering. Ersätter det anställd-vända flödet i legacy `scheduledev`
men återanvänder samma databas (`fresh_bokning`) utan schemaändringar.

- **URL:** http://localhost/time_raportDev/
- **Miljö:** PHP 8.1 (XAMPP), MariaDB 10.4, inga beroenden/byggsteg
- **Flöde:** Login → Dashboard → Veckoschema → Jobbdetalj (justera tider) →
  Tidrapport-preview → Skicka för godkännande → Historik
- Godkännande sker även fortsättningsvis i scheduledevs admin
  (`is_pending`-statusmaskinen delas).

## Kom igång

1. Kopiera `config/config.example.php` → `config/config.php` vid behov
   (defaults matchar lokal XAMPP).
2. Öppna http://localhost/time_raportDev/ och logga in med anställdens
   e-post + lösenord (samma konton som i scheduledev).
3. Rapportmail hamnar i `storage/mail/` tills `mail_mode` sätts till `'mail'`.

## Dokumentation

| Fil | Innehåll |
|---|---|
| `docs/ARCHITECTURE.md` | Lager, routing, beslut, framtida check-in/out-förslag |
| `docs/LEGACY_MAPPING.md` | Exakt mappning legacy-fil:rad → ny kod, medvetna avvikelser |
| `docs/DATABASE.md` | Tabeller, is_pending-statusmaskin, charset- och hourOffset-bevis |
| `docs/SECURITY.md` | Skyddsmatris, lösenordsmigreringsförslag |
| `docs/IMPLEMENTATION_PLAN.md` | Fasstatus, verifieringar, kvarstående användarsteg |

## Verifieringsscript (CLI, read-only)

```
C:\xampp\php\php.exe scripts\charset_check.php
C:\xampp\php\php.exe -d zend.assertions=1 scripts\parity_check.php <machid|namn> [2026-W37]
```
