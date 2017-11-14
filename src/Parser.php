<?php

namespace Swiftmade\Blogdown;

use Carbon\Carbon;
use Parsedown;
use ParsedownExtra;
use Symfony\Component\Yaml\Yaml;

class Parser
{
    private $path;
    private $content;

    public function __construct($path)
    {
        $this->path = $path;
        $this->content = file_get_contents($path);
    }

    public static function parse($path)
    {
        $parser = (new self($path));
        $blog = new \stdClass();
        try {
            $blog->meta = $parser->meta();
        } catch (\Exception $e) {
            try {
                $blog->meta = $parser->yaml();
            } catch (\Exception $e) {
                $blog->meta = [];
                echo $e;
            };
        };
        $blog->html = $parser->html();
        $blog->hash = md5_file($path);
        return $blog;
    }

    /**
     * Yaml parser with Symfony Component
     *
     * @return object
     */
    public function yaml()
    {
        if (!preg_match('/^-{3,}\n?(.*?)\n-{3,}/ms', $this->content, $matches)) {
            throw new \Exception('Not a yaml front matter we want. Abort caching.');
        }
        $meta = new \stdClass();
        $yaml = new Yaml();
        $meta = (object)$yaml->parse($matches[1], Yaml::PARSE_DATETIME);
        if(property_exists($meta, 'date')) {
            $meta->date = Carbon::createFromFormat(config('blogdown.date_format'),$meta->date);
        }
        $meta->path = $this->path;

        return $meta;
    }

    public function meta()
    {
        if (!preg_match('/\/\*(.+?)\*\//ms', $this->content, $matches)) {
            throw new \Exception("Invalid blogdown syntax. Missing meta section");
        }
        $meta = new \stdClass();
        $meta->path = $this->path;

        collect(explode("\n", $matches[1]))
            ->filter(function ($line) {
                return !empty(trim($line));
            })
            ->each(function ($line) use ($meta) {
                list($key, $value) = $this->breakMetaLine($line);
                $meta->$key = $value;
            });

        if(property_exists($meta, 'date')) {
            $meta->date = Carbon::createFromFormat(config('blogdown.date_format'), $meta->date);
        }

        return $meta;
    }

    protected function breakMetaLine($line)
    {
        $firstColon = strpos($line, ':');
        return array_map('trim', [
            substr($line, 0, $firstColon),
            substr($line, $firstColon + 1, strlen($line) - $firstColon)
        ]);
    }

    public function html()
    {
        if(preg_match('#/\*#', $this->content, $matches)) {
            $markdown = preg_replace('/\/\*(.+?)\*\//ms', '', $this->content);
        } else {
            $markdown = preg_replace('/^-{3,}\n(.*?)\n-{3,}$/ms', '', $this->content);
        }
        $html = new ParsedownExtra;
        // TODO: Move this to a custom modifier.
        $html = str_replace('<table>', '<table class="table table-bordered">', $html->text($markdown));
        return $html;
    }
}
