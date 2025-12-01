# 💡 Tuya Led strip
## Простое устройство для MajorDoMo

<p align="center">
  <img src="https://img.shields.io/badge/PHP-7.4%2B-blue" />
  <img src="https://img.shields.io/badge/MajorDoMo-Device%20Module-green" />
  <img src="https://img.shields.io/badge/Status-Production-success" />
  <img src="https://img.shields.io/badge/Type-Smart%20Lighting-yellow" />
  <img src="https://img.shields.io/badge/Version-1.0-orange" />
</p>

---

## 📘 Описание

**`SRGBStripTuya`** — расширяет класс *SControllers* 
> Простое устройство *Guver Lamp (Tuya)* для MajorDoMo.

**🛑 Лента должна быть подключена через хаб Tuya!**  
**🛑 Не через zigbee2mqtt!**  

---  

Поддерживает управление:

* 🎨 Цветной свет
* 🎬 Сцены

---

# ⚙️ Привязка свойств

| Tuya поле      | Свойство MajorDoMo |
| -------------- | ------------------ |
| `switch_led`   | `status`           |
| `work_mode`    | `workMode`         |
| `colour_data`  | `colorWork`        |
| `scene_data`   | `sceneWork`        |

`После привязки свойств надо поизменять свойства из приложения чтобы прилетели данные в объект`.

---
# 🔧 Методы

## 💡 Включение

```php
callMethod('Object.turnOn');
```

## ⛔ Отключение

```php
callMethod('Object.turnOff');
```

## 🔁 Переключение

```php
callMethod('Object.switch');
```
---

## 🎨 Управление цветом

### Цвет может задаваться:

✔ HEX-кодами

* `#RRGGBB`
* `#RGB`

✔ Цветовыми пресетами

```
red, green, blue, white, yellow,
cyan, magenta, orange, purple,
pink, lime
```


| Метод            | Описание                 |
| ---------------- | ------------------------ |
| `setColor`       | Установить цвет          |
| `levelDown`      | Уменьшить яркость        |
| `levelUp`        | Увеличить яркость        |

```php
callMethod('Имя Объекта.setColor', array("value"=>`#RRGGBB` или `#RGB` или присет));
callMethod('Имя Объекта.setLevel', array("value"=>1--100));
callMethod('Имя Объекта.colorLevelUp', array("value"=>1--100));
  *callMethod('Имя Объекта.colorLevelUp'); увеличит на 10
callMethod('Имя Объекта.colorLevelDown', array("value"=>1--100));
  *callMethod('Имя Объекта.colorLevelUp'); уменьшит на 10
```

---
## 🎬 Управление сценами

📄 `scenesList`

Хранит список доступных сцен в формате:

Название=Значение,Название=Значение,...

**Пример:**
```php
Спокойная=000e0d0000000000000000c80000,
Чтение=010e0d0000000000000003e801f4,
Работа=020e0d0000000000000003e803e8
```

- Если свойство `scenesList` пустое — оно автоматически заполнится дефолтными сценами.  
- Можно редактировать список вручную, добавлять свои сцены или полностью заменить его.  

---
🏷 `sceneName`

Хранит **имя текущей активной сцены**.

- Можно установить сцену по имени (Majordomo автоматически подставит нужный код).  
- Если указанное имя отсутствует в списке — установится **последняя сохранённая сцена**.  
- Если в `sceneName` записано `"unknown"` — в интерфейсе отображается **«Неизвестная сцена»**, но список сцен остаётся доступен для выбора.

---