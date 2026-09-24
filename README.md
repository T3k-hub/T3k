# T3K Website

- `public/index.html` = T3K Startseite, mit **T3K BEWERBUNG** über dem/bei T3K MEMBER.
- `public/members.html` = Mitgliederseite, inhaltlich unverändert aus der gelieferten Datei.
- `public/index(5).html` = Bewerbung.
- `public/admin.html` = Owner-Postfach.
- `server.js` = Backend zum Speichern und Anzeigen der Bewerbungen.

## Start

```bash
npm install
npm start
```

Dann öffnen: `http://localhost:3000/`

Owner-Postfach: `http://localhost:3000/admin`

**Owner-Passwort:** `T3K-Prime`

Für einen öffentlichen Server bitte `ADMIN_PASSWORD` und `SESSION_SECRET` als Umgebungsvariablen setzen und HTTPS aktivieren.
