# ExoClass Calendar Plugin - Versijų istorija

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