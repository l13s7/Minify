<?php

namespace Fahri5567\Minify\Providers;

use Fahri5567\Minify\Contracts\MinifyInterface;
use Minify_CSS;

class StyleSheet extends BaseProvider implements MinifyInterface
{
    /**
     *  The extension of the outputted file.
     */
    const EXTENSION = '.css';

    /**
     * @return string
     */
    public function minify()
    {
        $minified = Minify_CSS::minify($this->appended, ['preserveComments' => false]);

        return $this->put($minified);
    }

    /**
     * @param $file
     * @param array $attributes
     * @return string
     */
    public function tag($file, array $attributes = array())
    {
        $attributes = array('href' => $file, 'rel' => 'stylesheet') + $attributes;

        return "<link {$this->attributes($attributes)}>".PHP_EOL;
    }

    /**
     * Override appendFiles to solve css url path issue
     *
     * @throws \Fahri5567\Minify\Exceptions\FileNotExistException
     */
    protected function appendFiles()
    {
        foreach ($this->files as $file) {
            if ($this->checkExternalFile($file)) {
                if (strpos($file, '//') === 0) $file = 'http:'.$file;

                $headers = $this->headers;
                foreach ($headers as $key => $value) {
                    $headers[$key] = $key.': '.$value;
                }
                $context = stream_context_create(array('http' => array(
                        'ignore_errors' => true,
                        'header' => implode("\r\n", $headers),
                )));

                $http_response_header = array(false);


                if (strpos($http_response_header[0], '200') === false) {
                    throw new FileNotExistException("File '{$file}' does not exist");
                }
            }
            $contents = $this->urlCorrection($file);
            $this->appended .= $contents."\n";
        }
    }

    /**
     * Css url path correction
     *
     * @param string $file
     * @return string
     */
    public function urlCorrection($file)
    {
        $folder             = str_replace(public_path(), '', $file);
        $folder             = str_replace(basename($folder), '', $folder);
        $content            = file_get_contents($file);
        $contentReplace     = [];
        $contentReplaceWith = [];
        preg_match_all('/url\(([\s])?([\"|\'])?(.*?)([\"|\'])?([\s])?\)/i', $content, $matches, PREG_PATTERN_ORDER);
        if (!count($matches)) {
            return $content;
        }
        foreach ($matches[0] as $match) {
            if (strpos($match, "'") != false) {
                $contentReplace[]     = $match;
                $contentReplaceWith[] = str_replace('url(\'', 'url(\''.$folder, $match);
            } elseif (strpos($match, '"') !== false) {
                $contentReplace[]     = $match;
                $contentReplaceWith[] = str_replace('url("', 'url("'.$folder, $match);
            } else {
                $contentReplace[]     = $match;
                $contentReplaceWith[] = str_replace('url(', 'url('.$folder, $match);
            }
        }
        return str_replace($contentReplace, $contentReplaceWith, $content);
    }
}
