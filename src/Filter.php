<?php

namespace Aginev\SearchFilters;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;

class Filter
{
    /**
     * Search params
     *
     * @var Collection
     */
    protected $request;

    /**
     * Query builder
     *
     * @var Builder
     */
    protected $query;

    /**
     * All the filters. Will be used in order check
     *
     * @var Collection
     */
    protected $constraints;

    /**
     * Default order by column
     *
     * @var string
     */
    protected $order_by;

    /**
     * Default order by direction
     *
     * @var string
     */
    protected $order_dir;

    /**
     * Flag if any custom filter has been applied
     *
     * @var bool
     */
    protected $has_custom_filter = false;

    public function __construct(Builder $query, array $request)
    {
        $this->query = $query;
        $this->request = new Collection($request);
        $this->constraints = new Collection();

        $this->order_by = config('search-filters.order_by', 'id');
        $this->order_dir = config('search-filters.order_dir', 'desc');
    }

    /**
     * @param $method
     * @param $arguments
     * @return $this
     * @throws \Exception
     */
    public function __call($method, $arguments)
    {
        $method = 'set' . ucfirst($method);
        $key = $arguments[0];
        
        if (method_exists($this, $method)) {
            $this->setConstraint($key);
            $value = $this->value($key);
            
            // Null will be returned if the value do not exists in the request
            // Consider empty string ('') as invalid value
            if (!is_null($value) && $value !== '') {
                array_push($arguments, $value);
                return call_user_func_array([$this, $method], $arguments);
            }
            
            return $this;
        }
        
        throw new \Exception('Filter method not found!');
    }

    public function getOrderByField()
    {
        return $this->request->get(config('search-filters.order_by_key', 'order_by'), '');
    }

    /**
     * Get order by field name
     *
     * @return string
     */
    public function getOrderBy()
    {
        $by = $this->getOrderByField();

        if ($this->constraints->has($by)) {
            $constraint = $this->constraints->get($by);

            if ($constraint instanceof Closure) {
                return $constraint;
            } else {
                return $by;
            }
        }

        return '';
    }

    /**
     * Get order by direction
     *
     * @return string
     */
    public function getOrderDir()
    {
        $dir = strtolower($this->request->get(config('search-filters.order_dir_key', 'order_dir'), $this->order_dir));
        $dir = in_array($dir, ['asc', 'desc']) ? $dir : $this->order_dir;

        return $dir;
    }

    /**
     * Set query order
     *
     * @return $this
     */
    public function order()
    {
        $by = $this->getOrderBy();
        $dir = $this->getOrderDir();

        if ($by instanceof Closure) {
            $by($this->getOrderByField(), $dir, $this->query);
        } else if ($by) {
            $this->query->orderBy($by, $dir);
        }

        return $this;
    }

    /**
     * True if has custom filters applied
     *
     * @return bool
     */
    public function hasCustomFilters()
    {
        return $this->has_custom_filter;
    }

    /**
     * Custom filter
     *
     * @param $key
     * @param Closure $callback
     * @param Closure|null $order_callback
     * @return $this
     */
    public function custom($key, Closure $callback, Closure $order_callback = null)
    {
        $this->setConstraint($key, $order_callback);
        $value = $this->value($key);
        
        if (!is_null($value)) {
            $callback($this->query, $key, $value);
        }

        $this->has_custom_filter = true;

        return $this;
    }

    /**
     * Equal filter
     *
     * @param $key
     * @param $value
     * @return $this
     */
    private function setEqual($key, $value)
    {
        $this->query->where($key, '=', $this->request->get($key));

        return $this;
    }

    /**
     * Distinct Filter
     *
     * @param $key
     * @param $value
     * @return $this
     */
    private function setDistinct($key, $value)
    {
        $this->query->where($key, '<>', $this->request->get($key));

        return $this;
    }

    /**
     * GreaterThan
     *
     * @param $key
     * @param $value
     * @return $this
     */
    private function setGreaterThan($key, $value)
    {
        $this->query->where($key, '>', $this->request->get($key));

        return $this;
    }

    /**
     * tGreaterOrEqualThan
     *
     * @param $key
     * @param $value
     * @return $this
     */
    private function setGreaterOrEqualThan($key, $value)
    {
        $this->query->where($key, '>=', $this->request->get($key));

        return $this;
    }

    /**
     * LessThan
     *
     * @param $key
     * @param $value
     * @return $this
     */
    private function setLessThan($key, $value)
    {
        $this->query->where($key, '<', $this->request->get($key));

        return $this;
    }

    /**
     * LessOrEqualThan
     *
     * @param $key
     * @param $value
     * @return $this
     */
    private function setLessOrEqualThan($key, $value)
    {
        $this->query->where($key, '<=', $this->request->get($key));

        return $this;
    }

    /**
     * Like (%LIKE%)
     *
     * @param $key
     * @param $value
     * @return $this
     */
    private function setLike($key, $value)
    {
        $this->query->where($key, 'LIKE', '%' . $value . '%');

        return $this;
    }

    /**
     * LIKE%
     *
     * @param $key
     * @param $value
     * @return $this
     */
    private function setLlike($key, $value)
    {
        $this->query->where($key, 'LIKE', '%' . $value);

        return $this;
    }

    /**
     * %LIKE
     *
     * @param $key
     * @param $value
     * @return $this
     */
    private function setRlike($key, $value)
    {
        $this->query->where($key, 'LIKE', $value . '%');

        return $this;
    }

    /**
     * Between
     *
     * @param $key
     * @param $value
     * @return $this
     */
    private function setBetween($key, $value)
    {
        if (is_array($value)) {
            $this->query->whereBetween($key, $value);
        }

        return $this;
    }

    /**
     * NotBetween
     *
     * @param $key
     * @param $value
     * @return $this
     */
    private function setNotBetween($key, $value)
    {
        if (is_array($value)) {
            $this->query->whereNotBetween($key, $value);
        }

        return $this;
    }

    /**
     * In
     *
     * @param $key
     * @param $value
     * @return $this
     */
    private function setIn($key, $value)
    {
        if (is_array($value)) {
            $this->query->whereIn($key, $value);
        }

        return $this;
    }

    /**
     * NotIn
     *
     * @param $key
     * @param $value
     * @return $this
     */
    private function setNotIn($key, $value)
    {
        if (is_array($value)) {
            $this->query->whereNotIn($key, $value);
        }

        return $this;
    }

    /**
     * Null
     *
     * @param $key
     * @param $value
     * @return $this
     */
    private function setNull($key, $value)
    {
        $this->query->whereNull($key, $value);

        return $this;
    }

    /**
     * NotNull
     *
     * @param $key
     * @param $value
     * @return $this
     */
    private function setNotNull($key, $value)
    {
        $this->query->whereNotNull($key, $value);

        return $this;
    }

    /**
     * Date
     *
     * @param $key
     * @param $value
     * @return $this
     */
    private function setDate($key, $value)
    {
        $this->query->where(\DB::raw('DATE(' . $key . ')'), '=', $value);

        return $this;
    }

    /**
     * DateBetween
     *
     * @param $key
     * @param $value
     * @return $this
     */
    private function setDateBetween($key, $value)
    {
        $this->query->whereBetween(\DB::raw('DATE(' . $key . ')'), $value);

        return $this;
    }

    /**
     * Get specific key value from the search criteria
     *
     * @param $key
     * @return null|mixed
     */
    private function value($key)
    {
        return $this->request->get($key, null);
    }

    /**
     * Set key in the constraints array
     *
     * @param $key
     * @param Closure $order
     * @return $this
     */
    private function setConstraint($key, Closure $order = null)
    {
        $this->constraints->put($key, $order ? $order : $key);

        return $this;
    }

}
