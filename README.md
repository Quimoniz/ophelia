# ![./img/weather/typ_gross.small.png](./img/weather/typ_gross.small.png) Ophelia's window ![./img/weather/sonne.small.png](./img/weather/sonne.small.png)

A lightweight data-visualization-framework for data of your immediate surroundings, like weather, public transport departures and rss feeds. 

![status-page-screenshot_2023-12-06](https://github.com/Quimoniz/ophelia/assets/653290/fdab311c-09a1-4f96-870b-c45dbc61ae26)

## Technicalities

It utilizes PHP 7+ and performs caching using the filesystem.  Additionally some `components` may have additional requirements (e.g. a MariaDB-database connection). The OpenWeatherMap-Component uses Plotly.

On a historical note: The first version of Ophelia dates back to the year 2016.

## Components

- Weather, using OpenWeatherMap
- VVO-Departure (tightly coupled)
- Feedreader (tighly coupled)
See the [example config file](./config.php.sample)

Non-maintained (read: disfunct) components:
- Vocabulary display
- Humidity graphing


## Setup

Just copy them files over, edit the [example config file](./config.php.sample) and store it under `config.php` (i.e. truncate the file name extension `.sample`).  It's PHP, so refer to whatever tutorial detailing the steps on how to set up PHP. If you want to run the OpenWeatherMap component, do setup a MariaDB database, including the user and database setup. Thereafter run the file [ophelia-table-creation.sql](./ophelia-table-creation.sql) to create the `openweathermap_forecast` table which is being used to Cache the weather data.

## Boring stuff

![./img/weather/wolke_wind.small.png](./img/weather/wolke_wind.small.png) License is MIT.  All of the images are also under MIT license. For the weather graphics I want to express my gratitude to the talented artist and former roommate [Comic Vogel](https://www.comicvogel.de).
