# Webto Dashboard

Kontrolpanelet til kundens website. Ren PHP, CSS og lidt vanilla JS. Intet byggetrin. Alle ser det samme
kontrolpanel; WordPress' rettigheder styrer, hvad hvert kort viser.

## Det ser kunden
- **Kontrolpanel** (WordPress' egen adresse `index.php` - menupunktet, undermenuen og links virker som før; WordPress'
  widgets bruges ikke): et kort pr. indholdstype (Sider, Indlæg og alle typer med eget menupunkt, også nye) med antal
  udgivne, kladder/planlagte/venter, de tre seneste ændringer og "Tilføj ...". Desuden **Henvendelser** fra Fluent Forms
  (kun når det er aktivt), **Billeder og filer** (de seneste og HappyFiles' mapper) og kort fra andre plugins.
- **Toppen** (Lasse 2026-10-13, som Windows 11's låseskærm): et flot billede, der skifter hver hele time, med
  "Velkommen til dit kontrolpanel", hilsenen, en glasknap "Se hjemmesiden" og et ur med dato i sitets tidszone. Billedet
  vælges som timer siden 1970 (UTC) modulo antallet, så ALLE installationer viser samme billede i samme time; siden skifter
  selv ved timeskiftet (`assets/top.js`, serverens ur). Billederne: `assets/baggrunde/<nr>.webp` (2000 px) og
  `<nr>-1000.webp` (mobil), WebP q78 uden metadata; en anden liste via filteret `webto_dashboard_baggrunde`.
- **Genveje** under toppen (Lasse 2026-10-14), altid én række - er der ikke plads, glider den (pile og blød kant på
  computer, swipe på mobil), aldrig to linjer: **Fortsæt hvor du slap** (pr. bruger: det, brugeren sidst har gemt -
  `_edit_last`, ellers det nyeste, brugeren selv har lavet; skjult, når der intet er; et lille kryds skjuler den, til
  brugeren gemmer noget andet - user meta `webto_dashboard_fortsaet_skjult` = post-id), **Sider**, **Indlæg** og sitets
  egne typer (som kortene) med "+" til en ny, **Billeder og filer** (+ upload), **Brugere** (+ ny bruger),
  **Vejledning** (fra Webto Vejledning), **Menuer** og **Plugins** (kun Webto). Hver genvej kun for dem, der må bruge den.
- Kontrolpanelet fylder hele bredden; fra 1800 px tre kolonner med indhold.
- Til højre (fra 1280 px; ellers nederst): **Skriv til Webto** og **Vejledning** (kortet kommer fra Webto Vejledning).
  Alt er ét gitter, så hjælpekortene flugter med rækkerne af indholdskort.
- **Support** i den sorte bjælke: på sitet et panel (`<dialog>`) med "Spørgsmål til denne side" eller "Generelt
  spørgsmål"; i wp-admin fører den til kortet i kontrolpanelet.
- **Kontrolpanel -> Beskeder til Webto**: alle beskeder (egne; administratorer ser alle) med status, de automatiske
  oplysninger og vedhæftningen.

## Kun for Webto: menupunktet "Webto" (Lasse 2026-10-14)
Ses kun af Webto-administratorer (`webto_dashboard_is_webto()`: @webto.dk-mail, eller alle på et udviklingssite).
Kunden ser hverken menupunktet eller siderne, heller ikke som administrator. Ikonet er W'et fra Webtos logo.
- **Synlighed** (`admin.php?page=webto`): alle plugins, punkter i **WordPress menu** (med underpunkter, i menuens
  rækkefølge) og i **Admin bar** (fra wp-admin og sitet; et punkt kommer på listen, første gang Webto har set det). Pr. punkt:
  **Vis**, **Skjul for kunder** eller **Skjul for alle** (ikke plugins). Skjult for kunder = kunden ser det ikke, et
  menupunkt (og dets underpunkter, også editoren for en skjult indholdstype) er spærret via adressen (403), og et
  skjult plugin kan ikke slås fra, slettes eller opdateres. Skjul for alle = heller ikke i Webtos menu/bjælke (Webto
  kan stadig åbne siden). Genveje til skjulte sider vises ikke. Kontrolpanelet, Webto og brugerens egen menu kan ikke
  skjules.
  Øverst står genveje til afsnittene (faste, mens man ruller) med antallet af skjulte punkter; Gem står fast nederst.
- **Blokke** (fjerde afsnit): alle blokke, der kan indsættes (PHP's og dem, der kun findes i editoren - husket, når Webto
  åbner editoren, option `webto_dashboard_blokke_set`; under-blokke styres gennem forælderen). En skjult blok forsvinder
  kun fra blokvælgeren (`supports.inserter = false` via `assets/blokke.js`, indlæst før blokkene registreres): blokke på
  siderne og blokgrupper med den virker stadig. Standard: kunden får ikke to udgaver af samme blok, men den, temaet har
  stylet - Kadences Række-layout/Sektion, Knapper (Adv), Harmonika, Faner og Ikon; WordPress' Overskrift, Afsnit,
  Billede, Tabel, Galleri, Afstandsstykke, Søg, Medie & tekst; temaets Hero; Fluent Forms. Desuden skjult: skabelonblokke
  (titel, dato, kommentarer, logo, navigation ...), Tilpasset HTML, Klassisk, Shortcode, widgets, Kadences formularer,
  sidehoved/navigation og udviklerblokke, SEOPress' gamle FAQ, Sitemap og Preferred Sources.
  Varianter (fx **Indlejr** -> YouTube, Vimeo, X) står som underpunkter med nøglen `blok/variant` (`core/embed/youtube`);
  en skjult variant mister kun pladsen i blokvælgeren. Standard: de sjældne indlejringer (Animoto, Cloudup, Scribd,
  Wolfram ...) er skjult for kunder; YouTube, Vimeo, X, Bluesky, Spotify, SoundCloud, TikTok og Pinterest vises.
- **Standard på alle sites** (`webto_dashboard_visibility_defaults()`, filter `webto_dashboard_synlighed_standard`):
  plugins Novamira, Novamira Pro, FluentSnippets, Webto Dashboard og Webto Vejledning skjult for kunder; menuen
  Novamira, FluentSnippets og ACF skjult for kunder; bjælkens Novamira skjult for kunder og Fluent Forms for alle.
  Sitets egne valg gemmes som forskellen fra standarden (option `webto_dashboard_synlighed`), så en ny standard i en
  opdatering slår igennem. **Nulstil til standard** sletter sitets valg.
- **Se som kunde** (knap på siden): wp-admin og sitet, som kunden ser dem (user meta `webto_dashboard_se_som_kunde`),
  til den røde knap i bjælken slår det fra. `webto_dashboard_sees_as_webto()` er falsk imens (også Webto Vejledning
  via `webto_vejledning_er_webto`), men Webto-siden kan stadig åbnes.

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
- `webto_dashboard_genveje`: genvejene. En genvej er `id`, `titel`, `ikon`, `url`, `ny` (`url`, `tekst` - "+",
  valgfri), `under` og `beskrivelse` (valgfri), `variant`, `skjul` og `raekkefoelge` (Fortsæt 0, typerne 10+, medier 30,
  brugere 50, Webto Vejledning 60, menuer 80, plugins 90).
- `webto_dashboard_is_webto()`: er brugeren fra Webto (@webto.dk-mail, eller alle på et udviklingssite)? Filtre
  `webto_dashboard_webto_domaener` og `webto_dashboard_udvikling`; konstanten `WEBTO_DASHBOARD_WEBTO` vinder.
  `webto_dashboard_sees_as_webto()` (falsk under "Se som kunde") er den, der skal bruges til det, kunden ikke må se.
- `webto_dashboard_synlighed_standard` (standardreglerne), `webto_dashboard_url_blocked( $url )` (er en side skjult for
  brugeren?), `webto_dashboard_is_hidden( $gruppe, $noegle )`.
- `webto_dashboard_post_types`, `webto_dashboard_cap_support`, `webto_dashboard_support_til`,
  `webto_dashboard_support_pr_time`, `webto_dashboard_mail_status`.

## Filer
`webto-dashboard.php` (indgang, rettighed, indholdstypen `webto_support`) og `includes/`: `oversigt.php`,
`kontrolpanel.php`, `genveje.php`, `webto.php` (menupunktet Webto, Se som kunde), `synlighed.php`, `support.php`,
`adminbar.php`. `assets/dashboard.css` (kontrolpanel, beskeder og
panelet), `assets/support.js`, `assets/top.js` (billede og ur), `assets/genveje.js` (pilene) og `assets/blokke.js` (blokke i editoren). `skitse/` er den godkendte skitse (2026-10-13) og skal ikke med i en udgivelse.

## Test
`hb/tests/t-dashboard.js` (44 tjek), `t-dashboard-top.js` (14), `t-dashboard-genveje.js` (24), `t-webto-synlighed.js`
(32; ændrer sitets valg - kør ikke, mens nogen bruger siden) og `t-webto-blokke.js` (13; ændrer intet) i skabelonens repo.
Testene rydder selv op.

## Opdateringer
Fra GitHub via `includes/opdatering.php` (automatisk; kun udgivelsens zip-fil). Kildekoden i skabelonen har markørfilen
`.udvikling` og opdateres aldrig. Udgivelse: `plugins/udgiv.sh webto-dashboard <version>` (se `plugins/README.md`).
