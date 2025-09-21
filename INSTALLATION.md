# Installation Guide - Anonymous Dealer Locator Plugin

## 📋 Hurtig Start

### 1. Upload til WordPress

1. **Zip hele plugin mappen**
   ```bash
   cd /path/to/your/plugins
   zip -r anonymous-dealer-locator.zip anonymous-dealer-locator-plugin/
   ```

2. **Upload via WordPress Admin**
   - Gå til: `WordPress Admin → Plugins → Add New → Upload Plugin`
   - Vælg `anonymous-dealer-locator.zip`
   - Klik "Install Now"

3. **Eller upload via FTP**
   - Upload hele `anonymous-dealer-locator-plugin` mappen til `/wp-content/plugins/`
   - Omdøb mappen til `anonymous-dealer-locator`

### 2. Aktiver Plugin

1. Gå til `WordPress Admin → Plugins`
2. Find "Anonymous Dealer Locator" og klik "Activate"
3. Du vil se en ny menu "Dealer Locator" i admin sidebar

### 3. Test Installation (Valgfrit)

**VIGTIGT: Kun til test-miljøer!**

1. Gå til: `yoursite.com/wp-content/plugins/anonymous-dealer-locator/demo-data.php`
2. Dette vil tilføje 5 test-forhandlere i danske byer
3. **SLET `demo-data.php` filen bagefter af sikkerhedsmæssige årsager**

### 4. Tilføj Dit Første Kort

1. Opret en ny side: `Pages → Add New`
2. Tilføj shortcoden: `[dealer_locator]`
3. Publicer siden og se resultatet

---

## ⚙️ Detaljeret Konfiguration

### Administrator Setup

#### 1. Tilføj Forhandlere

1. **Gå til**: `Admin → Dealer Locator → Tilføj Forhandler`

2. **Udfyld formularen**:
   - **Navn**: Forhandlerens navn (vises kun i admin)
   - **Email**: Modtager kundehenvendelser
   - **Telefon**: Valgfrit
   - **Adresse**: Fuld adresse for GPS lokalisering
   - **By/Postnummer**: Bruges til søgning

3. **Hent koordinater automatisk**:
   - Klik "Hent Koordinater Automatisk"
   - Koordinaterne udfyldes automatisk
   - Verificer at de er korrekte

4. **Gem forhandleren**

#### 2. Administrer Forhandlere

- **Vis alle**: `Dealer Locator → Alle Forhandlere`
- **Rediger**: Klik "Rediger" ved den relevante forhandler
- **Slet**: Klik "Slet" (bekræft handling)
- **Status**: Skift mellem "Aktiv" og "Inaktiv"

### Frontend Implementation

#### Basis Shortcode
```
[dealer_locator]
```

#### Avanceret Shortcode
```
[dealer_locator 
    height="600px" 
    zoom="10" 
    center_lat="55.6761" 
    center_lng="12.5683" 
    search_radius="25" 
    show_search="true"]
```

**Parametre forklaring**:
- `height`: Kortets højde (CSS format)
- `zoom`: Zoom niveau 1-18 (højere = tættere)
- `center_lat/lng`: Start koordinater
- `search_radius`: Søgeradius i kilometer
- `show_search`: Vis søgeboks (true/false)

### Mapbox API Setup (Anbefalet)

Plugin bruger en demo API nøgle som kan have begrænsninger.

#### 1. Opret Gratis Mapbox Konto
1. Gå til: https://account.mapbox.com/auth/signup/
2. Opret konto
3. Gå til "Access Tokens"
4. Kopier din "Default public token"

#### 2. Opdater Plugin
1. Rediger: `assets/js/frontend.js`
2. Find linjen: `mapboxgl.accessToken = 'pk.eyJ1Ijoi...'`
3. Erstat med din token: `mapboxgl.accessToken = 'pk.YOUR_TOKEN_HERE'`

---

## 🎨 Styling og Tilpasning

### CSS Tilpasning

Tilføj i dit tema's `style.css` eller via `Appearance → Customize → Additional CSS`:

```css
/* Tilpas søgeboks farver */
.adl-search-box button {
    background-color: #your-brand-color !important;
}

/* Tilpas modal styling */
.adl-modal-content {
    border-radius: 15px;
    border: 3px solid #your-brand-color;
}

/* Tilpas kort højde på mobil */
@media (max-width: 768px) {
    .adl-map {
        height: 300px !important;
    }
}
```

### JavaScript Customization

Lyt til plugin events:

```javascript
// Når kort er indlæst
document.addEventListener('DOMContentLoaded', function() {
    if (typeof ADL !== 'undefined') {
        // Tilføj din custom logik her
        console.log('Dealer Locator loaded');
    }
});
```

---

## 🔧 Fejlfinding

### Kort vises ikke

1. **Tjek console errors**: Åbn browser dev tools (F12)
2. **Internetforbindelse**: Kontroller at siden kan loade eksterne scripts
3. **Shortcode**: Verificer at `[dealer_locator]` er korrekt stavede
4. **Plugin aktivering**: Kontroller at plugin er aktiveret

### Koordinater kan ikke hentes

1. **Adresse format**: Brug fulde adresser (gade, postnummer, by)
2. **Internet forbindelse**: Geocoding kræver internet adgang
3. **Manually koordinater**: Hent koordinater fra Google Maps og indtast manuelt

### Emails sendes ikke

1. **Email konfiguration**: Kontroller WordPress email indstillinger
2. **SMTP plugin**: Overvej at installere et SMTP plugin
3. **Spam folder**: Tjek om emails ender i spam
4. **Server logs**: Tjek WordPress/server logs for fejl

### Database problemer

1. **Genaktiver plugin**: Deaktiver og aktiver plugin igen
2. **Database repair**: Kør WordPress database repair tool
3. **Permissions**: Kontroller database bruger permissions

---

## 📞 Support

### Før du beder om hjælp

1. **Tjek system requirements**:
   - WordPress 5.0+
   - PHP 7.4+
   - Moderne browser

2. **Test på standard tema**: 
   - Skift midlertidigt til Twenty Twenty-One tema
   - Test om problemet stadig opstår

3. **Deaktiver andre plugins**:
   - Deaktiver alle andre plugins
   - Test om konflikter er årsagen

### Debug Information

Hvis du har brug for support, inkluder:

- WordPress version
- PHP version
- Aktive plugins liste
- Tema navn og version
- Console error meddelelser
- Specifikke fejl beskrivelse

---

## 🔐 Sikkerhed

### Produktionsmiljø

1. **Slet demo files**: Fjern `demo-data.php` efter test
2. **Verificer permissions**: Kontroller at kun admin kan tilgå admin sider
3. **Update regelmæssigt**: Hold WordPress og plugins opdateret

### GDPR Compliance

- Plugin gemmer kun forhandleroplysninger
- Kunder data gemmes IKKE - kun sendes via email
- Forhandlere er ansvarlige for at behandle kundeopysninger korrekt

---

## 📝 Vedligeholdelse

### Backup

Backup følgende før opdateringer:
- `/wp-content/plugins/anonymous-dealer-locator/`
- Database tabeller: `wp_adl_dealers`

### Opdateringer

1. Backup eksisterende plugin
2. Upload ny version
3. Test funktionalitet
4. Gendan backup hvis problemer opstår

---

**🎉 Tillykke! Dit Anonymous Dealer Locator Plugin er nu klar til brug.**
