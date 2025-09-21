# Anonymous Dealer Locator Plugin

Et WordPress plugin der viser forhandlere på et kort uden at afsløre deres navne, med sikker kontaktformular funktionalitet.

## Funktioner

- **Anonym kortvisning**: Viser forhandlere som markører på kort uden at afsløre deres navne
- **Sikker kontakt**: Kunder kan kontakte forhandlere gennem en formular uden at se konkurrenters oplysninger
- **Geografisk søgning**: Søg efter forhandlere baseret på adresse eller postnummer
- **Admin interface**: Nem administration af forhandlere
- **Responsive design**: Fungerer på alle enheder
- **Email notifikationer**: Automatisk videreisendelse af kundehenvendelser

## Installation

1. Upload plugin filerne til `/wp-content/plugins/anonymous-dealer-locator/` mappen
2. Aktiver plugin gennem 'Plugins' menuen i WordPress
3. Gå til 'Dealer Locator' i admin menuen for at tilføje forhandlere

## Brug

### Administration

1. **Tilføj forhandlere**: Gå til *Dealer Locator > Tilføj Forhandler*
   - Udfyld navn, email, telefon og adresse
   - Klik "Hent Koordinater" for automatisk GPS lokalisering
   - Gem forhandleren

2. **Administrer forhandlere**: Gå til *Dealer Locator > Alle Forhandlere*
   - Se oversigt over alle forhandlere
   - Rediger eller slet forhandlere
   - Skift status (aktiv/inaktiv)

### Frontend visning

Brug shortcoden `[dealer_locator]` på en side eller indlæg for at vise kortet.

#### Shortcode parametre:

```
[dealer_locator height="500px" zoom="8" center_lat="55.6761" center_lng="12.5683" search_radius="50" show_search="true"]
```

**Parametre:**
- `height`: Kortets højde (standard: 500px)
- `zoom`: Initial zoom niveau (standard: 8)
- `center_lat`: Startposition breddegrad (standard: København)
- `center_lng`: Startposition længdegrad (standard: København)
- `search_radius`: Søgeradius i kilometer (standard: 50)
- `show_search`: Vis søgeboks (standard: true)

### Kundeflow

1. **Søgning**: Kunden indtaster adresse eller postnummer i søgefeltet
2. **Kortvisning**: Kortet viser anonyme markører for forhandlere i nærheden
3. **Kontakt**: Kunden klikker på en markør og udfylder kontaktformular
4. **Email**: Forhandleren modtager email med kundens oplysninger og besked

## Tekniske krav

- WordPress 5.0 eller nyere
- PHP 7.4 eller nyere
- Internetforbindelse (til kort og geocoding)

## Kort og geocoding

Plugin bruger:
- **Mapbox GL JS** til kortvisning (gratis)
- **OpenStreetMap Nominatim** til geocoding (gratis)

For bedre performance kan du:
1. Oprette gratis Mapbox konto og få din egen API nøgle
2. Erstatte demo API nøglen i `assets/js/frontend.js`

## Sikkerhed og privacy

- **Anonymitet**: Forhandlernavne vises aldrig på kortet
- **Beskyttelse**: Kun ID og koordinater sendes til frontend
- **Kontaktbeskyttelse**: Kun den relevante forhandler modtager kundens oplysninger
- **Nonce verification**: Alle AJAX requests er sikret med WordPress nonces
- **Input sanitization**: Al brugerinput saniteres før behandling

## Tilpasning

### CSS customization

Tilføj custom CSS i dit tema for at tilpasse udseendet:

```css
/* Tilpas søgeboks */
.adl-search-box input {
    border-color: #dein-farve;
}

/* Tilpas knapper */
.adl-submit-btn {
    background-color: #din-brand-farve;
}

/* Tilpas modal */
.adl-modal-content {
    border-radius: 15px;
}
```

### JavaScript hooks

Plugin tilbyder JavaScript events du kan lytte til:

```javascript
// Når kort er indlæst
document.addEventListener('adl_map_loaded', function(e) {
    console.log('Kort indlæst', e.detail);
});

// Når kontakt modal åbnes
document.addEventListener('adl_contact_opened', function(e) {
    console.log('Kontakt modal åbnet for dealer', e.detail.dealerId);
});
```

## Support og bidrag

For support eller feature requests, opret et issue på GitHub.

## Licens

GPL v2 eller nyere

---

## Changelog

### Version 1.0.0
- Initial release
- Basis kort funktionalitet
- Admin interface
- Kontaktformular system
- Email notifikationer
- Responsive design
