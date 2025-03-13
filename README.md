

## About News Aggregator Project

This platform aggregates news from different platforms using api Integrations, for this project we will be using:

- [Newsapa.ai](https://newsapi.ai)
- [Newsapi.org](https://newsapi.org)
- [New York Times API](https://nytimes.com) and
- [The Guardians API Integration](https://open-platform.theguardian.com/)
 
## Installation
- Clone the repository
- Install the requirements using `composer install`
- Create a `.env` file by copying the `.env.example` file
- Generate a new key using `php artisan key:generate`
- Add the database credentials to the `.env` file
- Run the migrations and seed database using `php artisan migrate --seed`
- Run the application using `php artisan serve`


## Data Source  Model
- Contains the Data aggregate Services
- If `max_article_per_sync` is null, it will process all the data, not recommended for large data
