<?php
/**
 * SilverStripe Email Obfuscator
 * =============================
 *
 * RequestProcessor filter to automatically encode all email
 * addresses (including mailto: links) in outputted HTML.
 * Switches between ASCII & hexadecimal encoding.
 *
 * Usage: Simply extract to your SilverStripe website path
 * and run a ?flush=1
 *
 * License: MIT-style license http://opensource.org/licenses/MIT
 * Authors: Techno Joy development team (www.technojoy.co.nz)
 */

class EmailObfuscatorRequestProcessor implements RequestFilter
{

    protected $list = null;
    /**
     * Filter executed AFTER a request
     * Run output through ObfuscateEmails filter
     * encoding emails in the $response
     */
    public function postRequest(SS_HTTPRequest $request, SS_HTTPResponse $response, DataModel $model)
    {
        if (preg_match('/text\/html/', $response->getHeader('Content-Type')) && $request->routeParams()['Controller'] != 'AdminRootController') {
            $response->setBody(
                $this->ObfuscateEmails($response->getBody())
            );
        }
    }

    /*
     * Obfuscate all matching emails
     * @param string
     * @return string
     */
    public function ObfuscateEmails($html)
    {
        $this->list = ArrayList::create();
        $regex_email = '[:_a-z0-9-+]+(\.[_a-z0-9-+]+)*@[a-z0-9-]+(\.[a-z0-9]+)*(\.[a-z]{2,4})';
        $regex_attributes = "((?:\w+\s*=\s*)(?:\w+|\"[^\"]*\"|'[^']*'))*?";
        $regex_email_query = "(?:\?[A-Za-z0-9_= %\.\-\~\_\&;\!\*\(\)\'#&]*)?)";
        $regex_email_href = "href\s*=\s*(['\"])mailto:(".$regex_email.$regex_email_query."\\2";
        $whitespace = "\s*";
        $plaintext_regex = "/".$regex_email."/i";
        $tag_regex = "/<a\s+".$regex_attributes/* 1 */.$whitespace.$regex_email_href/* 3,5,6 */.$whitespace.$regex_attributes/* 7 */.">(.*?)<\/a>/i";
        $tag_replacement = "/<a\s+".$regex_attributes/* 1 */.$whitespace.$regex_email_href/* 3,5,6 */.$whitespace.$regex_attributes/* 7 */.">(.*?)<\/a>/i";

        // First we convert all a tag Emails
        $html = preg_replace_callback($tag_regex, "self::callbackTag", $html);
        // Then we convert all plain text Emails
        $html = preg_replace_callback($plaintext_regex, "self::callbackPlainText", $html);

        $list = array();
        foreach ($this->list as $email) {
            $list[$email->getID()] = $email->getObfuscatedEmail();
        }
        $list = Convert::array2json($list);
        $script = "<script type='text/javascript'>
        window.addEventListener('DOMContentLoaded', function(){
            var emaillist = $list;
            setTimeout(function(){
                $('a[data-obfuscate]').each(function(index){
                    var emailid = $(this).data('obfuscate'),
                        email = emaillist[emailid];
                    if(email != 'undefined') {
                        $(this).attr('href', 'mailto:' + email[1] + '@' + email[0]).attr('data-obfuscate', '');
                    }
                });
                $('span[data-obfuscate]').each(function(index){
                    var emailid = $(this).data('obfuscate'),
                        email = emaillist[emailid];
                    if(email != 'undefined') {
                        $(this).html(email[1] + '@' + email[0]);
                    }
                });
            }, 2000);
        });
        </script>";
        $html = str_ireplace('</body>', $script.'</body>', $html);
        return $html;
    }

    private function callbackTag($matches) {
        $email = EmailObfuscate::create($matches[3])
            ->setText($matches[8])
            ->setAttributes($matches[1].' '.$matches[7]);
        $this->list->Push($email);
        return $email->getLink();
    }

    private function callbackPlainText($matches) {
        // check if we have accidentally picked up an image
        if (preg_match("/^.*\.(jpe?g|png|gif)$/", $matches[0])) {
            return $matches[0];
        }
        $email = EmailObfuscate::create($matches[0])
            ->setText($matches[0]);
        $this->list->Push($email);
        return $email->getText();
    }

    public function preRequest(SS_HTTPRequest $request, Session $session, DataModel $model)
    {
    }
}
