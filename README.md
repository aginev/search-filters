# Easily apply search criteria on Laravel 5 queries
This package will try to solve the problem with filtering a result set and will apply all the necessary checks for you.

## Features
- Composer installable
- PSR4 auto loading
- Filter query in Laravel 5+

## Requires
Build to be used with Laravel only!

## Installation
In terminal
```sh
composer require aginev/search-filters:2.0.*
```

Publish config
```sh
php artisan vendor:publish --provider="Aginev\SearchFilters\SearchFiltersServiceProvider" --tag="config"
```

## Usage

Add Filterable trait to you models like so
```php
use Aginev\SearchFilters\Filterable;
```

Implement setFilters method and define your filters. All filter methods are accepting a database column name as parameter. 
```php
/**
 * Set query filters
 *
 * Overwrite this method in the model to set query filters
 */
public function setFilters()
{
    $this->filter->equal('id', function ($by, $dir, $query) {
            // Every filter can recieve as last paramenter a closure that can be used for custom query order if required
            $query->orderBy($by, $dir);
        })
        ->like('email')
        ->like('first_name')
        ->like('middle_name')
        ->like('last_name')
        ->like('phone')
        ->equal('is_active')
        ->date('created_at')
        ->date('updated_at');
}
```

Need a custom filter?
```php
public function setFilters()
{
    $this->filter->equal('id')
        ->custom('full_name', function ($query, $key, $value) {
            $query->where(\DB::raw("CONCAT_WS(' ', first_name, middle_name, last_name)"), 'LIKE', '%' . $value . '%');
        }, function ($by, $dir, $query) {
            // Define custom order or skip this parameter in method call
            $query->orderBy($by, $dir);
        });
}
```

Note that you need to pass the input filters to filter scope. My URLs typically looks like this http://ex.com/?f[first_name]=Atanas&f[last_name]=GinevRetrieve Than you can get the results like so:
```php
// Simple filter query
$users = User::filter(\Request::input('f', []))->get();

// Add additional where and pagination
$users = User::where('is_admin', '=', 1)
    ->filter(\Request::input('f', []))
    ->paginate(25);
```

## Available filter methods
```php
public function setFilters()
{
     $this->filter
     ->custom('column', function($query, $column, $value) {
        // $query - instance of Illuminate\Database\Eloquent\Builder
        // $column - the string passed as first argument
        // $value - the filter value if exists and not empty
     }, function ($by, $dir, $query) {
        // Not required and can be applied to any other filter method
     
        // $by - order by field
        // $dir - order direction
        // $query - instance of Illuminate\Database\Eloquent\Builder
    })
     ->equal('column')                  // column = filter_value
     ->distinct('column')               // column <> filter_value
     ->greaterThan('column')            // column > filter_value
     ->greaterOrEqualThan('column')     // column >= filter_value
     ->lessThan('column')               // column < filter_value
     ->lessOrEqualThan('column')        // column <= filter_value
     ->like('column')                   // column LIKE '%filter_value%'
     ->llike('column')                  // column LIKE '%filter_value'
     ->rlike('column')                  // column LIKE 'filter_value%'
     ->between('column')                // column BETWEEN filter_value[0] AND filter_value[1]
     ->notBetween('column')             // column NOT BETWEEN filter_value[0] AND filter_value[1]
     ->in('column')                     // column IN (filter_value[0], ..., filter_value[N])
     ->notIn('column')                  // column NOT IN (filter_value[0], ..., filter_value[N])
     ->null('column')                   // column IS NULL
     ->notNull('column')                // column IS NOT NULL
     ->date('column')                   // column DATE(column) = filter_value
     ->dateBetween('column');           // column DATE(column) BETWEEN filter_value[0] AND filter_value[1]
}
```

## Contribution
Want to share your custom filter methods? Submit a pull request, and I'll consider them :)

## License
MIT - http://opensource.org/licenses/MIT

## About
Need a freelance web developer? Contact me at my website https://aginev.com
