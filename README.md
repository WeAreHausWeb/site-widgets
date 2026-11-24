# Site Widgets
Mall för en sajts eget plugin. Varje sajt får ett eget repo, `WeAreHausWeb/<domän>`, som Simon skapar från den här mallen
(`haus_site.py create-repo`). Allt sajtspecifikt ligger här: widgets, PHP, JS och CSS. Det som alla sajter har ligger i Webien Suite.

**Koden når sajten bara via det här repot.** En push till `master` bygger och deployar till produktion via GitHub Actions.
Ingen kod skrivs direkt på servern, varken av utvecklare eller av Claude. Redaktörer publicerar via Claude med
`haus_site.py publish`, och backar med `haus_site.py rollback` (se `AGENTS.md` och haus-wp-protocol).

**Funktioner** registreras i `index.php` med `Features::add()` och kan slås av under *Inställningar › Site features*.

#### För att komma igång, kör först:
```
composer install
nvm use
npm install
```
-----

- Kompilerar styles/scripts med Vite

- Bygg dina widgets i src mappen.
Se exempel med `XXX` mappen

- Widgets och andra funktioner registreras i `index.php` med `Features::add()`.

-----
### Kompilering

För komma igång och kompilera, kör:

```
npm run dev
```

För att kompilera automatiskt vid ändringar, kör:
```
npm run watch
```

För produktionsbygge, kör:
```
npm run build
```

Editera `vite.config.js` filen vid behov för att hantera styles/scripts.

-----
### Deploy till server (Oderland)
Se först till att uppdatera `deploy` filen som ligger i roten av pluginnet. Editera användarnamn och paths så att de matchar serverns setup.

Deploy sker automatiskt från github till servern om allt är uppsatt rätt i repot.
Så det ska räcka med att pusha/merga till master branch för att deploya till servern.
Fungerar det inte - [se info angående uppsättning av github actions här](https://github.com/WeAreHausWeb/dev/blob/main/Guides/Github-actions.md).

Det går även att göra en manuell deploy av filer till servern om `deploy` filen är hanterad och pekar till din server.
För att manuellt deploya din kod till servern, ställ dig i denna mapp och kör:

```
./deploy -production
```
eller
```
./deploy -staging
```


-----
### Säkerställ att deployen gick ut
Workflowet skriver `release.txt` (commit och tid) i pluginet vid varje deploy. `haus_site.py status` jämför den med repot.
Sätt gärna `WP_PATH_PROD` i `deploy` för att rensa LiteSpeed och Elementor-CSS efter varje deploy.

### Skydd på repot
Varje sajtrepo har en regel på `master`: ingen force push och ingen radering (skapas av `create-repo`). Pushar går direkt
till produktion – det finns ingen PR eller staging i redaktörsflödet; historiken och återställningen är skyddsnätet.
