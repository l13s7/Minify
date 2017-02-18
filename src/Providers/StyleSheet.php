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
     *  Regex find url().
     */	
    const REGEX_URL = '~url\s*\(\s*[\'"]?(?!(((?:https?:)?\/\/)|(?:data\:?:)))([^\'"\)]+)[\'"]?\s*\)~i';

    /**
     * @return string
     */
    public function minify()
    {
        $minified = Minify_CSS::minify( $this->FirstImportUrl( $this->appended ), ['preserveComments' => false]);

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
                $contents = file_get_contents($file, false, $context);
                $subst  = 'url("'.dirname( $file ).'/$3")';
                $contents = preg_replace(self::REGEX_URL, $subst, $contents);
                
                if (strpos($http_response_header[0], '200') === false) {
                    throw new FileNotExistException("File '{$file}' does not exist");
                }
            }
            else {
                $contents = $this->urlCorrection($file);
            }
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
        $folder  = str_replace(public_path(), '', $file);
        $folder  = str_replace(basename($folder), '', $folder);
        $content = file_get_contents($file);
        $subst = 'url("'.$folder.'$3")';
        return preg_replace(self::REGEX_URL, $subst, $content);
    }

    /**
     * Change css @import url to First Line.
     *
     * @param string $content
     * @return string
     */
    public function FirstImportUrl($content)
    {
        preg_match_all("/\@(import)(.*)/", $content, $importLine);
		$importLineTemp = '';
		foreach ($importLine[0] as $value) {
			$content = str_replace($value, '', $content);
			$importLineTemp .= $value."\n";
		}
		return $importLineTemp.$content;
	}
}
