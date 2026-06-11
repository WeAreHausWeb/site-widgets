# Site Widgets
Template plugin för att bygga vidare på och ska innehålla diverse site specifika widgets.

#### För att komma igång, kör först:
```
composer install
nvm use
npm install
```
-----
#
#### Kompilerar styles/scripts med Vite
För en utvecklingskompilering utan minifiering, kör:
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

Editera `vite.config.js` filen för att lägga till fler styles/scripts.

-----
#
#### Deploy till server
Deploy sker automatiskt från github till servern om allt är uppsatt rätt i repot.
Så det ska räcka med att pusha/merga till master branch för att deploya till servern.

Det går att göra en manuell deploy av filer till servern om `deploy` filen är hanterad och pekar till din server.
För att manuellt deploya din kod till servern, ställ dig i denna mapp och kör:
```
./deploy
```
