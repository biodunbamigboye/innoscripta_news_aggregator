<?php

namespace App\Services\DataSource;

use App\Contracts\DataSourceContract;


class NewsAPITopHeadlineService extends NewsAPIService implements DataSourceContract
{
    protected string $identifier = 'news-api-top-headlines';

    public function __construct()
    {
        parent::__construct();
    }

}
