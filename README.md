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
#### Kompilerar styles/scripts med Laravel Mix
För komma igång och kompilera, kör:
```
npx mix watch
```
Editera `webpack.mix.js` filen för att lägga till fler styles/scripts.

-----
#
#### Deploy till server (Oderland)
Pusha alltid upp dina ändringar till git först.
Se även till att första gången editera `deploy` filen så att den pekar till din server.
För att sedan deploya din kod till servern, ställ dig i denna mapp och kör:
```
./deploy
```
