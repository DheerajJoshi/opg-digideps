<?php
namespace AppBundle\Service\Mailer;

/**
 * \Swift_Message utils
 */
class MessageUtils
{
    /**
     */
    protected static $fieldsToSerialize = array(
        'to', 
        'from', 
        'bcc', 
        'cc', 
        'replyTo', 
        'returnPath',
        'subject', 
        'body',
        'sender'
    );
    
    /**
     * @param Swift_Mime_Message $message
     *
     * @return array
     */
    public static function messageToArray(\Swift_Mime_Message $message)
    {
        $ret = array();
        foreach (self::$fieldsToSerialize as $field) {
            $method = "get".ucfirst($field);
            $ret[$field] = $message->$method();
        }
        
        // add parts
        $ret['parts'] = array();
        foreach ($message->getChildren() as $child) {
            $ret['parts'][] = array(
                'body' => $child->getBody(),
                'contentType' => $child->getContentType(),
            );
        }
        
        return $ret;
    }
    
    /**
     * @param array $array
     * 
     * @return \Swift_Mime_Message
     */
    public static function arrayToMessage($array)
    {
        $message = new \Swift_Message;
        
        foreach (self::$fieldsToSerialize as $field) {
            if (!empty($array[$field])) {
                $method = "set".ucfirst($field);
                $message->$method($array[$field]);
            }
        }
        
        foreach ((array)$array['parts'] as $part) {
            $message->addPart($part['body'], $part['contentType']);
        }
        
        return $message;
    }
    
    
    /**
     * @param Swift_Mime_Message $message
     *
     * @return string
     */
    public static function messageToString(\Swift_Mime_Message $message)
    {
        $ret = '';
        foreach (self::$fieldsToSerialize as $field) {
            $method = "get".ucfirst($field);
            $ret .= sprintf("%s: %s\n", $field, $message->$method());
        }
        
        // add parts
        foreach ($message->getChildren() as $k => $child) {
            $ret .= sprintf("PART $k -----\n Body: %s\n ContentType:%s\n",
                $child->getBody(),
                $child->getContentType()
            );
        }
        
        $ret .= "--------------------------\n";
        
        return $ret;
    }
    
}