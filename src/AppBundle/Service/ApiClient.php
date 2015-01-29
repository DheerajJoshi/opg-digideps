<?php
namespace AppBundle\Service;

use JMS\Serializer\SerializerInterface;

class ApiClient
{
    /**
     * @var RestClient
     */
    private $restClient;
    
    /**
     * @var SerializerInterface
     */
    private $jsonSerializer;
    
     /**
     * @var string
     */
    private $format;
    
    
    public function __construct(RestClient $erstClient, SerializerInterface $jsonSerializer, $format)
    {
        $this->restClient = $erstClient;
        $this->jsonSerializer = $jsonSerializer;
        $this->format = $format;
    }
    
    private function checkResponseArray($responseArray)
    {
         if (empty($responseArray)) {
            throw new \RuntimeException("No json response from the client. Response: ");
        }
        if (empty($responseArray['success'])) {
            throw new \Exception("The API returned an error" . $responseArray['message']);
        }
    }
    
    
    public function getEntity($endpoint, $class)
    {
        $body = $this->restClient->get($endpoint)->getBody();
        $responseArray = $this->jsonSerializer->deserialize($body, 'array', $this->format);
        $this->checkResponseArray($responseArray);
        
        $ret = $this->jsonSerializer->deserialize(json_encode($responseArray['data']), 'AppBundle\\Entity\\' . $class, 'json');
        
        return $ret;
    }
    
    
    public function getEntities($endpoint, $class)
    {
        $body = $this->restClient->get($endpoint)->getBody();
        $responseArray = $this->jsonSerializer->deserialize($body,'array',$this->format);
        $this->checkResponseArray($responseArray);
        
        $ret = array();
        foreach ($responseArray['data'] as $row) { 
            $ret[] = $this->jsonSerializer->deserialize(json_encode($row), 'AppBundle\\Entity\\' . $class, 'json');
        }
        
        return $ret;
    }
    
    
    public function post($endpoint, array $data)
    {
        $body = json_encode($data);
        
        $response = $this->restClient->post($endpoint, ['body'=>$body]);
        
        return $response->getBody();
    }

   
}