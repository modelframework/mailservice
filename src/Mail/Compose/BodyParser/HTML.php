<?php
/**
 * Created by PhpStorm.
 * User: ksv
 * Date: 7/11/14
 * Time: 5:42 PM
 */

namespace Mail\Compose\BodyParser;

use Zend\Mail\Headers;

class HTML implements ParserInterface
{
    protected $additionalCSSWord = 'mail-css';

    /**
     * parsing text
     *
     * @param string  $data
     * @param Headers $header           order witch parts should be seen
     * @param Array   $additionalParams array of additional params
     *
     * @return Array
     */
    public function parse($data, $header, $additionalParams = null)
    {
        // TODO: Implement parse() method.
//        $temp = explode('<body>', $data);
//        if(count($temp) == 2)
//        {
//            $data = $temp[1];
//            $data = explode('</body>',$data)[0];
//        }
//
//        $data = '<div id="mail-body">'.$data.'<div id="mail-body">';

        //remove links
        $data = preg_replace('/<link(.*?)(\\/|\\\\)>/is', '', $data);
        $data = preg_replace('/<link(.*?)>/is', '', $data);
        $data = preg_replace('/<script(.*?)>(.*?)<(\\/|\\\\)script>/is', '', $data);
        $data = preg_replace('/<script(.*?)>/is', '', $data);

        //remove inline scripts
        $data = preg_replace('/<script>(.*)/is', '', $data);

        //remove frameset and frame tags
        $data = preg_replace('/<frameset>(.*?)<\/frameset>/is', '', $data);
        $data = preg_replace('/<frameset>(.*)/is', '', $data);
        $data = preg_replace('/<frame(.*?)>/is', '', $data);

        return $data;
    }
}
