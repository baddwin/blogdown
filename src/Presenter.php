<?php

namespace Swiftmade\Blogdown;

class Presenter
{
    /**
     * @var Repository
     */
    private $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    public function find($slug)
    {
        $blog = $this->repository->get($slug);
        if (is_null($blog)) {
            abort(404);
        }
        if ($this->isModified($blog)) {
            return $this->refresh($blog);
        }
        return $blog;
    }

    public function recent($take = 10)
    {
        return $this->repository->all()
            ->sortByDesc('meta.date')
            ->whereStrict('meta.draft','false')
            ->take($take);
    }

    public function others($slug, $take = 5)
    {
        return $this->repository->all()
            ->filter(function($blog) use($slug) {
                return $blog->meta->slug !== $slug;
            })
            ->shuffle()
            ->take($take);
    }

    public function category($categories, $take = 10)
    {
        return $this->repository->all()
            ->filter(function($value) use ($categories){
                if (in_array(strtolower($categories), array_map('strtolower',$value->meta->categories))) {
                    return $value;
                }
            })
            ->take($take);
    }

    public function tag($tags, $take = 10)
    {
        return $this->repository->all()
            ->filter(function($value) use ($tags){
                if (in_array(strtolower($tags), array_map('strtolower',$value->meta->tags))) {
                    return $value;
                }
            })
            ->take($take);
    }

    protected function isModified($blog)
    {
        return md5_file($blog->meta->path) !== $blog->hash;
    }

    protected function refresh($blog)
    {
        $blog = Parser::parse($blog->meta->path);
        $this->repository->put($blog);
        return $blog;
    }
}
