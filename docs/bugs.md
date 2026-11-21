# Bugregister Simplify Hosting

> Elke gefikste bug staat hier, met de test die hem bewaakt (WR-11). Een terugkerende bug is een regressie: zoek
> eerst uit waarom de test hem niet ving. Een rij weghalen mag niet, de test weghalen ook niet. Nieuwe nummers geeft de
> repo-thread zelf met het prefix van deze repo (WR-8); Startvulling 2-11 (V35): de
> bugs die twee keer of vaker terugkwamen, uit CHANGELOG en projectnotities. "Nog geen test" is een open punt voor
> de repo-thread.

| Nr | Datum | Wat zag de gebruiker | Oorzaak | Fix (versie) | Regressietest | Teruggekomen | Regel |
|---|---|---|---|---|---|---|---|
| B9 | 1.0.2 | Instellingspagina scrolt als geheel met Simplify 1.5 | Formulier zonder `s-fit` | 1.0.2 | `tests/panel/smoke.php` stap "settings page renders for a root admin" (controleert `class="s-fit"`) | 0× (patroon met andere add-ons) | P-7 |
| HO-B1 | 21-11 | (Audit vóór stable.) "Upgrade this server" en "Pay invoice" openen een kapotte link (`…&id=`, `/services//upgrade`) bij een server zonder external id, als de betaallink `{external_id}` gebruikt. Zonder en met kern | `Settings::billing()` vulde een lege waarde in; een server die in het paneel zelf is gemaakt heeft geen external id | Unreleased | `tests/panel/smoke.php`: "a billing link with {external_id} is left out for a server without an external id" (rood op c4b674b) | 0× | P-31 |
| HO-B2 | 21-11 | (Audit vóór stable.) De hulptekst bij "Open in host mode" zegt dat admins in de hostmodus starten "until they choose a mode themselves"; in werkelijkheid starten ze op de Admin-pagina, één keer per sessie | Tekst van vóór de Admin-pagina van Simplify (1.5.6, add-on API 5) | Unreleased: hulptekst gelijk aan het gedrag en aan de README, 32 talen | `tests/panel/smoke.php`: "the settings save from the page and reject a link that is not https" (regel over de hulptekst; rood op 9f0c67c) | 0× | P-35 |

## Patronen

Bugs met dezelfde oorzaak, en de regel of check die het hele patroon voorkomt.
- **Pagina scrolt** (B9: kern 6×, Toolkit, Server Maker, Explain, Hosting): P-7. Elke instellingspagina van een add-on
  heeft `s-fit` en een smoke-stap die dat controleert; elk kernscherm een audit `scroll-*`.
- **Updatepad** (B11, kern 1.4.4 en 1.5.1): P-28. Upgradetest met echte 0.7.0-instellingen in `checks.yml`.
- **Testpaneel lijkt niet op het echte paneel** (B15 Egg Updates 1.1.3/1.1.4, kern-audit): P-31. Smoke en audit bootsen
  het echte paneel na (volle nodes, echte eggs, 20+ servers, nep-plugin).
