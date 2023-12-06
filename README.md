# Ophelia's window

A lightweight data-visualization-framework for data of your immediate surroundings, like weather, public transport departures and rss feeds.

![status-page-screenshot_2023-12-06](https://github.com/Quimoniz/ophelia/assets/653290/fdab311c-09a1-4f96-870b-c45dbc61ae26)

## Technicalities

It utilizes PHP 7+ and performs caching using the filesystem.  Additionally some `components` may have additional requirements (e.g. a MariaDB-database connection). The OpenWeatherMap-Component uses Plotly.

On a historical note: The first version of Ophelia dates back to the year 2016.

## Components

- Weather, using OpenWeatherMap
- VVO-Departure (tightly coupled)
- Feedreader (tighly coupled)
- Vocabulary display (commented out, it's disfunct right now)

See the [example config file](./config.php.sample)
