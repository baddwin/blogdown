<?php

namespace Swiftmade\Blogdown;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;

class Repository
{
    public function all()
    {
        return Cache::get('blogdown.meta', collect([]));
    }

    public function get($slug, $default = null)
    {
        $all = $this->all();
        if (!$all->has($slug)) {
            return $default;
        }
        return $all->get($slug);
    }

    public function put($blog)
    {
        $all = $this->all();
        $all->put($blog->meta->slug, $blog);
        Cache::forever('blogdown.meta', $all);
    }

    public function flush()
    {
        Cache::forget('blogdown.meta');
    }

    /**
     * Gera a paginação dos itens de um array ou collection.
     * https://gist.github.com/vluzrmos/3ce756322702331fdf2bf414fea27bcb
     *
     * @param array|Collection      $items
     * @param int   $perPage
     * @param int   $page
     * @param array $options
     *
     * @return LengthAwarePaginator
     **/
    public function paginate($items, $perPage = 10, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }

    /**
     * Take only some items
     *
     * @return Collection
     */
    public function chunk($param = 5)
    {
        return $this->paginate($this->all()->sortByDesc('meta.date')->whereStrict('meta.draft','false'), $param);
    }
}
