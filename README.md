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
<<<<<<< HEAD
#### Kompilerar styles/scripts med Vite
För en utvecklingskompilering utan minifiering, kör:
=======
#### Bygg dina widgets i src mappen
Se exempel med `XXX` mappen

Widgeten registreras sen och initieras i `index.php` filen.

-----
#
#### Kompilerar styles/scripts med Laravel Mix
För komma igång och kompilera, kör:
>>>>>>> 17ce26d56ca5f1d7e0f3741c24f4b16cada99e77
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
<<<<<<< HEAD
#### Deploy till server
Deploy sker automatiskt från github till servern om allt är uppsatt rätt i repot.
Så det ska räcka med att pusha/merga till master branch för att deploya till servern.

Det går att göra en manuell deploy av filer till servern om `deploy` filen är hanterad och pekar till din server.
För att manuellt deploya din kod till servern, ställ dig i denna mapp och kör:
=======
#### Deploy till server (Oderland)
Pusha alltid upp dina ändringar till git först.
Se även till att första gången editera `deploy` filen så att den pekar till din server.
För att sedan deploya din kod till servern, ställ dig i denna mapp och kör:
>>>>>>> 17ce26d56ca5f1d7e0f3741c24f4b16cada99e77
```
./deploy -production
```
eller
```
./deploy -staging
```
