<?php
/**
 * @package silverstripe
 * @subpackage silverstripe-email-obfuscator
 */

class EmailObfuscate extends Object
{
    protected $email = null;

    protected $id = null;

    protected $text = null;

    protected $attributes = array();

    public function __construct($email = null, $text = null, $attributes = null)
    {
        $this->email = $email;
        $this->id = uniqid();
        if (isset($text)) {
            $this->setText($text);
        }
        if (isset($attributes)) {
            $this->setAttributes($attributes);
        }
    }

    public function setText($value = null)
    {
        if ($value) {
            $this->text = $value;
        }
        return $this;
    }

    public function setAttributes($value = null)
    {
        if ($value) {
            if (is_string($value)) {
                $value = explode(' ',trim($value));
            }
            if(!in_array('rel="nofollow"', $value)){
                $value[] = 'rel="nofollow"';
            }
            $this->attributes = $value;
        }
        return $this;
    }

    public function getID()
    {
        return $this->id;
    }

    public function getObfuscatedEmail()
    {
        $email = array_reverse(explode('@', $this->email));
        return $email;
    }

    public function getLink()
    {
        $id = $this->id;
        $attributes = implode(' ',$this->attributes);
        $text = $this->text;
        $fake_email = "gotcha@". $_SERVER['SERVER_NAME'];
        return "<a href='mailto:$fake_email' data-obfuscate='$id' $attributes>$text</a>";
    }

    public function getText()
    {
        $id = $this->id;
        list($name, $domain) = explode('@', $this->email);
        $name = str_split($name);
        foreach ($name as $key =>$character) {
            if ($key > 0) {
                $name[$key] = '.';
            }
        }
        $name= implode('',$name);
        $email = $name.'@'.$domain;
        return "<span data-obfuscate='$id'>$email</span>";
    }
}
