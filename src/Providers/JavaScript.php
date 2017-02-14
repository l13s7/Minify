<?php namespace Fahri5567\Minify\Providers;

use Fahri5567\Minify\Contracts\MinifyInterface;
use JSMin;

class JavaScript extends BaseProvider implements MinifyInterface
{
    /**
     *  The extension of the outputted file.
     */
    const EXTENSION = '.js';

    /**
     * @return string
     */
    public function minify()
    {
        $minified = JSMin::minify($this->appended);;

        return $this->put($minified);
    }

    /**
     * @param $file
     * @param array $attributes
     * @return string
     */
    public function tag($file, array $attributes)
    {
        $attributes = array('src' => $file) + $attributes;

        return "<script {$this->attributes($attributes)}></script>" . PHP_EOL;
    }
}
