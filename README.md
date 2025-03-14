

## About News Aggregator Project

This platform aggregates news from different platforms using api Integrations, for this project we will be using:

- [Newsapi.org](https://newsapi.org)
- [The Guardians API Integration](https://open-platform.theguardian.com/)
- [Newsapa.ai](https://newsapi.ai)
 

## Installation
- Clone the repository
- Install the requirements using `composer install`
- Create a `.env` file by copying the `.env.example` file
- Generate a new key using `php artisan key:generate`
- Add the database credentials to the `.env` file for convenience a remote database has been provided(Note: using the provided remote DB will reduce application speed)
- Add the api keys for the different sources to the `.env` file
- Run the migrations and seed database using `php artisan migrate:fresh --seed`
- Run the application using `php artisan serve`
- Run `php artisan schedule:run` to start scheduler to fetch news from the different sources
- Run `php artisan queue:work` to start the queue worker to process the news


## DataSource  Model
- Contains the Data aggregate Services
- Filters  in Data Source Model are used to filter the data from the different sources, so that the flow can be controlled with ease
- Services are responsible for fetching data from the different sources inside `app/Services/DataSource` directory
