# Translations

## Export
1. run
```bash
docker-compose exec php bash -c 'bin/console cache:clear'
```
2. from [translation source](https://clovekvtisni.sharepoint.com/:x:/r/sites/BMSWorkingGroup/_layouts/15/Doc.aspx?sourcedoc=%7Bf6214be4-7b4c-42a0-b7c9-55f50a628531%7D&action=edit&wdinitialsession=280bad1d-711d-420a-8a83-d2fe16f98237&wdrldsc=2&wdrldc=1&wdrldr=OnSaveAsWebMethodComplete&cid=eb23d864-716f-4a11-81cd-acf5d563a1ac)
   (New Keys sheet) add new translation to messages.en.xlf file:  
   `<trans-unit id="{KEY}"><source>{KEY}</source></trans-unit>`
3. run
```bash
docker-compose exec php bash -c 'bin/console translation:update --force en'
```
4. copy new en translations  
   from `/translations`  
   to `/app/Resources/translations`
5. call `/web-app/v1/translations-download` and save response as a file.
6. insert columns C - M (replace existing data) into [translation source for PIN](https://clovekvtisni.sharepoint.com/:x:/r/sites/BMSWorkingGroup/_layouts/15/Doc.aspx?sourcedoc=%7Bf6214be4-7b4c-42a0-b7c9-55f50a628531%7D&action=edit&wdinitialsession=280bad1d-711d-420a-8a83-d2fe16f98237&wdrldsc=2&wdrldc=1&wdrldr=OnSaveAsWebMethodComplete),
   BE Web App sheet (start with row 4, where is the first row# entry).

## Import
1. open [translation source for PIN](https://clovekvtisni.sharepoint.com/:x:/r/sites/BMSWorkingGroup/_layouts/15/Doc.aspx?sourcedoc=%7Bf6214be4-7b4c-42a0-b7c9-55f50a628531%7D&action=edit&wdinitialsession=280bad1d-711d-420a-8a83-d2fe16f98237&wdrldsc=2&wdrldc=1&wdrldr=OnSaveAsWebMethodComplete)
   BE Web App sheet, make sure no columns are hidden, sort by column A (row#)
2. copy values to your source file from Export 5. (when using desktop excel, copying straight from browser might be problematic - download translated source and copy after opening the file in desktop excel)
3. check in last row values in B (resname) and D (source) are matching
4. save as *CSV UTF-8 (Comma delimited) (\*.csv)* to `app/Resources/translations` as `translations.csv` (delimiter will be semicolon but that is desired)
5. make sure each translation is on one line
6. run
```bash
docker-compose exec php bash -c 'bin/console translation:update:generate'
```
7. run cleanup code