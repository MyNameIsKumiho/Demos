# Оформление стрима: Elden Ring × VTuber

## Бриф (для дизайнера или для себя)

Стиль: «благодать в Междуземье». Тёмная ночь с тёплым золотым свечением, тонкие золотые
рамки с угловыми орнаментами, медленно поднимающиеся искры. Никакого неона и киберпанка:
всё тёплое, старинное, торжественное. Аватар VTuber'а стоит справа в золотом ореоле, как
у места благодати.

- Палитра: ночь #0B0A09, пепел #16130F, золото #D4B06A, светлое золото #F3DDA2,
  угли #E9A04C, пергамент #E8DCC0, кровь для «Вы погибли» #A7201F.
- Шрифты: Forum (заголовки, с кириллицей), Cormorant Garamond (текст).
- Сцены: «Скоро начнём», «Игра», «Отдыхаю у благодати», «Конец эфира».
- Фишки: счётчик смертей с заставкой «Вы погибли», счётчик боссов, цель стрима,
  чат «Голоса из Междуземья».
- Официальный логотип и арт игры не используем, только свою графику в духе игры.

## Промпты для генерации фонов (Midjourney / Nano Banana / Flux)

Фон «Скоро начнём» и «Конец эфира», место под аватар справа:

```
dark fantasy landscape at night, a colossal glowing golden tree on the horizon radiating
soft light, gentle golden particles rising in the air, ancient ruins in silhouette, misty
valley, warm amber and gold light against deep black-brown shadows, painterly, cinematic,
empty space on the left third for text, empty foreground on the right for a character,
no characters, no text, no logos, 16:9 --ar 16:9 --style raw
```

Фон «Отдыхаю у благодати»:

```
a small shrine of golden light in a dark stone ruin, a single floating flame of grace
surrounded by faint golden runic circles, embers drifting upward, deep shadows, warm gold
glow, quiet and peaceful mood, painterly dark fantasy, no characters, no text, no logos,
16:9 --ar 16:9
```

Декоративная рамка для игровой сцены (PNG с прозрачностью, если модель умеет):

```
ornate thin golden frame border for a 16:9 game screen, gothic medieval filigree corners,
engraved gold metal, fine linework, symmetrical, transparent center, isolated on black
background, high detail, no text
```

Фон для аватара на игровой сцене (под колонкой справа):

```
vertical banner of soft golden light, faint radiant halo behind where a character stands,
floating embers, dark smoky background fading to black at the edges, dark fantasy,
no characters, no text, 9:16 --ar 9:16
```

Совет: генерируйте фоны без текста и без персонажей, текст и аватар лучше держать
отдельными слоями в OBS, так их легко менять.

## Подключение в OBS

1. Источник «Браузер» → «Локальный файл» → `overlay.html`, ширина 1920, высота 1080.
2. В конце адреса сцены добавьте `#starting`, `#game`, `#brb` или `#ending`
   (для этого вместо галочки «Локальный файл» укажите URL вида
   `file:///C:/путь/overlay.html#game`).
3. Игровая сцена: захват игры под оверлеем, аватар VTube Studio (Spout2 или захват окна)
   поверх оверлея в правом нижнем углу, в зоне золотого ореола.
4. Счётчики: ПКМ по источнику → «Взаимодействовать»: D — смерть, Shift+D — минус,
   B — босс, Shift+B — минус, R — сброс. Значения сохраняются между запусками.
5. Чат: в `overlay.html` в `CONFIG` впишите `twitchChannel: "ваш_ник"`. Ник, тема,
   цель и соцсети — там же.
