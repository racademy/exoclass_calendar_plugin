# ExoClass Calendar Plugin - Versijų istorija

## [1.2.5] - 2025-01-07
### Testavimas
- Test release - tikrinama ar veikia automatinis atnaujinimo mechanizmas
- Testuojama GitHub release assets ZIP struktūra

## [1.2.4] - 2025-01-07
### Pataisyta
- WordPress plugin atnaujinimo folder struktūros problema
- Plugin dabar teisingai išlaiko 'exoclass-calendar' folder pavadinimą po atnaujinimų
- GitHub workflow sukuria ZIP su teisinga plugin folder struktūra
- Atnaujinimo mechanizmas dabar naudoja release assets vietoj zipball URLs

## [1.2.3] - 2025-08-06
### Pataisyta
- GitHub ZIP failų ekstraktavimo problema WordPress atnaujinimuose
- Pagerinta folder detection logika GitHub release'ams
- Pridėtas debug logging atnaujinimų procesui

## [1.2.2] - 2025-08-05
### Pataisyta
- Dinaminis embed URL generavimas iš admin API nustatymų
- Registracijos mygtukas dabar naudoja konfigūruojamą API URL vietoj hardcoded
- Palaiko test.api.exoclass.com ir api.exoclass.com domenus

## [1.2.1] - 2025-08-05
### Pridėta
- Event details modal su išsamiu informacijos rodymu
- Teacher dropdown filtras kalendoriuje
- Teacher informacijos rodymas event modal'e
- "Read more" funkcionalumas teacher aprašymui
- "Read more" funkcionalumas event aprašymui su YouTube iframe palaikymu
- Dinaminis iframe aukščio skaičiavimas modal'e
- Address rodymas vietoj dance hall pavadinimo location dropdown'e
- Address trumpinimas (pašalintas šalis ir pašto kodas, palikta miesto pavadinimas)

### Pakeista
- Event click elgsena: dabar atidaro modal vietoj tiesioginio nukreipimo
- Teacher aprašymo stilius: pašalintas background ir border
- Teacher aprašymo pozicija: dabar rodomas po event aprašymo
- Location dropdown: dabar rodo adresą vietoj dance hall pavadinimo
- Teacher dropdown: pridėtas naujas filtras kalendoriuje

### Pataisyta
- Modal close button funkcionalumas
- Teacher aprašymo "read more" mygtuko pozicija
- Dinaminis iframe aukščio skaičiavimas modal'e

## [1.2.0] - 2024-01-16
### Pridėta
- Pilna GitHub releases integracija automatiniams atnaujinimams
- GitHub API integracija su WordPress update sistema
- Automatinis versijų tikrinimas kas 12 valandų
- GitHub Actions workflow automatiniam release kūrimui
- Atnaujinimų debug utility testavimui

### Pakeista
- Perkurtas update.php failas su GitHub API palaikymu
- Atnaujinta dokumentacija su aiškiomis instrukcijomis
- Optimizuotas atnaujinimų cache mechanizmas

### Pašalinta
- Nebereikalingi release.sh ir UPDATE-GUIDE.md failai

## [1.1.0] - 2024-01-XX
### Pridėta
- Automatinių atnaujinimų sistema
- Versijų valdymo mechanizmas
- Changelog failas

### Pakeista
- Versijos numerio nuoseklumas
- Atnaujinta plugin struktūra

### Pataisyta
- Versijos konstantos neatitikimas

## [1.0.0] - Pradinis leidimas
### Pridėta
- Pagrindinis kalendoriaus funkcionalumas
- ExoClass API integracija
- FullCalendar.js biblioteka
- Filtravimo galimybės
- Lietuvių kalbos lokalizacija
- WordPress admin sąsaja
- Responsive dizainas

---

## Versijų numeravimo taisyklės

Naudojame [Semantic Versioning](https://semver.org/):

- **MAJOR.MINOR.PATCH** (pvz., 1.0.0)
- **MAJOR**: Nesuderinami pakeitimai
- **MINOR**: Naujos funkcijos (suderinamos)
- **PATCH**: Klaidų taisymai