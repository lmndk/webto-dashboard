# Webto Dashboard

Kontrolpanelet til kundens website. Ren PHP, CSS og lidt vanilla JS. Intet byggetrin. Alle ser det samme
kontrolpanel; WordPress' rettigheder styrer, hvad hvert kort viser.

## Det ser kunden
- **Kontrolpanel** (WordPress' egen adresse `index.php` - menupunktet, undermenuen og links virker som før; WordPress'
  widgets bruges ikke): et kort pr. indholdstype (Sider, Indlæg og alle typer med eget menupunkt, også nye) med antal
  udgivne, kladder/planlagte/venter, de tre seneste ændringer og "Tilføj ...". Desuden **Henvendelser** fra Fluent Forms
  (kun når det er aktivt), **Billeder og filer** (de seneste og HappyFiles' mapper) og kort fra andre plugins.
- Til højre (fra 1280 px; ellers nederst): **Skriv til Webto** og **Vejledning** (kortet kommer fra Webto Vejledning).
  Alt er ét gitter, så hjælpekortene flugter med rækkerne af indholdskort.
- **Support** i den sorte bjælke: på sitet et panel (`<dialog>`) med "Spørgsmål til denne side" eller "Generelt
  spørgsmål"; i wp-admin fører den til kortet i kontrolpanelet.
- **Kontrolpanel -> Beskeder til Webto**: alle beskeder (egne; administratorer ser alle) med status, de automatiske
  oplysninger og vedhæftningen.

## Support
- Til `support@webto.dk` (filter `webto_dashboard_support_til`) med kundens mail som Reply-To. Emnet:
  `Support fra <site>: <starten af beskeden>`.
- Med automatisk: hjemmeside, bruger (navn, mail, rolle), sendt fra (kontrolpanel/panel), siden (titel + adresse),
  browser, skærm (vindue, skærm, zoom), WordPress, PHP, tema og aktive plugins med versioner, tidspunkt.
- Én vedhæftning: jpg/png/gif/webp/pdf, højst 5 MB, tjekket på indholdet (finfo). Gemmes i
  `uploads/webto-support/<år>/<tilfældigt navn>` (spærret for direkte adgang) og hentes via wp-admin
  (`admin-post.php?action=webto_dashboard_fil`, nonce + rettighed).
- Gemmes altid som `webto_support` (skjult indholdstype), også når mailen fejler (`_wd_status`, `_wd_fejl`).
- Højst 5 pr. bruger pr. time (filter `webto_dashboard_support_pr_time`).
- Mail-status før afsendelse: temaets spærre (`webto_mail_allowed()`, fx homelab) eller ingen Fluent SMTP-forbindelse
  giver en tydelig besked. På homelab ses den stoppede mail i PHP-loggen (`docker logs wp-ai-skabelon`).
- Én modtager (`admin-post.php?action=webto_dashboard_support`) til begge formularer: JSON, når browseren sender
  `wd_svar=json`, ellers tilbage til kontrolpanelet (virker uden JavaScript).

## Udvidelsespunkter
- `webto_dashboard_tiles`: kort i overblikket. Et kort er `id`, `titel`, `ikon` (dashicons-navn), `link`
  (`url`, `tekst`), `html` (escaped af den, der laver kortet), `omraade` (`indhold` eller `hjaelp`), `raekkefoelge`.
  Webto Vejledning lægger sit kort her (område `hjaelp`, rækkefølge 20; support er 10).
- `webto_dashboard_post_types`, `webto_dashboard_cap_support`, `webto_dashboard_support_til`,
  `webto_dashboard_support_pr_time`, `webto_dashboard_mail_status`.

## Filer
`webto-dashboard.php` (indgang, rettighed, indholdstypen `webto_support`) og `includes/`: `oversigt.php`,
`kontrolpanel.php`, `support.php`, `adminbar.php`. `assets/dashboard.css` (kontrolpanel, beskeder og panelet) og
`assets/support.js`. `skitse/` er den godkendte skitse (2026-10-13) og skal ikke med i en udgivelse.

## Test
`hb/tests/t-dashboard.js` i skabelonens repo (41 tjek). Testen rydder selv op.

## Opdateringer
Fra GitHub via `includes/opdatering.php` (automatisk; kun udgivelsens zip-fil). Kildekoden i skabelonen har markørfilen
`.udvikling` og opdateres aldrig. Udgivelse: `plugins/udgiv.sh webto-dashboard <version>` (se `plugins/README.md`).
